<?php
/**
 * The abstract Repoman Controller.
 * In this class, we define stuff we want on all of our controllers.
 */
abstract class RepomanManagerController extends modExtraManagerController {
    /** @var bool Set to false to prevent loading of the header HTML. */
    public $loadHeader = true;
    /** @var bool Set to false to prevent loading of the footer HTML. */
    public $loadFooter = true;
    /** @var bool Set to false to prevent loading of the base MODExt JS classes. */
    public $loadBaseJavascript = true;
    /** @var array An array of possible paths to this controller's templates directory. */
    public $templatesPaths = array();
    /** @var array An array of possible paths to this controller's directory. */
    //public $controllersPaths;
    /** @var modContext The current working context. */
    //public $workingContext;
    /** @var modMediaSource The default media source for the user */
    //public $defaultSource;
    /** @var string The current output content */
    //public $content = '';
    /** @var array An array of request parameters sent to the controller */
   // public $scriptProperties = array();
    /** @var array An array of css/js/html to load into the HEAD of the page */
    //public $head = array('css' => array(),'js' => array(),'html' => array(),'lastjs' => array());
    /** @var array An array of placeholders that are being set to the page */
    //public $placeholders = array();
    
        
	protected function _get_dir_options($current_val) {
		
		$options = '';
		foreach (new RecursiveDirectoryIterator(MODX_BASE_PATH) as $filename) {
			if (is_dir($filename)) {
				$shortname = preg_replace('#^'.MODX_BASE_PATH.'#','',$filename);
				if ($shortname != '.' && $shortname != '..'
					&& '/'.$shortname.'/' != MODX_CONNECTORS_URL
					&& '/'.$shortname.'/' != MODX_MANAGER_URL
					&& '/'.$shortname.'/' != '/processors/'
					&& '/'.$shortname.'/' != '/core/'
					) {
					//$val = '{base_path}'.$shortname;					
					$val = MODX_BASE_PATH .$shortname;
					$selected = '';					
					if (MODX_BASE_PATH.$shortname == $current_val) {
						$selected = ' selected="selected"';
					}
					$label = $shortname.'/';
					$options .= sprintf('<option value="%s"%s>%s</option>',$val,$selected,$label);
				}
			}
		}
		return $options;
	}

	/**
	 * Used for errors, warnings, and success messages
	 *
	 * @param string $msg
	 * @param string $type error|warning|success
	 */
	protected function _get_msg($msg, $type='error') {
		return $this->_load($type, array('msg'=>$msg));
	}

	/**
	 * Loads a controller file.
	 * @param	string	$file
	 */
	protected function _load($file, $data=array()) {
		extract($data);
		ob_start();
		include $this->config['namespace_path'].'views/'.$file.'.php';
		return ob_get_clean();
	}
	
    /**
     * Initializes the main manager controller. You may want to load certain classes,
     * assets that are shared across all controllers or configuration. 
     *
     * All your other controllers in this namespace should extend this one.
     *
     */
    public function initialize() {

    }

    /**
     * Defines the lexicon topics to load in our controller.
     * @return array
     */
    public function getLanguageTopics() {
        return array('repoman:default');
    }
    
    /**
     * We can use this to check if the user has permission to see this controller
     * @return bool
     */
    public function checkPermissions() {
        return true; // TODO
    }

	/** 
	 * Relies on $this->props
	 */
	protected function _render() {
		
		extract($this->props);
		
		ob_start();
		include $this->config['namespace_path'].'views/templates/mgr_page.php';
		return ob_get_clean();
	}
	    
    /**
     * Get a URL for a given action in the manager
     *
     * @param string $action
     * @param array $args any additional url parameters
     * @return string
     */
    public function getUrl($action, $args=array()) {
        $url = '';
        foreach ($args as $k => $v) {
            if (is_scalar($k) && is_scalar($v)) {
                $url .= '&'.$k.'='.$v;
            }
        }
        return MODX_MANAGER_URL . '?a='.$this->config['id'].'&action='.$action.$url;
    }
}