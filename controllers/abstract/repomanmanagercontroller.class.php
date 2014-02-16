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