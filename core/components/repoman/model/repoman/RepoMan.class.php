<?php
/**
 *
 *
 *
 */
require_once('RepoMan_parser.php');
class RepoMan {

	// TODO: make these configurable?
	public $folder_object_map = array(
		'chunks' => 'modChunk',
		'chunk' => 'modChunk',
		'modChunk' => 'modChunk',
		
		'snippets' => 'modSnippet',
		'plugins' => 'modPlugin'
	);

	public $readme_filenames = array('README.md','readme.md');

	// 1st must come objects with no foreign keys.  All of an object's dependencies must appear before
	// the object appears, similar to the order of CREATE TABLES in an InnoDB database.
	public $object_order = array(
		'modPropertySet',
		'modElementPropertySet',
		'modCategory', 
		'modChunk',
		'modSnippet',
		'modPlugin'
	);
	
	public $modx;

	// Events
	public $log = array();
	// 1 = show only errors. 2 = show errors and warnings, 3 = show everything 
	public $verbosity = 3;
	/**
	 *
	 * @param object MODX reference
	 */
	public function __construct($modx) {
		$this->modx = &$modx;
	}

	/**
	 * Logging function.
	 *
	 * @param string $msg to be logged
	 * @param integer $level 1 = Error, 2 = Warn, 3 = Info
	 * @param integer $line number
	 */
	private function _log($msg, $level=1, $line='???') {
		if ($this->verbosity >= $level) {
			$this->log[] = $msg . " [LINE $line]";
		}
	}

	/**
	 * Sets the package.assets_path setting (if not set already)
	 *
	 */
	private function _set_assets_path($package_name, $path) {
	 	$key = $package_name .'.assets_path';
	 	$assets_path = $path .'/assets/components/'.$package_name.'/';
		$Setting = $this->modx->getObject('modSystemSetting', $key);
		if (!$Setting) {
			$Setting = $this->modx->newObject('modSystemSetting');	
		}

		$Setting = $this->modx->newObject('modSystemSetting');
		$Setting->set('key', $key);
		$Setting->set('value', $assets_path);
		$Setting->set('xtype', 'textfield');
		$Setting->set('namespace', $package_name);
		$Setting->set('area', 'default');
		
		$Setting->save();
		
		$this->_log("$key set to $assets_path", 3, __LINE__);
		
		return $assets_path;
	}
			
	/**
	 * Sets the package.assets_url setting (if not set already)
	 *
	 */
	private function _set_assets_url($package_name, $path) {
	 	$key = $package_name .'.assets_url';

	 	// Strip out the path to find the relative path
	 	$rel_path = preg_replace('#^'.MODX_BASE_PATH.'#','',$path);	 	
	 	$assets_url = MODX_BASE_URL.$rel_path .'/assets/components/'.$package_name.'/';
	 	
		$Setting = $this->modx->getObject('modSystemSetting', $key);
		if (!$Setting) {
			$Setting = $this->modx->newObject('modSystemSetting');	
		}

		$Setting = $this->modx->newObject('modSystemSetting');
		$Setting->set('key', $key);
		$Setting->set('value', $assets_url);
		$Setting->set('xtype', 'textfield');
		$Setting->set('namespace', $package_name);
		$Setting->set('area', 'default');
		
		$Setting->save();
		$this->_log("$key set to $assets_url", 3, __LINE__);
		
		return $assets_url;
	}

	/**
	 * Sets the package.base_url setting (if not set already)
	 *
	 */	
	private function _set_base_path($package_name,$path) {
	 	$key = $package_name .'.base_path';
	 	$base_path = $path .'/core/components/'.$package_name.'/';
		$Setting = $this->modx->getObject('modSystemSetting', $key);
		if (!$Setting) {
			$Setting = $this->modx->newObject('modSystemSetting');	
		}

		$Setting = $this->modx->newObject('modSystemSetting');
		$Setting->set('key', $key);
		$Setting->set('value', $base_path);
		$Setting->set('xtype', 'textfield');
		$Setting->set('namespace', $package_name);
		$Setting->set('area', 'default');
		
		$Setting->save();	
		
		$this->_log("$key set to $base_path", 3, __LINE__);
		
		return $base_path;
	}

	/**
	 * Scan the elements directory for object types... create the elements (Chunks, Snippets, etc.).
	 * The order is important!  Objects with no foreign keys must be created first 
	 * (e.g. Categories before Templates).
	 * @param string $dir containing element-folders, e.g. /path/to/core/components/my_pkg/elements
	 */
	private function _create_elements($dir) {
			
		// Which object directories are available?
		$folders = array();
		//foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $filename) {
		foreach (new RecursiveDirectoryIterator($dir) as $filename) {
			if (is_dir($filename)) {
				$shortname = preg_replace('#^'.$dir.'/#','',$filename);
				if ($shortname != '.' && $shortname != '..') {
					$folders[] = $shortname;
				}
			}	
		}
		
		// Sort?
		// Translate folder names into object names
		$objecttypes = array();
		foreach ($folders as $f) {
			if(isset($this->folder_object_map[$f])) {
				// Get the objects in the folder
				$o = $this->folder_object_map[$f];
				$objecttypes[$o] = array();
				$Parser = $o.'_parser';
				require_once('objecttypes/'.$Parser.'.php');
				foreach (new RecursiveDirectoryIterator($dir.'/'.$f) as $filename) {
					if (!is_dir($filename)) {
						$exts = array_map('preg_quote', $Parser->$extensions);
						$pattern = implode('|',$exts);
						if (preg_match('/('.$pattern.')$/i',$filename)) {							
							$objecttypes[$this->folder_object_map[$f]][] = $filename;
						}	
					}
				}
			}
			else {
				$this->_log("Unknown element type: $f", 2, __LINE__);
			}
		}
		$this->_log('Object Types: ' . print_r($objecttypes,true), 3, __LINE__);

		// Loop through the objects in the order they need to appear
		foreach ($this->object_order as $o) {
			if (isset($objecttypes[$o])) {
				$Parser = $o.'_parser';
				require_once('objecttypes/'.$Parser.'.php');
				
				$P = new $Parser();
				$file = file_get_contents($objecttypes[$o]);
				$P->get_attributes_from_dox($file);
			}
		}
	}
	
	/**
	 * Create/Update the namespace
	 * @param string $package_name
	 * @param string $path to the repo
	 */
	private function _create_namespace($package_name, $path) {
		$N = $this->modx->getObject('modNamespace',$package_name);
		if (!$N) {
			$N = $this->modx->newObject('modNamespace');
			$N->set('name', $package_name);
		}
		$N->set('path', $path);
		$N->set('assets_path',$path.'/assets/components/'.$package_name.'/');
		$N->save();
		
		$this->_log("Namespace $package_name created.", 3, __LINE__);
	}

	
	/**
	 * Get the readme file from a repo
	 *
	 * @param string $repo_path full path to file, without trailing slash
	 * @return string (contents of README.md file) or false if not found
	 */
	public function get_readme($repo_path) {
		return false;
		foreach ($this->readme_filenames as $f) {
			$readme = $repo_path.'/'.$f;
			if (file_exists($readme)) {
				return file_get_contents($readme);
			}
		}
		return false;
	}
	
	
	/**
	 * This is the raison d'etre of the entire package: this scans a local
	 * working copy (i.e. a local repository) for objects, then pushes them
	 * into MODX, creating or updating objects as required, creating static
	 * uncached elements whenever possible so that your local repo is the 
	 * master copy of your package.
	 *
	 * Important here is the order: certain objects must be created first, e.g.
	 * a category must exist before you can add an object to it.
	 *
	 * 1. Register namespace (if not already registered). Remember: {base_path}, {core_path}, {assets_path} are oddities. No other settings are supported.
	 * e.g. {assets_path}components/repoman/core/components/repoman/
	 * 2. Create System settings (if not already created): pkg.core_path, pkg.asset_path, pkg.asset_url
	 * 3. Scan directory for objects: for each, create or update
	 
	 * @param string $repo_path including package name (omit trailing slash)
	 * @return ???
	 */
	public function sync_modx($repo_path) {

		$namespace = basename($repo_path); // same as the package's short name
		
		$this->_create_namespace($namespace, $repo_path);
		$this->_set_assets_path($namespace, $repo_path);
		$this->_set_assets_url($namespace, $repo_path);
		$this->_set_base_path($namespace, $repo_path);
		
		$this->modx->cacheManager-> refresh(array('system_settings' => array()));
			
		// TODO: read from config?
		$dir = $repo_path .'/core/components/'.$namespace.'/elements';
		
		$this->_create_elements($dir);
		
		// create resources?
		// create ??? uh... resolvers???
		// Which object directories are available?
		
		return $this->log;
	}
}
/*EOF*/