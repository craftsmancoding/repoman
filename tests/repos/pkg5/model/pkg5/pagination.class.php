<?php
/*-------------------------------------------------------------------------------
Pagination Library: Tokenized formatting to create pagination links.

USAGE:

There are 2 ways to identify page numbers during pagination. The most obvious one
is that we number each page: 1,2,3.  This corresponds to pagination links
like mypage.php?page=3 for example.

		require_once('Pagination.php');
		$p = new Pagination();
		$offset = $p->page_to_offset($_GET['page'], $_GET['rpp']);
		$p->set_offset($offset); //
		$p->set_results_per_page($_GET['rpp']);  // You can optionally expose this to the user.
		$p->extra = 'target="_self"'; // optional
		print $p->paginate(100); // 100 is the count of records

The other way to identify page numbers is via an offset of the records. This is
a bit less intuitive, but it is more flexible if you ever want to let the user
change the # of results shown per page. Imagine if someone bookmarked a URL
with ?page=3 on it, and then adjusted the # of records per page from 10 to 100.
The page would contain an entirely different set of records, whereas with the offset
method, e.g. ?offset=30, the page would at least start with the same records no matter
if the # of records per page changed.


Private functions reference internal publics; public functions do not.

AUTHOR: everett@fireproofsocks (2010, revised 2012)

@package query
*/

class Pagination {

	// Formatting template chunks, set via set_tpl() or set_tpls()
	private $firstTpl = '<a href="[[+base_url]]&offset=[[+offset]]" [[+extra]]>&laquo; First</a> &nbsp;';
	private $lastTpl = '&nbsp;<a href="[[+base_url]]&offset=[[+offset]]" [[+extra]]>Last &raquo;</a>';
	private $prevTpl = '<a href="[[+base_url]]&offset=[[+offset]]" [[+extra]]>&lsaquo; Prev.</a>&nbsp;';
	private $nextTpl = '&nbsp;<a href="[[+base_url]]&offset=[[+offset]]" [[+extra]]>Next &rsaquo;</a>';
	private $currentPageTpl = '&nbsp;<span>[[+page_number]]</span>&nbsp;';
	private $pageTpl = '&nbsp;<a href="[[+base_url]]&offset=[[+offset]]" [[+extra]]>[[+page_number]]</a>&nbsp;';
	private $outerTpl = '<div id="pagination">[[+content]]<br/>
				Page [[+current_page]] of [[+page_count]]<br/>
				Displaying records [[+first_record]] thru [[+last_record]] of [[+record_count]]
			</div>';
	
	// Stores any error messages (useful for devs)
	public $errors = array();
	
	
	// Controls how many pagination links are shown
	private $link_cnt = 10; 

	// Controls how many pages are flipped forward/backwards when the prev/next links are clicked
	private $jump_size = 1;
	
	// If not defined, this is the default number of links per page
	private $default_results_per_page = 25;
	
	// Contains all placeholders passed to the outerTpl
	public $properties = array();

	/**
	 * Bootstrap some properties
	 */
	function __construct() {
		// set defaults
		$this->extra = '';
		$this->set_base_url('?');
		$this->set_offset(0);
		$this->set_results_per_page($this->default_results_per_page);
	}

	/**
	 * Dynamic getter. Note that there is a PHP "feature" (not a bug) that prohibits
	 * $this->$x['y']; usage inside of magic functions. You must use a hard-coded value
	 * instead of a variable ('properties' instead of $x our case)
	 * 
	 * @param string $name
	 * @return mixed
	 */
	public function __get($name) {
		return $this->properties[$name];
	}
	
	/**
	 * Dynamic setter to set any placeholder property (used in formatting the tpls).
	 *
	 * @param string $name
	 * @param mixed $val -- ought to always be a string.
	 */
	public function __set($name, $val) {
		if (is_scalar($name)) {
			$this->properties[$name] = $val;
		}
	}


	/**
	 * Parses the first template (firstTpl)
	 *
	 * @return string
	 */
	private function _parse_firstTpl() {
		if ($this->offset > 0) {
			return $this->_parse($this->firstTpl, array('offset'=> 0, 'page_number'=> 1 ));
		} else {
			return '';
		}
	}


	/**
	 * Parse the last template (lastTpl)
	 *
	 * @return string
	 */
	private function _parse_lastTpl() {
		$page_number = $this->page_count;
		$offset = $this->page_to_offset($page_number, $this->results_per_page);
		if ($this->current_page < $this->page_count) {
			return $this->_parse($this->lastTpl, array(
				'offset'=> $offset, 
				'page_number'=> $page_number
				)
			);
		} else {
			return '';
		}
	}

	/**
	 * @return string
	 */
	private function _parse_pagination_links() {
		$output = '';
		for ( $page = $this->lowest_visible_page; $page <= $this->highest_visible_page; $page++ ) {
			$offset = $this->page_to_offset( $page, $this->results_per_page);

			if ( $page == $this->current_page ) {
				$output .= $this->_parse( $this->currentPageTpl, array('offset'=> $offset, 'page_number'=> $page));
			} else {
				$output .= $this->_parse($this->pageTpl, array('offset'=> $offset, 'page_number'=> $page));
			}
		}
		return $output;
	}


	/**
	 * Parse the tpl used for the "Next >" link.
	 *
	 * @return string
	 */
	private function _parse_nextTpl() {
		$page_number = $this->_get_next_page( $this->current_page, $this->page_count );
		$offset = $this->page_to_offset( $page_number, $this->results_per_page );
		if ( $this->current_page < $this->page_count ) {
			return $this->_parse($this->nextTpl, array('offset'=> $offset, 'page_number'=> $page_number));
		} else {
			return '';
		}
	}

	/**
	 * Parse the tpl used for the "< Prev" link.
	 *
	 * @return string
	 */
	private function _parse_prevTpl() {
		$page_number = $this->_get_prev_page( $this->current_page, $this->page_count );
		$offset = $this->page_to_offset( $page_number, $this->results_per_page );
		if ($this->offset > 0) {
			return $this->_parse( $this->prevTpl, array('offset'=> $offset, 'page_number'=> $page_number) );
		}
	}

	/**
	 * A calcuation to get the highest visble page when displaying a cluster of 
	 * links, e.g. 4 5 6 7 8  -- this function is what calculates that "8" is the 
	 * highest visible page.
	 *
	 * @param integer $current_pg
	 * @param integer $total_pgs_shown
	 * @param integer $total_pgs
	 * @return integer
	 */
	private function _get_highest_visible_page($current_pg, $total_pgs_shown, $total_pgs) {
		//if ($total_pgs_shown is even)
		$half = floor($total_pgs_shown / 2);

		$high_page = $current_pg + $half;
		$output = '';
		if ($high_page < $total_pgs_shown) {
			$output = $total_pgs_shown;
		} else {
			$output = $high_page;
		}
		if ($output > $total_pgs) {
			$output = $total_pgs;
		}
		return $output;
	}

	/**
	 * Calculates the lowest of the visible pages, keeping the current page floating
	 * in the center.
	 *
	 * @param integer $current_pg
	 * @param integer $pgs_visible
	 * @param integer $total_pgs
	 * @return integer
	 */
	private function _get_lowest_visible_page($current_pg, $pgs_visible, $total_pgs) {
		//if ($pgs_visible is even, subtract the 1)
		$half = floor($pgs_visible / 2);
		$output = 1;
		$low_page = $current_pg - $half;
		if ($low_page < 1) {
			$output = 1;
		} else {
			$output = $low_page;
		}
		if ( $output > ($total_pgs - $pgs_visible) ) {
			$output = $total_pgs - $pgs_visible + 1;
		}
		if ($output < 1) {
			$output = 1;
		}
		return $output;
	}

	//------------------------------------------------------------------------------
	/**
	 * The page targeted by the Next link. 
	 *
	 * @param integer $current_pg
	 * @param integer $total_pgs
	 * @return integer
	 */
	private function _get_next_page($current_pg, $total_pgs) {
		$next_page = $current_pg + $this->jump_size;
		if ($next_page > $total_pgs) {
			return $total_pgs;
		} else {
			return $next_page;
		}
	}
	
	//------------------------------------------------------------------------------
	/**
	 * The page targeted by the Prev link.
	 *
	 * @param integer $current_pg
	 * @param integer $total_pgs
	 * @return integer
	 */
	private function _get_prev_page($current_pg, $total_pgs) {
		$prev_page = $current_pg - $this->jump_size;
		if ($prev_page < 1) {
			return 1;
		} else {
			return $prev_page;
		}
	}

	//------------------------------------------------------------------------------
	/**
	 * Standard parsing function to replace [[+placeholders]] with value
	 *
	 * @param string $tpl
	 * @param array $record
	 * @return string
	 */
	private function _parse($tpl, $record) {

		foreach ($record as $key => $value) {
			$tpl = str_replace('[[[+'.$key.']]', $value, $tpl);
		}
		return $tpl;
	}
		
	//------------------------------------------------------------------------------
	//! PUBLIC FUNCTIONS
	//------------------------------------------------------------------------------
	/**
	 * convert an offset number to a page number
	 *
	 * @param integer $offset
	 * @param integer $results_per_page (optional) defaults to the set $this->results_per_page
	 * @return integer
	 */
	public function offset_to_page($offset, $results_per_page=null) {
		$offset = (int) $offset;
		if ($results_per_page) {
			$results_per_page = (int) $results_per_page;
		}
		else {
			$results_per_page = $this->results_per_page;
		}
		if (is_numeric($results_per_page) && $results_per_page > 0) {
			return (floor($offset / $results_per_page)) + 1;
		} else {
			return 1;
		}
	}

	//------------------------------------------------------------------------------
	/**
	 * Convert page number to an offset
	 *
	 * @param integer $page
	 * @param integer $results_per_page
	 * @return integer
	 */
	public function page_to_offset($page, $results_per_page=null) {
		$page = (int) $page;
		if ($results_per_page) {
			$results_per_page = (int) $results_per_page;
		}
		else {
			$results_per_page = $this->results_per_page;
		}
		if (is_numeric($page) && $page > 1) {
			return ($page - 1) * $results_per_page;
		} else {
			return 0;
		}
	}

	//------------------------------------------------------------------------------
	/**
	 * This is the primary interface for the whole library = Get the goods!
	 * INPUT: (int) the # of records you're paginating.
	 * OUTPUT: formatted links
	 *
	 * @param integer $record_count
	 * @return string	html used for pagination (formatted links)
	 */
	public function paginate($record_count) {

		$record_count = (int) $record_count;

		// No point in doing pagination if there aren't enough records
		if ( empty($record_count)) {
			return '';
		}

		// Pagination is not necessary if you are on the first page and the record count
		// is less than the results per page.
		if ( ($record_count <= $this->results_per_page) && $this->offset == 0 ) {
			return ' ';
		}

		$this->page_count = ceil($record_count / $this->results_per_page);

		$this->current_page = $this->offset_to_page( $this->offset, $this->results_per_page );

		$this->lowest_visible_page = $this->_get_lowest_visible_page(
			$this->current_page
			, $this->link_cnt
			, $this->page_count
		);

		$this->highest_visible_page = $this->_get_highest_visible_page (
			$this->current_page
			, $this->link_cnt
			, $this->page_count
		);

		$this->first_record = $this->offset + 1;

		if ( $this->offset + $this->results_per_page >= $record_count) {
			$this->last_record = $record_count;
		}
		else {
			$this->last_record = $this->offset + $this->results_per_page;
		}

		// We need keys from config
		$this->properties['record_count'] = $record_count;
        $this->properties['current_page'] = $this->current_page;
        $this->properties['lowest_visible_page'] = $this->lowest_visible_page;
        $this->properties['highest_visible_page'] = $this->highest_visible_page;
        $this->properties['results_per_page'] = $this->results_per_page;
        $this->properties['first_record'] = $this->first_record;
        $this->properties['last_record'] = $this->last_record;
        $this->properties['link_cnt'] = $this->link_cnt;
        
		$this->properties['content'] = $this->_parse_firstTpl();
		$this->properties['content'] .= $this->_parse_prevTpl();
		$this->properties['content'] .= $this->_parse_pagination_links();
		$this->properties['content'] .= $this->_parse_nextTpl();
		$this->properties['content'] .= $this->_parse_lastTpl();
		$first_pass = $this->_parse($this->outerTpl, $this->properties);
		
		$this->properties['pagination_links'] = $this->_parse($first_pass, $this->properties);
		return $this->properties['pagination_links'];
	}

	//------------------------------------------------------------------------------
	/**
	 * This is the base url used when creating all the links to all the pages.
	 * WARNING: use a clean URL!!! Filter out any Javascript or anything that might
	 * lead to an XSS attack before you set this value -- this function does not do
	 * any of its own filtering.
	 * The base_url is intented to be manually set, not open to user input.
	 *
	 * @param string $base_url
	 */
	public function set_base_url($base_url) {
		if ( !preg_match('/\?/', $base_url) ) {
			$base_url = $base_url . '?';
		}

		$this->properties['base_url'] = $base_url;
	}

	//------------------------------------------------------------------------------
	/**
	 * Controls how many pages are flipped forwards/backwards when the prev/next
	 * links are clicked. With a value of 1, this operates like a book -- flip
	 * forward or back one page at a time.
	 * Giant leaps are possible e.g. if you display 10 links at a time and you flip 
	 * 10 pages forward, e.g. from displaying pages 11 - 20 to 21 - 30, etc.
	 *
	 * @param integer $pgs 1 or greater
	 * @return void
	 */
	public function set_jump_size($pgs) {
		$pgs = (int) $pgs;
		if ($pgs > 0) {
			$this->jump_size = $pgs;	
		}
		else {
			$this->errors[] = 'set_jump_size() requires an integer greater than 0';
		}	
	}
	
	//------------------------------------------------------------------------------
	/**
	 * Set the number of pagination links to display, e.g. 3 might generate a set 
	 * of links like this:
	 *
	 *		<< First < Prev 4 5 6 Next > Last >>
	 *
	 * Whereas setting a value of 6 might generate a set of links like this:
	 *
	 *		<< First < Prev 4 5 6 7 8 9 Next > Last >>
	 *
	 * @param integer $cnt the total number of links to show
	 * @return void
	 */
	public function set_link_cnt($cnt) {
		$cnt = (int) $cnt;
		if ($cnt > 0) {
			$this->link_cnt = $cnt;
		}
		else {
			$this->errors[] = 'set_link_cnt() requires an integer greater than 0';
		}
	}
	
	//------------------------------------------------------------------------------
	/**
	 * Goes thru integer filter; this one IS expected to get its input from users
	 * or from the $_GET array, so using (int) type-casting is a heavy-handed filter.
	 *
	 * @param integer $offset
	 */
	public function set_offset($offset) {
		$offset = (int) $offset;
		if ($offset >= 0 ) {
			$this->properties['offset'] = $offset;
		}
		else {
			$this->properties['offset'] = 0;
		}
	}


	//------------------------------------------------------------------------------
	/**
	 * Set the number of results to show per page.
	 *
	 * @param integer $results_per_page 
	 */
	public function set_results_per_page($results_per_page) {
		$results_per_page = (int) $results_per_page;
		if ($results_per_page > 0 ) {
			$this->properties['results_per_page'] = $results_per_page;
		}
		else {
			$this->errors[] = "set_results_per_page() requires an integer greater than zero.";
		}
	}

	//------------------------------------------------------------------------------
	/**
	 * Set a single formatting tpl.
	 * @param string $tpl one of the named tpls
	 * @param string $content
	 */
	public function set_tpl($tpl, $content) {
		if (!is_scalar($content)) {
			$this->errors[] = "Content for $tpl tpl must be a string.";
		}
		if (in_array($tpl, array('firstTpl','lastTpl','prevTpl','nextTpl','currentPageTpl',
			'pageTpl','outerTpl'))) {
			$this->$tpl = $content;
		}
		else {
			$this->errors[] = "Unknown tpl " . strip_tags($tpl);
		}
	}

	//------------------------------------------------------------------------------
	/**
	 * Set all the tpls in one go by supplying an array.  You must supply 
	 * a *complete* set of tpls to this function! A missing key is equivalent to 
	 * supplying an empty string.
	 *
	 * @param array $tpls, associative array with keys 
	 */
	public function set_tpls($tpls) {
		if (is_array($tpls)) {
			$tpls = array_merge(array('firstTpl'=>'','lastTpl'=>'','prevTpl'=>'',
			'nextTpl'=>'','currentPageTpl'=>'','pageTpl'=>'','outerTpl'=>''), $tpls);
			foreach($tpls as $tpl => $v) {
				$this->set_tpl($tpl,$v);
			}
		}
		else {			
			$this->errors[] = "set_tpls() requires array input.";
		}
	}
}

/*EOF*/