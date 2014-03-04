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
    

    public $repo_dir;
    
    public $valid_controllers = array('home','view','ajax','create');
    
    public function __construct(modX &$modx,$config = array()) {
        
        require_once $modx->getOption('repoman.core_path','', MODX_CORE_PATH.'core/components/repoman/').'vendor/autoload.php';
        $controller = (isset($_REQUEST['action'])) ? $_REQUEST['action'] : 'home';
        if (!in_array($controller, $this->valid_controllers)) {
            $_REQUEST['action'] = '404';
        }
        $assets_url = $modx->getOption('repoman.assets_url','',MODX_ASSETS_URL.'components/repoman/');
		$modx->regClientCSS($assets_url.'style.css');
        parent::__construct($modx,$config);
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

	public function get_info($repo) {
        try {
            $dir = Repoman::get_dir(MODX_BASE_PATH.$this->modx->getOption('repoman.dir'));
            $config = Repoman::load_config($dir.$repo);
            //print '<pre>'.print_r($config,true).'</pre>'; exit;
            return $this->_load('info', $config);
        }
        catch (Exception $e) {
            return 'There were problems in the composer.json file. '.$e->getMessage();
        }
	}
	
	public function get_readme($repo) {
        try {
            $dir = Repoman::get_dir(MODX_BASE_PATH.$this->modx->getOption('repoman.dir'));
            $valid_files = array('readme.md','README.md','README.MD','readme.txt','README.TXT');
            foreach ($valid_files as $f) {                
                if (file_exists($dir.$repo.'/'.$f)) {
                    return \Michelf\Markdown::defaultTransform(file_get_contents($dir.$repo.'/'.$f));        
                }
            }
            return $this->_get_msg('No README.md file found.','warning');
        }
        catch (Exception $e) {
            return $e->getMessage();
        }
	}
	
	/**
	 * Generate a series of links for the repo contained in the given $subdir
	 * @param string $subdir
	 */
	public function get_repo_links($subdir) {
        $data = array();	
        $data['update_available'] = false;
//        $data['repo'] = $subdir;
        $data['subdir'] = $subdir;
        try {
            $dir = Repoman::get_dir(MODX_BASE_PATH.$this->modx->getOption('repoman.dir'));
            $config = Repoman::load_config($dir.'/'.$subdir);
            $namespace = $config['namespace'].'.version';
            $data['namespace'] = $config['namespace'];
			if (!$Setting = $this->modx->getObject('modSystemSetting', array('key' => $namespace))) {
                $data['installed'] = false;
			}
			else {
                //print $Setting->get('value'); exit;
                //print $config['version']; exit;
			    $data['installed'] = true;
			    if (version_compare($Setting->get('value'), $config['version'],'<')) {
                    $data['update_available'] = true;
			    }
			}
        }
        catch (Exception $e) {
            return $e->getMessage();
        }

        
        return $this->_load('links', $data);
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