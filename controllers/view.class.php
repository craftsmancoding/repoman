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
		$this->props['pagetitle'] = 'View Repo';	

        $repo = $this->modx->getOption('repo', $scriptProperties);
		$info = $this->get_info($repo);
		$this->props['content'] = $this->_load('page_repo', 
			array(
                'info' => $this->get_info($repo),
				'readme'=>$this->get_readme($repo), 
				'index_url' => $this->getUrl('home'), 
			)
		);
		
		return $this->_render();

    }
    
    /**
     * The pagetitle to put in the <title> attribute.
     * @return null|string
     */
    public function getPageTitle() {
        return 'View Repo';
    }

}
/*EOF*/