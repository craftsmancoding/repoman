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
    /**
     * Any specific processing we want to do here. Return a string of html.
     * @param array $scriptProperties
     */
    public function process(array $scriptProperties = array()) {
		$this->props['pagetitle'] = 'Overview';
		$pagedata = array('repo_dir_settings'=>'', 'cache_settings'=>'','repos'=>'');
		$props = array();		
		if (!empty($_POST)) {
			//print_r($_POST); exit;
			$Setting = $this->modx->getObject('modSystemSetting', 'repoman.repo_dir');
			$Setting->set('value', $this->modx->getOption('repoman_repo_dir',$_POST,''));
			$this->repo_dir = $this->modx->getOption('repoman_repo_dir',$_POST,''); // for the rest of this request
			$Setting->save();
				
			$Setting = $this->modx->getObject('modSystemSetting', 'cache_scripts');
			$Setting->set('value', $this->modx->getOption('cache_scripts',$_POST));
			$Setting->save();
			$Setting = $this->modx->getObject('modSystemSetting', 'cache_resource');
			$Setting->set('value', $this->modx->getOption('cache_resource',$_POST));
			$Setting->save();
		
			$this->modx->cacheManager->refresh(array('system_settings' => array()));
		}
		
		/*
		----------------
		Repo Dir Setting
		----------------
		*/
		$props['repo_dir'] = $this->repo_dir;
		$props['options'] = $this->_get_dir_options($props['repo_dir']);
		$pagedata['repo_dir_settings'] = $this->_load('selector_repo_dir', $props);
		
		/*
		-----------------------------------
		Caching System Settings
		-----------------------------------
		cache_scripts  : set to No to ensure that you always read the PHP from file
		cache_resource : set to No to ensure that Chunks etc. are always read from file
		*/
		$props['cache_scripts'] = $this->modx->getOption('cache_scripts');
		$props['cache_resource'] = $this->modx->getOption('cache_resource');
		$pagedata['cache_settings'] = $this->_load('settings', $props);
		
		
		// Validate the setting
/*
		if (empty($this->repo_dir)) {
			$this->props['msg'] = $this->_get_msg('Please set the repoman.repo_dir directory.','error');
			return $this->_render();
		}
*/
		if (!empty($this->repo_dir) && !file_exists($this->repo_dir)) {
			$this->props['msg'] = $this->_get_msg($this->repo_dir .' does not exist!','error');
		}
		elseif (!empty($this->repo_dir) && !is_dir($this->repo_dir)) {
			$this->props['msg'] = $this->_get_msg($this->repo_dir .' must be a directory!','error');
		}
		elseif (!empty($this->repo_dir)) {
			$this->repo_dir = preg_replace('#'.DIRECTORY_SEPARATOR.'$#','',$this->repo_dir); // strip trailing slash
			
			$repos = '';
			$i = 0;
			//foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($repo_dir)) as $filename) {
			foreach (new RecursiveDirectoryIterator($this->repo_dir) as $filename) {
				if (is_dir($filename)) {
					$shortname = preg_replace('#^'.$this->repo_dir.'/#','',$filename);
					if ($shortname != '.' && $shortname != '..') {
						$i++;
						$class = 'repoman_odd';
						if ($i % 2 == 0) {
							$class = 'repoman_even';	
						}
						$repos .= $this->_load('tr_repo'
							, array(
								'sync_link'=>REPOMAN_MGR_URL .'&action=sync&repo='.$shortname,
								'view_link'=>REPOMAN_MGR_URL .'&action=view&repo='.$shortname,
								'repo'=>$shortname,
								'class'=>$class
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