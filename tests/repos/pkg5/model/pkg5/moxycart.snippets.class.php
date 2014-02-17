<?php 
/**
 * BaseClass for Moxycart Snippets
 */
class MoxycartSnippet {

	/** @var $modx modX */
    public $modx;

    public function __construct(&$modx) {
        $this->modx =& $modx;       
		$this->modx->getService('moxycart');
    }

    /**
	* Pull records and format the returned data
	* @param string $method - Available methods from moxycrt.class.php
	* @param array $args
	* @return string $output
    **/
    public function execute($method,$args) {
    	$outerTpl = $this->modx->getOption('outerTpl',$args);
		$innerTpl = $this->modx->getOption('innerTpl',$args);
		$total = $this->modx->getOption('total',$args,0);
		$firstClass = $this->modx->getOption('firstClass',$args,'first');

		unset($args['outerTpl']);
		unset($args['innerTpl']);
		unset($args['total']);
		unset($args['firstClass']);

		$records = $this->modx->moxycart->$method($args, true);
		
        if($total) {
        	return $records['total'];
        }
        		
		if($records['total'] == 0) {
			return '';
		}

		$output = '';
		if (isset($records['results']) && is_array($records['results'])) {
        	foreach ($records['results'] as $index => $row) {
        		if($index == 0) {
        			$row['firstClass'] = $firstClass;
        		}
        		$row['index'] = $index+1;
           		$output .= $this->modx->getChunk($innerTpl,$row);
        	}			
		}


        if ($outerTpl) {
    		$output = $this->modx->getChunk($outerTpl,array('moxy.items' => $output)); 
		}
		return $output;
    }

    public function get_rate_average($method,$args) {
    	$records = $this->modx->moxycart->$method($args, true);

    	$total = 0;
    	$rating = 0;
		if($records['total'] == 0) {
			return '';
		}

		foreach ($records['results'] as $row) {
	   		$total += $row['rating'];
		}

		$rating = ($total / $records['total']) / $records['total'];
		return round($rating, 2);

    }


}







