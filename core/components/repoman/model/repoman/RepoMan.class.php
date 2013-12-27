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

    // Used to provide transparency
    public static $queue = array();
	// public $readme_filenames = array('README.md','readme.md');

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
		
		Repoman::$queue[] = 'modSystemSetting: '.$key;		
		if (!$this->get('dry_run')) {
            $Setting->save();
    		$data = self::get_criteria('modSystemSetting', $Setting->toArray());
    		$this->modx->log(modX::LOG_LEVEL_INFO, "System Setting created/updated: $key");
        }
	}
			
	/**
	 * Get an array of elements for the given $objecttype
	 *
	 * @param string $objecttype
	 * @param string $pkg_dir the repo location, e.g. /home/usr/public_html/assets/repos/mypkg
	 * @return array of objects of type $objecttype
	 */
	private function _get_elements($objecttype,$pkg_dir) {
        
        require_once dirname(__FILE__).'/repoman_parser.class.php';
        require_once dirname(__FILE__).'/objecttypes/'.strtolower($objecttype).'_parser.class.php';
        
        $classname = $objecttype.'_parser';
        $Parser = new $classname($this->modx,$this->config);
        
        return $Parser->gather($pkg_dir);
	}
	
	/**
	 * Slurp up an object and track down its relations
	 *
	 * 
	 * [ContextSetting] => Array
        (
            [class] => modContextSetting
            [local] => key
            [foreign] => key
            [cardinality] => one
            [owner] => local
        )
        
	 * @param string $classname
	 * @param array $objectdata
	 * @return object
	 */
    private function _get_object($classname,$objectdata) {
        $Object = $this->modx->getObject($classname, $this->get_criteria($classname, $objectdata));
        if (!$Object) {
            $Object = $this->modx->newObject($classname);
        }
        
        // Mass assignment $Object->fromArray() does not work: some fields are blocked
        foreach ($objectdata as $k => $v) {
            $Object->set($k, $v);
        }

        $related = array_merge($this->modx->getAggregates($classname), $this->modx->getComposites($classname));
        foreach ($related as $rclass => $def) {
            if (isset($objectdata[$rclass])) {
            // && $def['owner'] == 'local'
                $rel_data = $objectdata[$rclass];
                if (!is_array($rel_data)) throw new Exception('Data in '.$classname.'['.$rclass.'] not an array.');
                if ($def['cardinality'] == 'one') {
                    //if (!isset($rel_data[ $def['foreign'] ])) throw new Exception('Missing key "'.$def['foreign'].'" in '.$classname.'['.$rclass.']');
                    $one = $this->_get_object($def['class'],$rel_data); // Avoid E_STRICT notices
                    $Object->addOne($one);
                }
                else {
                    if (!isset($rel_data[0])) {
                        $rel_data = array($rel_data);
                    }
                    $many = array();
                    foreach ($rel_data as $r) {
                       // if (!isset($r[ $def['foreign'] ])) throw new Exception('Missing key "'.$def['foreign'].'" in '.$classname.'['.$rclass.']');
                        $many[] = $this->_get_object($def['class'],$r);   
                    }
                    $Object->addMany($many);
                }
            }
        }
        
        return $Object;
    }
    
	/**
	 * Iterate over the objects directory to load up all non-element objects
	 *
	 * @param string $pkg_dir
	 */
	private function _get_objects($pkg_dir) {
        $dir = $pkg_dir.'/core/components/'.$this->get('namespace').'/'.$this->get('objects_dir');
        if (!file_exists($dir) || !is_dir($dir)) {
            $this->modx->log(modX::LOG_LEVEL_INFO,'No object directory detected at '.$dir);
            return;
        }
        $this->modx->log(modX::LOG_LEVEL_INFO,'Crawling object directory '.$dir);

        $files = glob($dir.'/*.php');

        foreach($files as $f) {
            $classname = basename($f,'.php');
            $fields = $this->modx->getFields($classname);
            if (empty($fields)) throw new Exception('Unrecognized object classname: '.$classname);
            $data = include $f;
            if (!is_array($data)) throw new Exception('Data in '.$f.' not an array.');
            if (!isset($data[0])) {
                $data = array($data);
            }
            
            $i = 0;
            foreach ($data as $objectdata) {
                $Object = $this->_get_object($classname,$objectdata);
                $data = self::get_criteria($classname, $Object->toArray());
                Repoman::$queue[] = $classname.': '.implode('-',$data);
                if(!$this->get('dry_run')) {
                    if ($Object->save()) {
                        $this->modx->log(modX::LOG_LEVEL_INFO,'Saved '.$classname);
                        
                        $this->modx->cacheManager->set($classname.'/'.$i, $data, 0, self::$cache_opts);
                        $i++;
                    }
                    else {
                        throw new Exception('Error saving '.$classname);
                    }
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
		$N->set('path', $path.'/core/components/'.$name.'/');
		$N->set('assets_path',$path.'/assets/components/'.$name.'/');
		
		Repoman::$queue[] = 'Namespace: '.$name."\n"
		  ."       core_path:   ".$N->get('path')."\n"
		  ."       assets_path: ".$N->get('assets_path');

        if (!$this->get('dry_run')) {
    		$N->save();
    		// Prepare Cache folder for tracking object creation
    		self::$cache_opts = array(xPDO::OPT_CACHE_KEY => self::CACHE_DIR.'/'.$name);
    		$data = Repoman::get_criteria('modNamespace',$N->toArray());
            $this->modx->cacheManager->set('modNamespace/'.$N->get('name'), $data, 0, Repoman::$cache_opts);
    		$this->modx->log(modX::LOG_LEVEL_INFO, "Namespace created/updated: $name");
        }
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
            if (isset($config['package_name']) && !isset($config['category'])) {
                $config['category'] = $config['package_name'];
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
    public static function get_criteria($classname, $array) {
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
            case 'modNamespace':
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
        $this->config['is_build'] = true; 
        
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
            $this->modx->log(modX::LOG_LEVEL_INFO, "Creating new category: ".$this->get('category'));
            $Category = $this->modx->newObject('modCategory');
            $Category->set('category', $this->get('category'));
        }
        else {
            $this->modx->log(modX::LOG_LEVEL_INFO, "Using existing category: ".$this->get('category'));        
        }
        
        // Import Elements
        $chunks = self::_get_elements('modChunk',$pkg_dir);
        $plugins = self::_get_elements('modPlugin',$pkg_dir);
        $snippets = self::_get_elements('modSnippet',$pkg_dir);
        $templates = self::_get_elements('modTemplate',$pkg_dir);
        $tvs = self::_get_elements('modTemplateVar',$pkg_dir);
        
        if ($chunks) $Category->addMany($chunks);
        if ($plugins) $Category->addMany($plugins);
        if ($snippets) $Category->addMany($snippets);
        if ($templates) $Category->addMany($templates);
        if ($tvs) $Category->addMany($tvs);
        
        if (!$this->get('dry_run') && $Category->save()) {
            $data = self::get_criteria('modCategory', $Category->toArray());
    		$this->modx->cacheManager->set('modCategory/'.$this->get('category'), $data, 0, self::$cache_opts);
            $this->modx->log(modX::LOG_LEVEL_INFO, "Category created/updated: ".$this->get('category'));
        }

        // Regular Objects        
        self::_get_objects($pkg_dir);
 
        if ($this->get('dry_run')) {
            $msg = "\n==================================\n";
            $msg .= "    Dry Run Enqueued objects:\n";
            $msg .= "===================================\n";
            $this->modx->log(modX::LOG_LEVEL_INFO, $msg.implode("\n",Repoman::$queue));		
        }

        // Migrations

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
        self::migrate($pkg_dir);
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
        
    /**
     * Attempts to uninstall the default namespace, system settings, modx objects,
     * and any database migrations. The behavior is dependent on the MODX cache b/c
     * all new objects are registered in the repoman custom cache partition.
     *
     * No input parameters are required: this looks at the "namespace" config setting.
     *
     * php repoman.php uninstall --namespace=something
     */
    public function uninstall() {

        $cache_dir = MODX_CORE_PATH.'cache/repoman/'.$this->get('namespace');
        if (file_exists($cache_dir) && is_dir($cache_dir)) {
            $obj_dirs = array_diff(scandir($cache_dir), array('..', '.'));

            foreach ($obj_dirs as $objectname_dir) {
                if (!is_dir($cache_dir.'/'.$objectname_dir)) {
                    continue; // wtf? Did you manually edit the cache dirs?
                }

                $objects = array_diff(scandir($cache_dir.'/'.$objectname_dir), array('..', '.'));
                $objecttype = basename($objectname_dir);
                foreach($objects as $o) {
                    $criteria = include $cache_dir.'/'.$objectname_dir.'/'.$o;
                    $Obj = $this->modx->getObject($objecttype, $criteria);
                    if ($Obj) {
                        $Obj->remove();
                    }
                    else {
                        // Some objects are removed b/c of relations before we get to them
                        $this->modx->log(modX::LOG_LEVEL_DEBUG, $objecttype.' could not be located '.print_r($criteria,true));
                    }
                }
            }
            
            Repoman::rrmdir($cache_dir);
        }
        else {
            $this->modx->log(modX::LOG_LEVEL_WARN, 'No cached import data at '.$cache_dir);
        }
        

        
        // migrate uninstall.php
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
            throw new Exception('Path is not a directory: '.$path);
        }
        return $path;
	}
	
	/** 
	 *
	 * See http://www.php.net/manual/en/function.rmdir.php
	 *
	 */
    public static function rrmdir($dir) {
        foreach(glob($dir . '/*') as $file) {
            if(is_dir($file))
                Repoman::rrmdir($file);
            else
                unlink($file);
        }
        rmdir($dir);
    }
	
}
/*EOF*/