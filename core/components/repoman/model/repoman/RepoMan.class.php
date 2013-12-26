<?php
/**
 * This class has some static methods for utility functions that can be used
 * before the class is instantiated.
 *
 *
 */

class Repoman {

	public $modx;
	
	public $config = array();

	// public $readme_filenames = array('README.md','readme.md');

    /**
     * Any tags to skip in the doc block, e.g. @param, that may have significance for PHPDoc and 
     * for general documentation, but which are not intended for RepoMan and do not describe
     * object attributes. Omit "@" from the attribute names.
     * See http://en.wikipedia.org/wiki/PHPDoc
     */
    public static $skip_tags = array('param','return','abstract','access','author','copyright',
        'deprecated','deprec','example','exception','global','ignore','internal','link','magic',
        'package','see','since','staticvar','subpackage','throws','todo','var','version'
    );


	// Events
/*
    public $log = array(
        'target'=>'FILE',
        'options' => array(
            'filename'=>'repoman.log'
        )
    );
*/
    public static $cache_opts = array();
    const CACHE_DIR = 'repoman';
        
	/**
	 *
	 * @param object MODX reference
	 */
	public function __construct($modx,$config=array()) {
		$this->modx = &$modx;
		$this->config = $config;
		self::$cache_opts = array(xPDO::OPT_CACHE_KEY => self::CACHE_DIR);
	}


	/**
	 * For creating Repoman's system settings (not for user created settings)
	 *
	 *     pkg_name.assets_path
	 *     pkg_name.assets_url
	 *     pkg_name.core_path
	 *
	 * @param string $name
	 */
	private function _create_setting($namespace, $key, $value) {
        if (empty($namespace)) {
            throw new Exception('namespace parameter cannot be empty.');
        }

		$N = $this->modx->getObject('modNamespace',$namespace);
		if (!$N) {
            throw new Exception('Invalid namespace ');
		}
	
		$Setting = $this->modx->getObject('modSystemSetting', array('key'=>$key));
		if (!$Setting) {
            $this->modx->log(modX::LOG_LEVEL_INFO, "Creating new System Setting: $key");
			$Setting = $this->modx->newObject('modSystemSetting');	
		}

		$Setting->set('key', $key);
		$Setting->set('value', $value);
		$Setting->set('xtype', 'textfield');
		$Setting->set('namespace', $namespace);
		$Setting->set('area', 'default');
		
		$Setting->save();
		$date = date('Y-m-d H:i:s');
		$this->modx->cacheManager->set('modSystemSetting/'.$key, $date, 0, self::$cache_opts);
		
		$this->modx->log(modX::LOG_LEVEL_INFO, "System Setting created/updated: $key");

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
		if (!is_dir($dir)) {
			$this->_log("Directory does not exist: $dir", 1, __LINE__);
			return;
		}
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
		
		// Translate folder names into object names (key) and then a SplFileInfo object (value), e.g.
		// 		modChunk => SplFileInfo Object
 		//            	(
        // 	           		[pathName:SplFileInfo:private] => /path/to/chunks/mychunk.html
        //             		[fileName:SplFileInfo:private] => mychunk.html
        //        		)
        // see http://php.net/manual/en/class.splfileinfo.php
		$objecttypes = array();
		foreach ($folders as $f) {
			if(isset($this->folder_object_map[$f])) {
				// Get the objects in the folder
				$o = $this->folder_object_map[$f];
				$objecttypes[$o] = array();
				$Parser = $o.'_parser';
				require_once('objecttypes/'.$Parser.'.php');
				$P = new $Parser();
				foreach (new RecursiveDirectoryIterator($dir.'/'.$f) as $filename) {
					if (!is_dir($filename)) {
						$exts = array_map('preg_quote', $P->extensions);
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
		// $this->_log('Object Types: ' . print_r($objecttypes,true), 3, __LINE__);

		// Loop through the object-types in the order they need to appear
		foreach ($this->object_order as $o) {
			if (!isset($objecttypes[$o])) {
				$this->_log("No objects of type $o found.  Skipping...", 4, __LINE__);
				continue;
			}
			
			$Parser = $o.'_parser';
			require_once('objecttypes/'.$Parser.'.php');
			
			$P = new $Parser();
			$exts = array_map('preg_quote', $P->extensions);
			$pattern = implode('|',$exts);
			
			// Loop through all objects (e.g. all modChunks)
			foreach ($objecttypes[$o] as $f) {
				$name = preg_replace('/('.$pattern.')$/i','', $f->getFilename());					
				//print file_get_contents($f->getRealPath()); exit;
				$atts = $P->get_attributes_from_dox(file_get_contents($f->getRealPath()));
				if ($atts === false) {
					$this->_log("No doc block found for $name", 1, __LINE__);
					continue;
				}
				// override name (some flexibility here due to inconsistent naming in MODX db)
				if (isset($atts['name'])) {
					$name = $atts['name']; 
				}
				elseif (isset($atts[$P->name_attribute])) {
					$name = $atts[$P->name_attribute];
				}
				
				// Create/update the object
				$O = $this->modx->getObject($o, $name);
				if (!$O) {
					$O = $this->modx->newObject($o);
					$O->set($P->name_attribute,$name);
				}
				foreach ($atts as $k => $v) {
					$O->set($k, $v);
				}
				// Make static. REMEMBER: static_file is relative to MODX base path!!
				$rel_path = preg_replace('#^'.MODX_BASE_PATH.'#','',$f->getRealPath());
				$O->set('static',1);
				$O->set('static_file', $rel_path);
				$O->set('source', 1); // Media source
				
				if (!$O->save()) {
					$this->_log("Problem saving $o $name", 1, __LINE__);
				}
				else {
					$this->_log("Updated $o $name", 3, __LINE__);
				}
			}
		}
	}
	
	/**
	 * Create/Update the namespace
	 * @param string $package_name (lowercase)
	 * @param string $path to the repo
	 */
	private function _create_namespace($name, $path) {
        if (empty($name)) {
            throw new Exception('namespace parameter cannot be empty.');
        }
        if (preg_match('/[^a-z0-9_\-]/', $name)) {
            throw new Exception('Invalid namespace :'.$name);
        }
		$N = $this->modx->getObject('modNamespace',$name);
		if (!$N) {
			$N = $this->modx->newObject('modNamespace');
			$N->set('name', $name);
		}
		$N->set('path', $path);
		$N->set('assets_path',$path.'/assets/components/'.$name.'/');
		$N->save();

		// Prepare Cache folder for tracking object creation
		self::$cache_opts = array(xPDO::OPT_CACHE_KEY => self::CACHE_DIR.'/'.$name);

		//$this->modx->cacheManager->set('tags/'.$info['hash'], $tag, 0, self::$cache_opts);
		$this->modx->log(modX::LOG_LEVEL_INFO, "Namespace created/updated: $name");
	}

	/**
	 * Our config getter
	 * @param string $key
	 * @return mixed
	 */
	public function get($key) {
	   return (isset($this->config[$key])) ? $this->config[$key] : null;
	}
	
	/**
	 * Get the readme file from a repo
	 *
	 * @param string $repo_path full path to file, without trailing slash
	 * @return string (contents of README.md file) or false if not found
	 */
	public function get_readme($repo_path) {
		foreach ($this->readme_filenames as $f) {
			$readme = $repo_path.'/'.$f;
			if (file_exists($readme)) {
				return file_get_contents($readme);
			}
		}
		return false;
	}
	
	
	/** 
	 * Get configuration for a given package path.
	 * This reads the config.php (if present), and merges it with global config
	 * settings.
	 *
     * @param string $pkg_path
     * @param array $overrides any run-time overrides
	 * @return array
	 */
	public static function load_config($pkg_path, $overrides=array()) {
	
        $global = include dirname(__FILE__).'/global.config.php';
        $config = array();
        if (file_exists($pkg_path.'/config.php')) {
            $config = include $pkg_path.'/config.php';
            if (!is_array($config)) {    
                $config = array();
            }
        }
        
        return array_merge($global, $config, $overrides);
	}
	
	//------------------------------------------------------------------------------
	//! Public
	//------------------------------------------------------------------------------

    /**
     * Goal here is to generate an array that can be passed as filter criteria to
     * getObject so that we can identify and load existing objects (instead of 
     * haphazardly creating new objects all the time). In practice, we don't 
     * always use the primary key to load an object (e.g. we don't specify the pk in 
     * the package's repository) so for each classname, we need a field (or fields)
     * to consider when searching for existing records.  E.g. for modSnippet or modChunk, 
     * we want to look only at the name, but for modResource we might look at context and uri.
     *
     * @param string $classname
     * @param array $array data for a single object representing $classname
     * @return array
     */
    function get_criteria($classname, $array) {
        $fields = array();
        switch ($classname) {
            case 'modMenu':
                $fields = array('text');
                break;
            case 'modAction':
                $fields = array('namespace','controller');
                break;
            case 'modContentType':
            case 'modDashboard':
            case 'modUserGroup':
            case 'modUserGroupRole':
            case 'modPropertySet':
            case 'modTemplateVar':
            case 'modSnippet':
            case 'modChunk':
            case 'modPlugin':
                $fields = array('name');
                break;
            case 'modTemplate':
                $fields = array('templatename');
                break;
            case 'modUser':
                $fields = array('username');
                break;
            case 'modContext':
            case 'modSystemSetting':
                $fields = array('key');
                break;
            case 'modResource':
                $fields = array('uri','context_key');
                break;
            case 'modCategory':
                $fields = array('category');
                break;
            case 'modDashboardWidget':
                $fields = array('name','namespace');
                break;
        }
        
        $criteria = array();
        foreach ($fields as $f) {
            if (isset($array[$f]) && !empty($array[$f])) {
                $criteria[$f] = $array[$f];
            }
        }
        
        return $criteria;
    }


    /** 
     * Unified build script.
     *
     */
    public function build($pkg_dir, $args) {
    
    }
    
	

    
    /** 
     * Import pkg elements into MODX
     *
     */
    public function import($pkg_dir) {
       
        $pkg_dir = self::getdir($pkg_dir);
        
        self::_create_namespace($this->get('namespace'),$pkg_dir);

        // Settings
        $key = $this->get('namespace') .'.assets_url';
        $rel_path = str_replace(MODX_BASE_PATH,'',$pkg_dir); // convert path to url
        $assets_url = MODX_BASE_URL.$rel_path .'/assets/';
        self::_create_setting($this->get('namespace'), $this->get('namespace').'.assets_url', $assets_url);
        self::_create_setting($this->get('namespace'), $this->get('namespace').'.assets_path', $pkg_dir.'/assets/');
        self::_create_setting($this->get('namespace'), $this->get('namespace').'.core_path', $pkg_dir .'/core/');        
     
        // The gratis Category
        $Category = $this->modx->getObject('modCategory', array('category'=>$this->get('category')));
        if (!$Category) {
            $Category = $this->modx->newObject('modCategory');
            $Category->set('category', $this->get('package_name'));
            $Category->save();
            $this->modx->log(modX::LOG_LEVEL_INFO, "Category created: ".$this->get('category'));
        }
        
        // Import Elements
        
    }

    /**
     * Install all elements and 
     *
     *
     */
    public function install($pkg_dir) {
        $pkg_dir = self::getdir($pkg_dir);
        //throw new Exception('Invalid something...');
        self::import($pkg_dir);
        self::migrate($pgk_dir);
    }

    public function migrate($pkg_dir) {
    
    }

	/**
	 * Shows manual page for a given $function.
	 *
	 * @param string $function
	 * @return string
	 */
	public static function rtfm($function) {
        $doc = dirname(dirname(dirname(__FILE__))).'/docs/'.basename($function).'.txt';
        if (file_exists($doc)) {
            return file_get_contents($doc) . "\n\n";
        }
        return "No manual page found.\n";
	}

    public function seed($pkg_dir) {
    
    }
        
    public function uninstall() {
    
    }

    /**
     * Given an absolute path, e.g. /home/user/public_html/assets/file.php
     * return the file path relative to the MODX base path, e.g. assets/file.php
     * @param string $path
     * @param string $base : the /full/path/to/base/ (MODX_BASE_PATH)
     * @return string
     */
    public function path_to_rel($path,$base) {
        return str_replace($base,'',$path); // convert path to url
    }
        	
    /**
     * Read parameters out of a (PHP) docblock... like a repoman repossessing 
     * outstanding leased objects.
     *
     * @param string $string the unparsed contents of a file
     * @param string $dox_start string designating the start of a doc block
     * @param string $dox_start string designating the start of a doc block 
     * @return array on success | false on no doc block found
     */
    public static function repossess($string,$dox_start='/*',$dox_end='*/') {
        
        $dox_start = preg_quote($dox_start,'#');
        $dox_end = preg_quote($dox_end,'#');
    
        preg_match("#$dox_start(.*)$dox_end#msU", $string, $matches);
    
        if (!isset($matches[1])) {
                return false; // No doc block found!
        }
        
        // Get the docblock                
        $dox = $matches[1];
        
        // Loop over each line in the comment block
        $a = array(); // attributes
        foreach(preg_split('/((\r?\n)|(\r\n?))/', $dox) as $line){
            preg_match('/^\s*\**\s*@(\w+)(.*)$/',$line,$m);
            if (isset($m[1]) && isset($m[2]) && !in_array($m[1], self::$skip_tags)) {
                    $a[$m[1]] = trim($m[2]);
            }
        }
        
        return $a;
    }
	
	/**
	 * Verify a directory, converting for any OS variants and convert
	 * any relative paths to absolute . 
	 *
	 * @param string $path path (or relative path) to package
	 * @return string full path without trailing slash
	 */
	public static function getdir($path) {
        $path = strtr(realpath($path), '\\', '/');
        if (!file_exists($path)){
            throw new Exception('Directory does not exist: '.$path);
        }
        elseif(!is_dir($path)) {
            throw new Exception('Path is not a directory: '.$path,'ERROR');
        }
        return $path;
	}
	
}
/*EOF*/