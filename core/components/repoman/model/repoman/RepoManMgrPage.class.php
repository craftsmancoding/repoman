<?php
/**
 * Handles drawing of RepoMan's CMP pages in the MODX manager.
 *
 *
 */
class RepoManMgrPage {

	public $modx;
	public $repoman;
	
	public $action = 'index';
	public $repo_dir = '';
	
	// Page properties (like placeholders) used on manager pages
	public $props = array(
		'pagetitle' => '',
		'msg' => '',
		'content' => '',
	);


	//------------------------------------------------------------------------------
	//! MAGIC
	//------------------------------------------------------------------------------
	/** 
	 * Manager page air-traffic control
	 */
	public function __call($method, $args) {
	
		
		if (!method_exists($this, $method)) {
			return $this->show404();
		}
		
		$this->$method();
	}

	/**
	 * Some general gatekeeping
	 */
	public function __construct($modx) {
		$this->modx = &$modx;
		$this->repoman = new RepoMan();
		
		$this->props['pagetitle'] = 'Repo Man';
		
		$this->repo_dir = $modx->getOption('repoman.repo_dir');

		if (empty($this->repo_dir)) {
			$this->props['msg'] = $this->_get_msg('Please set the repoman.repo_dir directory.','error');
			return $this->_render();
		}
		if (!file_exists($this->repo_dir)) {
			$this->props['msg'] = $this->_get_msg($this->repo_dir .' does not exist!','error');
			return $this->_render();
		}
		if (!is_dir($this->repo_dir)) {
			$this->props['msg'] = $this->_get_msg($this->repo_dir .' must be a directory!','error');
			return $this->_render();
		}
		
		$this->repo_dir = preg_replace('#'.DIRECTORY_SEPARATOR.'$#','',$this->repo_dir); // strip trailing slash
		
	}
	
	//------------------------------------------------------------------------------
	//! PRIVATE	
	//------------------------------------------------------------------------------

	/**
	 * Used for errors, warnings, and success messages
	 *
	 * @param string $msg
	 * @param string $type error|warning|success
	 */
	private function _get_msg($msg, $type='error') {
		return $this->_load($type, array('msg'=>$msg));
	}
	
	/**
	 * Loads a controller file.
	 * @param	string	$file
	 */
	private function _load($file, $data=array()) {
		extract($data);
		ob_start();
		include(REPOMAN_PATH.'views/'.$file.'.php');
		return ob_get_clean();
	}
	
	/** 
	 * Relies on $this->props
	 */
	private function _render() {
		
		extract($this->props);
		
		ob_start();
		include(REPOMAN_PATH.'views/templates/mgr_page.php');
		return ob_get_clean();
	}

	/**
	 * If there's a problem with the repo name, this will return it.
	 *
	 * @param string 
	 * @return string error msg on problem|false
	 */
	private function _invalid_repo_name($repo) {
		if (preg_match('/[^0-9a-z_\-]/i', $repo)) {
			return $this->_get_msg('Invalid repo name. <a href="'.REPOMAN_MGR_URL.'">Back</a>','error');
		}
		if (!file_exists($this->repo_dir.'/'.$repo)) {
			return $this->_get_msg($this->repo_dir.'/'.$repo .' does not exist!','error');
		}
		if (!is_dir($this->repo_dir.'/'.$repo)) {
			return $this->_get_msg($this->repo_dir .'/'.$repo. ' must be a directory!','error');
		}	
		
		return false; // no problem
	}

	//------------------------------------------------------------------------------
	//! PUBLIC PAGES
	//------------------------------------------------------------------------------
	
	/**
	 * Default controller
	 */
	public function index() {
		

		// Get the repo folders in the repo directory
		$options = array();
		
		$this->props['pagetitle'] = 'Your Repositories';
		$repos = '';
		//foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($repo_dir)) as $filename) {
		foreach (new RecursiveDirectoryIterator($this->repo_dir) as $filename) {
			if (is_dir($filename)) {
				$shortname = preg_replace('#^'.$this->repo_dir.'/#','',$filename);
				if ($shortname != '.' && $shortname != '..') {
					$repos .= $this->_load('li_repo'
						, array('link'=>REPOMAN_MGR_URL .'&action=view&repo='.$shortname
						, 'item'=>$shortname)
					);
				}
			}	
		}
		
		if (empty($repos)) {
		//if (true) {
			$this->props['msg'] = $this->_get_msg('You do not have any repos in your repo directory yet.','error');
		}
		else {
			// Wrap
			$this->props['content'] = $this->_load('ul', array('content' =>$repos,'class'=>'repos'));
		}
		
		return $this->_render();
	}
	
	
	
	public function show404() {
		$this->props['pagetitle'] = 'Invalid Action';
		$this->props['msg'] = $this->_get_msg('The action you are requesting could not be found. <a href="'.REPOMAN_MGR_URL.'">Back</a>','error');
		return $this->_render();
	}

	/** 
	 * View a single repo
	 */
	public function view() {
		$this->props['pagetitle'] = 'View Repo';		
		$repo = '';
		if (isset($_GET['repo'])) {
			$repo = $_GET['repo'];
		}
	
		if ($this->props['msg'] = $this->_invalid_repo_name($repo)) {
			return $this->_render();
		}

		
		if (!$readme = $this->repoman->get_readme($this->repo_dir .'/'.$repo)) {
			$readme = $this->_get_msg('No README.md file found.','warning');
		}
		// Wrap/format the readme
		else {
			// TODO: http://michelf.ca/projects/php-markdown/ convert markdown
			$readme = $this->_load('readme', array('readme'=>$readme));
		}
		
		$this->props['content'] = $this->_load('page_repo', 
			array(
				'readme'=>$readme,
				'index_url' => REPOMAN_MGR_URL, 
				'sync_url' => REPOMAN_MGR_URL .'&action=sync&repo='.$repo, 
			)
		);
		
		return $this->_render();
	}

	/**
	 * Sync a repo: read data from the filesystem and make sure MODX is up to date.
	 *
	 *
	 */
	public function sync() {

		$this->props['pagetitle'] = 'Sync MODX With Repo';		
		$repo = '';
		if (isset($_GET['repo'])) {
			$repo = $_GET['repo'];
		}
	
		if ($this->props['msg'] = $this->_invalid_repo_name($repo)) {
			return $this->_render();
		}

		$this->props['content'] = 'Syncing';
		
		//foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($repo_dir)) as $filename) {
		return $this->repoman->sync_modx($this->repo_dir.'/'.$repo);
/*
		$dir = $this->repo_dir.'/'.$repo.'/core/components/'.$repo.'/elements/';
		$items = '';
		foreach (new RecursiveDirectoryIterator($dir) as $filename) {
			if (is_dir($filename)) {
				$shortname = preg_replace('#^'.$this->repo_dir.'/#','',$filename);
				if ($shortname != '.' && $shortname != '..') {
					$items .= $this->_load('li'
						, array('link'=>REPOMAN_MGR_URL .'&action=view&repo='.$shortname
						, 'item'=>$shortname)
					);
				}
			}	
		}
		return $items;
*/
		
		return $this->_render();	
	}

}
/*EOF*/