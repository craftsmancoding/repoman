<?php
/**
 * The name of the controller is based on the action (home) and the
 * namespace. This home controller is loaded by default because of
 * our IndexManagerController.
 */
class RepomanHomeManagerController extends RepomanManagerController {
    /** @var bool Set to false to prevent loading of the header HTML. */
    public $loadHeader = true;
    /** @var bool Set to false to prevent loading of the footer HTML. */
    public $loadFooter = true;
    /** @var bool Set to false to prevent loading of the base MODExt JS classes. */
    public $loadBaseJavascript = true;
    
    public $props = array();
    
    /**
     * Any specific processing we want to do here. Return a string of html.
     * @param array $scriptProperties
     */
    public function process(array $scriptProperties = array()) {

		$this->props['pagetitle'] = 'Overview';
		$pagedata = array('repo_dir_settings'=>'', 'cache_settings'=>'','repos'=>'');
		$props = array();		
		if (!empty($_POST)) {

			if (!$Setting = $this->modx->getObject('modSystemSetting', array('key' => 'repoman.dir'))) {
                $Setting = $this->modx->newObject('modSystemSetting');
                $Setting->set('key','repoman.dir');
			}
			$Setting->set('value', $this->modx->getOption('repoman_dir',$_POST,''));
			$Setting->save();
			$this->modx->setOption('repoman.dir', $this->modx->getOption('repoman_dir',$_POST,''));
			$this->modx->cacheManager->refresh(array('system_settings' => array()));
		}
		
		/*
		----------------
		Repo Dir Setting
		----------------
		*/
		
        $this->props['class'] = 'repoman_success';

        $repo_dir = $this->modx->getOption('repoman.dir');
        if (empty($repo_dir)) {
            $this->props['class'] = 'repoman_error';
			$this->props['msg'] = $this->_get_msg('Set your Repoman directory (relative to your MODx base path) so Repoman will know where to find your local repositories.','error');        
        }        
		elseif (!file_exists(MODX_BASE_PATH.$repo_dir)) {
            $this->props['class'] = 'repoman_error';
			$this->props['msg'] = $this->_get_msg($repo_dir .' does not exist!','error');
		}
		elseif (!is_dir(MODX_BASE_PATH.$repo_dir)) {
            $this->props['class'] = 'repoman_error';
			$this->props['msg'] = $this->_get_msg($repo_dir .' must be a directory!','error');
		}
		elseif ($repo_dir == MODX_CONNECTORS_URL || $repo_dir == MODX_MANAGER_URL || $repo_dir == 'core/') {
            $this->props['class'] = 'repoman_error';
			$this->props['msg'] = $this->_get_msg($repo_dir .' cannot be one of the built-in MODX directories.','error');
        }
        else {		
			$repos = '';
			$i = 0;
			foreach (new RecursiveDirectoryIterator(MODX_BASE_PATH.$repo_dir) as $filename) {
				if (is_dir($filename)) {
					$shortname = basename($filename);
					if ($shortname != '.' && $shortname != '..') {
						$i++;
						$class = 'repoman_odd';
						if ($i % 2 == 0) {
							$class = 'repoman_even';	
						}
						
						$config = Repoman::load_config($filename);

						$repos .= $this->_load('tr_repo'
							, array(
								'install_link'=>$this->getUrl('install', array('repo'=>$shortname)),
								'view_link'=>$this->getUrl('view',array('repo'=>$shortname)),
								'package_name'=>$config['package_name'],
								'description'=>$config['description'],
								'class'=>$class,
								'namespace' => $config['namespace']
							)
						);
					}
				}	
			}
		}
		
		
		// Repos
		if (empty($repos)) {
			$pagedata['repos'] = $this->_get_msg('You do not have any repos in your repo directory yet.','warning');
		}
		else {
			$pagedata['repos'] .= $this->_load('table', array('content' =>$repos,'class'=>'repos'));
		}
		
		
		$this->props['content'] = $this->_load('page_index', $pagedata);
		
		return $this->_render();

    }
    
    /**
     * The pagetitle to put in the <title> attribute.
     * @return null|string
     */
    public function getPageTitle() {
        return 'Repoman ';
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