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
            'category' => '',
            'settings' => '',
            'chunks' => '',
            'plugins' => '',
            'snippets' => '',
            'templates' => '',
            'tvs' => ''
        );
        
        $data['category_options'] = '';
        $categories = $this->modx->getCollection('modCategory');
        foreach ($categories as $c) {
            $is_selected = (isset($_POST['category']) && $_POST['category'] == $c->get('category')) ? ' selected="selected"' : '';
            $data['category_options'] .= '<option value="'.$c->get('id').$is_selected.'">'.$c->get('category').'</option>';
        }
        
		if (!empty($_POST)) {
            $data['namespace'] = preg_replace('/[^a-z0-9\_]/','',$this->modx->getOption('namespace', $_POST));
            $data['package_name'] = strip_tags($this->modx->getOption('package_name', $_POST));
            $data['description'] = strip_tags($this->modx->getOption('description', $_POST));
            $data['author_name'] = strip_tags($this->modx->getOption('author_name', $_POST));
            $data['author_email'] = strip_tags($this->modx->getOption('author_email', $_POST));
            $data['author_homepage'] = strip_tags($this->modx->getOption('author_homepage', $_POST));
            
            $data['category_id'] = (int) $this->modx->getOption('category_id', $_POST);
            $data['settings'] = $this->modx->getOption('settings', $_POST);
            
            $data['chunks'] = (int) $this->modx->getOption('chunks', $_POST);
            $data['plugins'] = (int) $this->modx->getOption('plugins', $_POST);
            $data['snippets'] = (int) $this->modx->getOption('snippets', $_POST);
            $data['templates'] = (int) $this->modx->getOption('templates', $_POST);
            $data['tvs'] = (int) $this->modx->getOption('tvs', $_POST);
            //print '<pre>'.print_r($data,true).'</pre>'; exit;
            $Repoman = new Repoman($this->modx, $data);
            $Repoman->create($data['namespace'], $data);
            
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