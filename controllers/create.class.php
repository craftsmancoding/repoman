<?php
/**
 * The name of the controller is based on the action (home) and the
 * namespace. This home controller is loaded by default because of
 * our IndexManagerController.
 */
class RepomanCreateManagerController extends RepomanManagerController {
    /** @var bool Set to false to prevent loading of the header HTML. */
    public $loadHeader = true;
    /** @var bool Set to false to prevent loading of the footer HTML. */
    public $loadFooter = true;
    /** @var bool Set to false to prevent loading of the base MODExt JS classes. */
    public $loadBaseJavascript = true;
    /**
     * Any specific processing we want to do here. Return a string of html.
     * @param array $scriptProperties
     */
    public function process(array $scriptProperties = array()) {
		$this->props['pagetitle'] = 'Create New Repo';	

        // Defaults
        $data = array(
            'namespace' => '',
            'package_name' => '',
            'description' => '',
            'author_name' => $this->modx->user->Profile->get('fullname'),
            'author_email' => $this->modx->user->Profile->get('email'),
            'author_homepage' => MODX_SITE_URL,
        );
        
		if (!empty($_POST)) {
            $data['namespace'] = preg_replace('/[^a-z0-9\_]/','',$this->modx->getOption('namespace', $_POST));
            $data['package_name'] = strip_tags($this->modx->getOption('package_name', $_POST));
            $data['description'] = strip_tags($this->modx->getOption('description', $_POST));
            $data['author_name'] = strip_tags($this->modx->getOption('author_name', $_POST));
            $data['author_email'] = strip_tags($this->modx->getOption('author_email', $_POST));
            $data['author_homepage'] = strip_tags($this->modx->getOption('author_homepage', $_POST));
            
            print '<pre>'.print_r($data,true).'</pre>'; exit;
            
		}
		
		$this->props['content'] = $this->_load('page_create', $data);
		
		return $this->_render();

    }
    
    /**
     * The pagetitle to put in the <title> attribute.
     * @return null|string
     */
    public function getPageTitle() {
        return 'Create Repo';
    }

}
/*EOF*/