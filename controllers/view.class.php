<?php
/**
 * The name of the controller is based on the action (home) and the
 * namespace. This home controller is loaded by default because of
 * our IndexManagerController.
 */
class RepomanViewManagerController extends RepomanManagerController {
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
		$this->props['pagetitle'] = 'Invalid Repo';		
		$repo = '';
		if (isset($_GET['repo'])) {
			$repo = $_GET['repo'];
		}

		if ($this->props['msg'] = $this->_invalid_repo_name($repo)) {
			return $this->_render();
		}
		$this->props['pagetitle'] = $repo;	

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
     * The pagetitle to put in the <title> attribute.
     * @return null|string
     */
    public function getPageTitle() {
        return 'Repoman';
    }
    
    /**
     * Register needed assets. Using this method, it will automagically
     * combine and compress them if that is enabled in system settings.
     */
    public function loadCustomCssJs() {
/*
        $this->addCss('url/to/some/css_file.css');
        $this->addJavascript('url/to/some/javascript.js');
        $this->addLastJavascript('url/to/some/javascript_load_last.js');
        $this->addHtml('<script type="text/javascript">
        Ext.onReady(function() {
            // We could run some javascript here
        });
        </script>');
*/
    }
}
/*EOF*/