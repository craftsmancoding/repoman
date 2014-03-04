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
        $this->props['msg'] = '';	
        
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
            'sample_chunks' => '',
            'sample_plugins' => '',
            'sample_snippets' => '',
            'sample_templates' => '',
            'sample_tvs' => ''

/*
            // TODO: export some items by name
            'chunks' => '',
            'plugins' => '',
            'snippets' => '',
            'templates' => '',
            'tvs' => ''
*/
        );
        
        $data['category_options'] = '';
        $categories = $this->modx->getCollection('modCategory');
        foreach ($categories as $c) {
            $is_selected = (isset($_POST['category']) && $_POST['category'] == $c->get('category')) ? ' selected="selected"' : '';
            $data['category_options'] .= '<option value="'.$c->get('id').$is_selected.'">'.$c->get('category').'</option>';
        }
        
        // Handle Submitted Form
		if (!empty($_POST)) {
            // Basics
            $data['namespace'] = preg_replace('/[^a-z0-9\_]/','',$this->modx->getOption('namespace', $_POST));
            $data['package_name'] = strip_tags($this->modx->getOption('package_name', $_POST));
            $data['description'] = strip_tags($this->modx->getOption('description', $_POST));
            $data['author_name'] = strip_tags($this->modx->getOption('author_name', $_POST));
            $data['author_email'] = strip_tags($this->modx->getOption('author_email', $_POST));
            $data['author_homepage'] = strip_tags($this->modx->getOption('author_homepage', $_POST));
            
            // Export Data?
            $data['category_id'] = (int) $this->modx->getOption('category_id', $_POST);
            $data['settings'] = trim($this->modx->getOption('settings', $_POST));
            
            // Sample Data?
            $data['sample_chunks'] = (int) $this->modx->getOption('sample_chunks', $_POST);
            $data['sample_plugins'] = (int) $this->modx->getOption('sample_plugins', $_POST);
            $data['sample_snippets'] = (int) $this->modx->getOption('sample_snippets', $_POST);
            $data['sample_templates'] = (int) $this->modx->getOption('sample_templates', $_POST);
            $data['sample_tvs'] = (int) $this->modx->getOption('sample_tvs', $_POST);
                    
            try {
                $Repoman = new Repoman($this->modx, $data);
                $Repoman->create($data['namespace'], $data);
                $pkg_root_dir = MODX_BASE_PATH.$this->modx->getOption('repoman.dir').$data['namespace'];
            
                // Export some sample data
                if ($data['category_id']) {
                    $data['where'] = array('category' => $data['category_id']);
                    $data['classname'] = 'modChunk';
                    $Repoman->export($pkg_root_dir, $data);
                    $data['classname'] = 'modPlugin';
                    $Repoman->export($pkg_root_dir, $data);
                    $data['classname'] = 'modSnippet';
                    $Repoman->export($pkg_root_dir, $data);
                    $data['classname'] = 'modTemplate';
                    $Repoman->export($pkg_root_dir, $data);
                    $data['classname'] = 'modTemplateVar';
                    $Repoman->export($pkg_root_dir, $data);
                }
                
                if (!empty($data['settings'])) {
                    $keys = explode(',',$data['settings']);
                    $data['where'] = array('key:IN' => $keys);
                    $data['classname'] = 'modSystemSetting';            
                    $Repoman->export($pkg_root_dir, $data);                
                }
                
                header('Location: '.$this->getUrl('home') ) ;
                exit;

            }  
            catch (Exception $e) {
    			$this->props['msg'] = $this->_get_msg($e->getMessage(),'error');                
            }
            
		} // end submitted form
		
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