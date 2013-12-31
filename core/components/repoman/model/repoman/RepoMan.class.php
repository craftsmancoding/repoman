<?php
/**
 * This class has some static methods for utility functions that can be used
 * before the class is instantiated.
 *
 */
// We need this for the xPDOTransport class constants
require_once MODX_CORE_PATH.'xpdo/transport/xpdotransport.class.php';

class Repoman {

	public $modx;
	
	public $config = array();

    // Used to provide transparency
    public static $queue = array();
	// public $readme_filenames = array('README.md','readme.md');

    public static $cache_opts = array();
    const CACHE_DIR = 'repoman';

    // Used for package building: we can't reference xPDOTransport constants until
    // we've loaded the modPackageBuilder class
    // $modx->loadClass('transport.modPackageBuilder', '', false, true);
/*
    public $cat_attributes = array(
        xPDOTransport::PRESERVE_KEYS => true,
        xPDOTransport::UPDATE_OBJECT => false,
        xPDOTransport::UNIQUE_KEY => array('category'),
    	xPDOTransport::RELATED_OBJECTS => true,
        xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array (
            'Snippets' => array(
                xPDOTransport::PRESERVE_KEYS => false,
                xPDOTransport::UPDATE_OBJECT => true,
                xPDOTransport::UNIQUE_KEY => 'name',
            ),
            'Chunks' => array (
                xPDOTransport::PRESERVE_KEYS => false,
                xPDOTransport::UPDATE_OBJECT => true,
                xPDOTransport::UNIQUE_KEY => 'name',
            ),
            'Plugins' => array (
                xPDOTransport::PRESERVE_KEYS => false,
                xPDOTransport::UPDATE_OBJECT => true,
                xPDOTransport::UNIQUE_KEY => 'name',
    			xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array (
    		        'PluginEvents' => array(
    		            xPDOTransport::PRESERVE_KEYS => true,
    		            xPDOTransport::UPDATE_OBJECT => false,
    		            xPDOTransport::UNIQUE_KEY => array('pluginid','event'),
    		        ),
        		),
            ),
        )    
    );
*/
        
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
    		$data = $this->get_criteria('modSystemSetting', $Setting->toArray());
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
        $Parser = new $classname($this);
        
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
                $rel_data = $objectdata[$rclass];
                if (!is_array($rel_data)) throw new Exception('Data in '.$classname.'['.$rclass.'] not an array.');
                if ($def['cardinality'] == 'one') {
                    $one = $this->_get_object($def['class'],$rel_data); // Avoid E_STRICT notices
                    $Object->addOne($one);
                }
                else {
                    if (!isset($rel_data[0])) {
                        $rel_data = array($rel_data);
                    }
                    $many = array();
                    foreach ($rel_data as $r) {
                        $many[] = $this->_get_object($def['class'],$r);   
                    }
                    $Object->addMany($many);
                }
            }
        }
        
        return $Object;
    }
    
	/**
	 * Iterate over the objects directory to load up all non-element objects.
	 *
	 * @param string $pkg_dir
	 * @return array of objects : keys for the classname
	 */
	private function _get_objects($pkg_dir) {
        $dir = $pkg_dir.'/core/components/'.$this->get('namespace').'/'.$this->get('objects_dir');
        if (!file_exists($dir) || !is_dir($dir)) {
            $this->modx->log(modX::LOG_LEVEL_DEBUG,'No object directory detected at '.$dir);
            return array();
        }
        $this->modx->log(modX::LOG_LEVEL_INFO,'Crawling object directory '.$dir);

        $objects = array();
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
                $objects[$classname][] = $Object;
            }
	   }
	   return $objects;
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
    		$data = $this->get_criteria('modNamespace',$N->toArray());
            $this->modx->cacheManager->set('modNamespace/'.$N->get('name'), $data, 0, Repoman::$cache_opts);
    		$this->modx->log(modX::LOG_LEVEL_INFO, "Namespace created/updated: $name");
        }
	}

    //------------------------------------------------------------------------------
    //! Static
    //------------------------------------------------------------------------------
	/**
	 * Verify a directory, converting for any OS variants and convert
	 * any relative paths to absolute . 
	 *
	 * @param string $path path (or relative path) to package
	 * @return string full path without trailing slash
	 */
	public static function get_dir($path) {
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
     * When building packages, these attributes govern how objects are updated
     * when the package is installed.  One difficulty here is that one instance 
     * of an object may have many related objects (and thus require deeply nested
     * build attributes), whereas another object instance may have no related objects.
     * So this function traces out all of an object's relations and grows the build
     * attributes accordingly.
     *
     * @param string $classname
     *
     * @return array 
     */
    public function get_build_attributes($Obj) {

        if (!isset($Obj->_class)) {
            throw new Exception('Class not defined for object.');
        }
        $classname = $Obj->_class;
        $attributes = $this->get('build_attributes');
        if (!isset($attributes[$classname])) {
            throw new Exception('Build attributes not defined for class '.$classname);
        }
        // The attributes for the base
        $out = $attributes[$classname];
        // Any related objects?
        $related = array_merge($this->modx->getAggregates($classname), $this->modx->getComposites($classname));
        foreach ($related as $alias => $def) {            
            if (!empty($Obj->$alias)) {
                $out[xPDOTransport::RELATED_OBJECTS] = true;
                $rel_class = $def['class'];
                if ($def['cardinality'] == 'one') {
                    $relObj = $Obj->getOne($alias);
                }
                else {
                    $relObjs = $Obj->getMany($alias);
                    $relObj = array_shift($relObjs);
                }
                $out[xPDOTransport::RELATED_OBJECT_ATTRIBUTES][$rel_class] = $this->get_build_attributes($relObj);
            }
        }
        return $out;
    }

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
     * @param array $attributes data for a single object representing $classname
     * @return array
     */
    public function get_criteria($classname, $attributes) {
        $attributes = $this->get('build_attributes');
        if (!isset($attributes[$classname])) {
            throw new Exception('xPDOTransport::UNIQUE_KEY not defined for class '.$classname);
        }
        $fields = (array) $attributes[$classname][xPDOTransport::UNIQUE_KEY];
        $criteria = array();
        foreach ($fields as $f) {
            if (isset($attributes[$f]) && !empty($attributes[$f])) {
                $criteria[$f] = $attributes[$f];
            }
        }
                
        return $criteria;
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
       	
	//------------------------------------------------------------------------------
	//! Public
	//------------------------------------------------------------------------------
	
    /** 
     * Unified build script.
     *
     */
    public function build($pkg_dir) {

        $this->config['is_build'] = true; // TODO
        $this->config['force_static'] = false; // TODO
        
        $required = array('package_name','namespace','version','release','category');
        foreach($required as $k) {
            if (!$this->get($k)) {
                throw new Exception('Missing required configuration parameter: '.$k);
            }
        }
        
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Beginning build of package '.$this->get('package_name'));
        
        $this->modx->loadClass('transport.modPackageBuilder', '', false, true);
        $builder = new modPackageBuilder($this->modx);
        $builder->createPackage($this->get('package_name'), $this->get('version'), $this->get('release'));
        $builder->registerNamespace($this->get('namespace'), false, true, '{core_path}components/' . $this->get('namespace').'/');
        
        $Category = $this->modx->newObject('modCategory');
        $Category->set('category', $this->get('category'));

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

        // TODO: skip this if there are no elements
        $build_attributes = $this->get_build_attributes($Category);
        $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Build attributes for '. $Category->_class. "\n".print_r($build_attributes,true));
        $vehicle = $builder->createVehicle($Category, $build_attributes);
        //$builder->putVehicle($vehicle);


        // Files...: TODO: these need their own builder
        // Assets
        $dir = $pkg_dir.'/assets/components/'.$this->get('namespace');
        if (file_exists($dir) && is_dir($dir)) {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Packing assets from '.$dir);
            $vehicle->resolve('file', array(
                'source' => $dir,
                'target' => "return MODX_ASSETS_PATH . 'components/';",
            ));
        }        
        // Core
        $dir = $pkg_dir.'/core/components/'.$this->get('namespace');
        if (file_exists($dir) && is_dir($dir)) {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Packing core files from '.$dir);
            $vehicle->resolve('file', array(
                'source' => $dir,
                'target' => "return MODX_CORE_PATH . 'components/';",
            ));
        }
        $builder->putVehicle($vehicle);
        
        // Migrations: we attach our all-purpose resolver to handle migrations
        $config = $this->config;
        $config['source'] = dirname(__FILE__).'/resolver.php';        
        $vehicle = $builder->createVehicle($config,array('vehicle_class'=>'xPDOScriptVehicle'));
        $builder->putVehicle($vehicle);
/*
        $vehicle = $builder->createVehicle(array('source'=>'path/to/myfile.php','arbitrary'=>'some arbitrary data'), $attributes = array (
  'vehicle_class' => 'xPDOScriptVehicle',));
*/

        
//        $dir = $pkg_dir .'/core/components/'.$this->get('namespace').'/'.$this->get('migrations_dir');
//        $f = $dir.'/test.php';
//        $f = '/Users/everett2/Sites/moxycart/html/assets/mycomponents/repoman/core/components/repoman/elements/chunks/resolver.php';
//        $this->modx->log(modX::LOG_LEVEL_INFO, 'TEST: Packing migrations from '.$f);
//        $vehicle->resolve('php', array('source' => $f));
/*
        if (file_exists($dir) && is_dir($dir)) {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Packing migrations from '.$dir);
            $files = glob($dir.'*.php');
            foreach($files as $f) {
                if (basename($f) != 'uninstall.php') {
                    $this->modx->log(modX::LOG_LEVEL_INFO, 'Adding migration '.basename($f));
                    $vehicle->resolve('php', array('source' => $f));
                }
            }
        }
*/
    


        
        // Objects
        $objects = self::_get_objects($pkg_dir);
        foreach ($objects as $classname => $Obj) {
            $build_attributes = $this->get_build_attributes($Category);
            $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Build attributes for '
                . $Category->_class. "\n".print_r($build_attributes,true));
            $vehicle = $builder->createVehicle($Obj, $build_attributes);
            $builder->putVehicle($vehicle);
        }
        

        // Package Attributes (Documents)
        $dir = $pkg_dir.'/core/components/'.$this->get('namespace').'/docs/';
        if (file_exists($dir) && is_dir($dir)) {
            $docs = array(
                'readme'=>'No readme defined.',
                'changelog'=>'No changelog defined.',
                'license'=>'No license defined.'
            );
            $files = array();
            $build_docs = $this->get('build_docs');
            if (!empty($build_docs) && is_array($build_docs)) {
                foreach ($build_docs as $d) {
                    $files[] = $dir . $d;
                }
            }
            else {            
                $files = glob($dir.'*.{html,txt}',GLOB_BRACE);
            }
            
            foreach($files as $f) {
                $stub = basename($f,'.txt');
                $stub = basename($stub,'.html');
                $docs[$stub] = file_get_contents($f);
                $this->modx->log(modX::LOG_LEVEL_INFO, "Adding doc $stub for $f");
            }
            
            if (!empty($docs)) {
                $builder->setPackageAttributes($docs);
            }
        }
        else {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'No documents found in '.$dir);
        }
        
        // Zip up the package
        $builder->pack();

        $zip = $this->get('namespace').'-'.$this->get('version').'-'.$this->get('release').'.transport.zip';
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Build complete: '. MODX_CORE_PATH.'packages/'.$zip);
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
     * Import pkg elements into MODX
     *
     */
    public function import($pkg_dir) {

        $pkg_dir = self::get_dir($pkg_dir);        
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
            $data = $this->get_criteria('modCategory', $Category->toArray());
    		$this->modx->cacheManager->set('modCategory/'.$this->get('category'), $data, 0, self::$cache_opts);
            $this->modx->log(modX::LOG_LEVEL_INFO, "Category created/updated: ".$this->get('category'));
        }

        // Regular Objects        
        $objects = self::_get_objects($pkg_dir);
        foreach ($objects as $classname => $Object) {
            $data = $this->get_criteria($classname, $Object->toArray());
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
        $pkg_dir = self::get_dir($pkg_dir);
        self::import($pkg_dir);
        self::migrate($pkg_dir);
    }

    /**
     * Run database migrations: create/remove custom database tables.
     *
     */
    public function migrate($pkg_dir) {
        
        global $modx;
        
        // TODO: check for modx_transport_packages -- SELECT * FROM modx_transport_packages WHERE package_name = xxx
        // if this has been installed via a package, then skip??
        // TODO: make this configurable... Dept. of Redundency Dept.
        $migrations_path = $pkg_dir .'/core/components/'.$this->get('namespace').'/'.$this->get('migrations_dir');
        $seeds_path = $pkg_dir .'/core/components/'.$this->get('namespace').'/'.$this->get('seeds_dir');

        if (!file_exists($migrations_path) || !is_dir($migrations_path)) {
            $this->modx->log(modX::LOG_LEVEL_INFO, "No migrations detected at ".$migrations_path);
            return;
        }

        if (file_exists($migrations_path.'/uninstall.php')) {
            $this->modx->log(modX::LOG_LEVEL_INFO, "Running migrations/uninstall.php");
            include $migrations_path.'/uninstall.php';
        }
        if (file_exists($migrations_path.'/install.php')) {
            $this->modx->log(modX::LOG_LEVEL_INFO, "Running migrations/install.php");
            include $migrations_path.'/install.php';
        }
        // Loop over remaining migrations
        $files = glob($migrations_path.'/*.php');

        foreach($files as $f) {
            $base = basename($f);
            if (in_array($base, array('install.php','uninstall.php'))) {
                $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Skipping '.$base);
                continue;
            }
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Running migration '.basename($f));
            include $f;
        }
            
        // Optional Seed data
        $seed = $this->get('seed');
        if ($seed) {
            $seed = basename($seed,'.php');
            $seed_file = $seeds_path.'/'.$seed.'.php';
            if (!file_exists($seed_file)) {
                throw new Exception('Seed file does not exist: '.$seed_file);
            }
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Importing seed data from '.$seed_file);
            include $seed_file;
        }
    }

    /**
     * Dev tool for parsing XML schema.  xyz.mysql.schema.xml maps to the model/xyz/ directory.
     *
     * Configuration options:
     *
     *  --schema (required)
     *  --regenerate_classes
     */
    public function parse($pkg_dir) {
        $schema = $this->get('schema'); // name of the schema
        $regenerate_classes = $this->get('regenerate_classes');
        $dir_perms = $this->get('dir_perms');
        
        if (empty($schema)) throw new Exception('"schema" parameter is required.');
        
        // TODO: make this configurable
        $schema_file = $pkg_dir .'/core/components/'.$this->get('namespace').'/model/schema/'.$schema.'.mysql.schema.xml';
        if (!file_exists($schema_file)) throw new Exception('Schema file does not exist: '.$schema_file);
        $model_dir = $pkg_dir.'/core/components/'.$this->get('namespace').'/model/';
        $class_dir = $model_dir.$this->get('schema').'/';
        $mysql_dir = $class_dir.'mysql';
                
        // Load existing stuff? Not needed for parsing...
        //$this->modx->addPackage($this->get('schema'),$model_dir);
        $manager = $this->modx->getManager();
        $generator = $manager->getGenerator();
        
        $dirs = array($mysql_dir, $class_dir);  
        foreach ($dirs as $d) {
            if ( !file_exists($d) ) {
                if (!mkdir($d, $dir_perms, true) ) {
                    throw new Exception('Could not create directory '.$d);
                }
            }
            if (!is_writable($d) ) {
                throw new Exception('Directory is not writeable '.$d);
            }
        }
         
        // Use this to generate classes and maps from your schema
        if ($regenerate_classes) {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Attempting to remove class files at '.$class_dir);
            self::rrmdir($class_dir);
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Attempting to remove class files at '.$mysql_dir);
            self::rrmdir($mysql_dir);
        }

        $generator->parseSchema($schema_file,$model_dir);        
        
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
    public function uninstall($pkg_dir) {

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
        
        // uninstall migrations. Global modx so that included files can reference $modx object.
        global $modx;
        
        $migrations_path = $pkg_dir .'/core/components/'.$this->get('namespace').'/'.$this->get('migrations_dir');

        if (!file_exists($migrations_path) || !is_dir($migrations_path)) {
            $this->modx->log(modX::LOG_LEVEL_INFO, "No migrations detected at ".$migrations_path);
            return;
        }

        if (file_exists($migrations_path.'/uninstall.php')) {
            $modx->log(modX::LOG_LEVEL_INFO, "Running migrations/uninstall.php");
            include $migrations_path.'/uninstall.php';
        }        
    }
	
}
/*EOF*/