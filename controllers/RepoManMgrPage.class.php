<?php
/**
 * Handles drawing of RepoMan's CMP pages in the MODX manager.
 *
 *
 */
class RepoManMgrPage {

	public $modx;
	public $repoman;
	
	public $action = 'index';
	public $repo_dir = '';
	
	// Page properties (like placeholders) used on manager pages
	public $props = array(
		'pagetitle' => '',
		'msg' => '',
		'content' => '',
	);


	//------------------------------------------------------------------------------
	//! MAGIC
	//------------------------------------------------------------------------------
	/** 
	 * Manager page air-traffic control
	 */
	public function __call($method, $args) {
	
		if (!method_exists($this, $method)) {
			return $this->show404();
		}
		
		$this->$method();
	}

	/**
	 * Some general gatekeeping
	 */
	public function __construct($modx) {
		$this->modx = &$modx;
		$this->repoman = new RepoMan($modx);
		
		$this->props['pagetitle'] = 'Repo Man';
		
		$this->repo_dir = $modx->getOption('repoman.repo_dir');
		$this->repo_dir = preg_replace('#'.DIRECTORY_SEPARATOR.'$#','',$this->repo_dir); // strip trailing slash

/*
		if (empty($this->repo_dir)) {
			$this->props['msg'] = $this->_get_msg('Please set the repoman.repo_dir directory.','error');
			return $this->_render();
		}
		if (!file_exists($this->repo_dir)) {
			$this->props['msg'] = $this->_get_msg($this->repo_dir .' does not exist!','error');
			return $this->_render();
		}
		if (!is_dir($this->repo_dir)) {
			$this->props['msg'] = $this->_get_msg($this->repo_dir .' must be a directory!','error');
			return $this->_render();
		}
		
		$this->repo_dir = preg_replace('#'.DIRECTORY_SEPARATOR.'$#','',$this->repo_dir); // strip trailing slash
*/
		
	}
	
	//------------------------------------------------------------------------------
	//! PRIVATE	
	//------------------------------------------------------------------------------

	/**
	 * Get dirs inside the web root, viable for use as repo containers.
	 * MODX_PROCESSORS_PATH
	 */
	private function _get_dir_options($current_val) {
		
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
	private function _get_msg($msg, $type='error') {
		return $this->_load($type, array('msg'=>$msg));
	}
	
	/**
	 * Loads a controller file.
	 * @param	string	$file
	 */
	private function _load($file, $data=array()) {
		extract($data);
		ob_start();
		include(REPOMAN_PATH.'views/'.$file.'.php');
		return ob_get_clean();
	}
	
	/** 
	 * Relies on $this->props
	 */
	private function _render() {
		
		extract($this->props);
		
		ob_start();
		include(REPOMAN_PATH.'views/templates/mgr_page.php');
		return ob_get_clean();
	}

	/**
	 * If there's a problem with the repo name, this will return it.
	 *
	 * @param string 
	 * @return string error msg on problem|false
	 */
	private function _invalid_repo_name($repo) {
		if (preg_match('/[^0-9a-z_\-]/i', $repo)) {
			return $this->_get_msg('Invalid repo name. <a href="'.REPOMAN_MGR_URL.'">Back</a>','error');
		}
		if (!file_exists($this->repo_dir.'/'.$repo)) {
			return $this->_get_msg($this->repo_dir.'/'.$repo .' does not exist!','error');
		}
		if (!is_dir($this->repo_dir.'/'.$repo)) {
			return $this->_get_msg($this->repo_dir .'/'.$repo. ' must be a directory!','error');
		}	
		
		return false; // no problem
	}

	//------------------------------------------------------------------------------
	//! PUBLIC PAGES
	//------------------------------------------------------------------------------
	
	/**
	 * Default controller
	 */
	public function index() {
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
	
	
	
	public function show404() {
		$this->props['pagetitle'] = 'Invalid Action';
		$this->props['msg'] = $this->_get_msg('The action you are requesting could not be found. <a href="'.REPOMAN_MGR_URL.'">Back</a>','error');
		return $this->_render();
	}

	/** 
	 * View a single repo
	 */
	public function view() {
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
	 * Sync a repo: read data from the filesystem and make sure MODX is up to date.
	 *
	 *
	 */
	public function sync() {

		$this->props['pagetitle'] = 'Sync MODX With Repo';		
		$repo = '';
		if (isset($_GET['repo'])) {
			$repo = $_GET['repo'];
		}
	
		if ($this->props['msg'] = $this->_invalid_repo_name($repo)) {
			return $this->_render();
		}

		$this->props['content'] = 'Syncing...';
		
		//foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($repo_dir)) as $filename) {
		$log = $this->repoman->sync_modx($this->repo_dir.'/'.$repo);
		
		$this->props['content'] .= '<pre>';
		$this->props['content'] .= print_r($log,true);
		$this->props['content'] .= '</pre>';

		$this->props['content'] = $this->_load('page_sync', 
			array(
				'log'=> '<pre>'.print_r($log,true).'</pre>',
				'index_url' => REPOMAN_MGR_URL, 
				'view_url' => REPOMAN_MGR_URL .'&action=view&repo='.$repo, 
			)
		);

		
		return $this->_render();	
	}

}
/*EOF*/