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
		
		Repoman::$queue['modSystemSetting'][] = $key;		
		if (!$this->get('dry_run')) {
            $Setting->save();
    		$data = $this->get_criteria('modSystemSetting', $Setting->toArray());
    		$this->modx->log(modX::LOG_LEVEL_INFO, "System Setting created/updated: $key");
        }
	}
			
	/**
	 * Get an array of element objects for the given $objecttype
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
        $criteria = $this->get_criteria($classname, $objectdata);

        $Object = $this->modx->getObject($classname, $criteria);
        if (!$Object || $this->get('is_build')) {
            $Object = $this->modx->newObject($classname);
        }
        
        // Make sure we're allowed to update this type of object
        $attributes = $this->get('build_attributes');
        if ($attributes[$classname][xPDOTransport::UPDATE_OBJECT] || $this->get('is_build')) {
            // Mass assignment $Object->fromArray() does not work: some fields are blocked
            foreach ($objectdata as $k => $v) {
                $Object->set($k, $v);
            }
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
	 * Iterate over the $dir to load up either PHP or JSON arrays representing objects,
	 * then return an array of the corresponding objects.
	 *
Array
(
    [0] => nada.xx.php
    [1] => nada
    [2] => .xx
    [3] => php
)
	 
	 * @param string $dir
	 * @return array of objects : keys for the classname
	 */
	private function _get_objects($dir) {
        
        if (!file_exists($dir) || !is_dir($dir)) {
            $this->modx->log(modX::LOG_LEVEL_DEBUG,'No object directory detected at '.$dir);
            return array();
        }
        $this->modx->log(modX::LOG_LEVEL_INFO,'Crawling object directory '.$dir);

        $objects = array();
        $files = glob($dir.'/*{.php,.json}',GLOB_BRACE);

        foreach($files as $f) {
            
            preg_match('/^(\w+)(.?\w+)?\.(\w+)$/', basename($f), $matches);
            if (!isset($matches[3])) throw new Exception('Invalid filename '.$f);
            
            $classname = $matches[1];
            $ext = $matches[3];            
            $this->modx->log(modX::LOG_LEVEL_INFO,'Processing object(s) in '.basename($f));
            $fields = $this->modx->getFields($classname);
            if (empty($fields)) throw new Exception('Unrecognized object classname: '.$classname);
            
            if ($ext == 'php') {
                $data = include $f;
            }
            elseif ($ext == 'json') {
                $data = json_decode(file_get_contents($f),true);
            }
            $this->modx->log(modX::LOG_LEVEL_DEBUG, $classname." Object data: \n". print_r($data,true));            
            if (!is_array($data)) throw new Exception('Data in '.$f.' not an array.');
            if (!isset($data[0])) {
                $data = array($data);
            }
            
            $i = 0;
            foreach ($data as $objectdata) {
                $i++;
                $Object = $this->_get_object($classname,$objectdata);

                $pk = $Object->getPK();
                if (is_scalar($pk)) {

                    $pk_val = $Object->get($pk);
                    if (!$pk_val) {
                        $pk_val = $i;
                    }
                    $this->modx->log(modX::LOG_LEVEL_INFO,'Setting primary key to '.$pk_val);
                    $Object->set($pk, $pk_val);
                }
                
                $objects[$classname][$pk] = $Object;
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
		
		Repoman::$queue['modNamespace'][] = $name;

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
     * @param object $Obj
     * @param string $classname
     *
     * @return array 
     */
    public function get_build_attributes($Obj,$classname) {

        $attributes = $this->get('build_attributes');
        if (!isset($attributes[$classname])) {
            throw new Exception('Build attributes not defined for class '.$classname);
        }

        // The attributes for the base
        $out = $attributes[$classname];
        return $out; 
        // BUG: dynamic detection is not working...
        // Any related objects?
        $related = array_merge($this->modx->getAggregates($classname), $this->modx->getComposites($classname));

        foreach ($related as $alias => $def) {            
            if (!empty($Obj->$alias)) {
                // WTF?  Not sure why the Resources alias comes overloaded with info
                // if unchecked, this will bomb out the memory usage
                if ($classname == 'modTemplate' && $alias == 'Resources') {
                    continue;
                }
                if (in_array($alias, array('LexiconEntries'))) {
                    continue;
                }
                $out[xPDOTransport::RELATED_OBJECTS] = true;
                $rel_class = $def['class'];
                if ($def['cardinality'] == 'one') {
                    $relObj = $Obj->getOne($alias);
                }
                else {
                    $relObjs = $Obj->getMany($alias);
                    $relObj = array_shift($relObjs);
                }
                $out[xPDOTransport::RELATED_OBJECT_ATTRIBUTES][$rel_class] = $this->get_build_attributes($relObj,$def['class']);
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
        $build_attributes = $this->get('build_attributes');
        if (!isset($build_attributes[$classname][xPDOTransport::UNIQUE_KEY])) {
            throw new Exception('Build attributes xPDOTransport::UNIQUE_KEY not defined for class '.$classname);
        }
        $fields = (array) $build_attributes[$classname][xPDOTransport::UNIQUE_KEY];
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
	 * Assistence function for examining MODX objects and their relations.
	 * _pkg (string) colon-separated string defining the arguments for addPackage() -- 
     *      package_name, model_path, and optionally table_prefix  
     *      e.g. `tiles:[[++core_path]]components/tiles/model/:tiles_` or 
     *      If only the package name is supplied, the path is assumed to be "[[++core_path]]components/$package_name/model/"
	 * Required option: --classname
	 * Optional options:
     *      relations (same as setting aggregates and composites)
     *      aggregates
     *      composites
     *
	 * @param array $args
	 * @return string message
	 */
	public static function look($classname, $args) {

        global $modx;
             
        $relations = (isset($args['relations'])) ? $args['relations'] : '';
        $aggregates = (isset($args['aggregates'])) ? $args['aggregates'] : '';
        $composites = (isset($args['composites'])) ? $args['composites'] : '';
        $classmap = (isset($args['classmap'])) ? $args['classmap'] : '';
//        $showall = (isset($args['showall'])) ? $args['showall'] : '';

        /* TODO:
        if ($pkg) {
            $parts = explode(':',$pkg);
            if (isset($parts[2])) {
                $modx->addPackage($parts[0],$parts[1],$parts[2]);     
            }
            elseif(isset($parts[1])) {
                $modx->addPackage($parts[0],$parts[1]);
            }
            else {
                $modx->addPackage($parts[0],MODX_CORE_PATH.'components/'.$parts[0].'/model/');
            }
        }
        */

        if (empty($classname)) {
            $out = "\n-------------------------\n";
            $out .= "All Available Classes\n";
            $out .= "-------------------------\n";
            foreach ($modx->classMap as $parentclass => $childclasses) {
                            
                $out .= "\n".$parentclass."\n".str_repeat('-', strlen($parentclass))."\n"; 
                foreach ($childclasses as $c) {
                    $out .= "    ".$c."\n";
                }
            }
            return $out;
        }
        
        if (empty($classname)) {
            throw new Exception('classname is required.');
        }

        $array = $modx->getFields($classname);
        
        $related = array();
        if ($aggregates && $composites) {
            $related = array_merge($modx->getAggregates($classname), $modx->getComposites($classname));
        }
        elseif ($aggregates) {
            $related = $modx->getAggregates($classname);
        }
        elseif ($composites) {
            $related = $modx->getComposites($classname);
        }
        elseif ($relations) {
            $related = array_merge($modx->getAggregates($classname), $modx->getComposites($classname));
        }

        foreach ($related as $alias => $def) {
            if ($classmap) {
                $array[$alias] = $def;            
            }
            else {
                $rel_class = $def['class'];
                $rel_fields = $modx->getFields($rel_class);
                if ($def['cardinality'] == 'one') {
                    $array[$alias] = $rel_fields;    
                }
                else {
                    $array[$alias] = array($rel_fields);
                }
            }
        }
        
        $out = print_r($array,true); 
        
        // Make the result pretty. TODO: make it have correct syntax!!!
        $out = str_replace(array('Array','[',']',')'), array('array',"'","'",'),'), $out);
        
        return $out;
	}
	
	/**
	 * Parse command line arguments
	 *
	 * @param array $args
	 * @return array
	 */
	public static function parse_args($args) {
        $overrides = array();
        foreach($args as $a) {
            if (substr($a,0,2) == '--') {
                if ($equals_sign = strpos($a,'=',2)) {
                    $key = substr($a, 2, $equals_sign-2);
                    $val = substr($a, $equals_sign+1);
                    $overrides[$key] = $val;
                }
                else {
                    $flag = substr($a, 2);
                    $overrides[$flag] = true;
                }
            }
        }	
        return $overrides;
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
        
        $required = array('package_name','namespace','version','release');
        foreach($required as $k) {
            if (!$this->get($k)) {
                throw new Exception('Missing required configuration parameter: '.$k);
            }
        }
        
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Beginning build of package "'.$this->get('package_name').'"');
        
        $this->modx->loadClass('transport.modPackageBuilder', '', false, true);
        $builder = new modPackageBuilder($this->modx);
        $builder->createPackage($this->get('package_name'), $this->get('version'), $this->get('release'));
        $builder->registerNamespace($this->get('namespace'), false, true, '{core_path}components/' . $this->get('namespace').'/');
        
        // Tests (Validators): this is run BEFORE your package code is in place
        // so you cannot include package files from your validator! They won't exist when the code is run.
        $validator_file = $pkg_dir.'/core/components/'.$this->get('namespace').'/'.$this->get('validators_dir').'/install.php';
        if (file_exists($validator_file)) {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'Packaging validator '.$validator_file);
            $config = $this->config;
            $config['source'] = $validator_file;
            $validator_attributes = array(
                'vehicle_class' => 'xPDOScriptVehicle',
                'source' => $validator_file,
                xPDOTransport::ABORT_INSTALL_ON_VEHICLE_FAIL => $this->get('abort_install_on_fail')
            );
            $vehicle = $builder->createVehicle($config,$validator_attributes);
            $builder->putVehicle($vehicle);
        }
        else {
            $this->modx->log(modX::LOG_LEVEL_DEBUG, 'No validator detected at '.$validator_file);
        }
        
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
        //if (empty($chunks) && empty($plugins) && empty($snippets) && empty($templates) && empty($tvs)) {
        $build_attributes = array();
        $build_attributes = $this->get_build_attributes($Category,'modCategory');
        $this->modx->log(modX::LOG_LEVEL_DEBUG, 'Build attributes for '. $Category->_class. "\n".print_r($build_attributes,true));
        $vehicle = $builder->createVehicle($Category, $build_attributes);
        //}
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

/*
        $validator_attributes = array(
            'vehicle_class' => 'xPDOScriptVehicle',
            'source' => dirname(__FILE__).'/validator.php',
            xPDOTransport::ABORT_INSTALL_ON_VEHICLE_FAIL => $this->get('abort_install_on_fail')
        );
        $vehicle->validate('php', $validator_attributes);
*/        
        //$vehicle->validate('php', array('source' => dirname(__FILE__).'/validator.php'));        
        $builder->putVehicle($vehicle);
        
        // Migrations: we attach our all-purpose resolver to handle migrations
        $config = $this->config;
        $config['source'] = dirname(__FILE__).'/resolver.php';
        $attributes = array('vehicle_class' => 'xPDOScriptVehicle');        
        $vehicle = $builder->createVehicle($config,$attributes);
        $builder->putVehicle($vehicle);

        
        // Objects
        $objects_dir = $pkg_dir.'/core/components/'.$this->get('namespace').'/'.$this->get('objects_dir');
        $objects = self::_get_objects($objects_dir);
        foreach ($objects as $classname => $info) {
            foreach ($info as $k => $Obj) {
                $build_attributes = $this->get_build_attributes($Obj,$classname);
                $this->modx->log(modX::LOG_LEVEL_INFO, $classname. ' created');
                $vehicle = $builder->createVehicle($Obj, $build_attributes);
                $builder->putVehicle($vehicle);
            }
        }
        

        // Package Attributes (Documents)
        $dir = $pkg_dir.'/core/components/'.$this->get('namespace').'/docs/';
        // defaults
        $docs = array(
            'readme'=>'This package was built using Repoman (https://github.com/craftsmancoding/repoman/)',
            'changelog'=>'No change log defined.',
            'license'=> file_get_contents(dirname(dirname(dirname(__FILE__))).'/docs/license.txt'),
        );        
        if (file_exists($dir) && is_dir($dir)) {
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
                if (strtolower($stub) == 'readme') {
                    $docs['readme'] = $docs['readme'] ."\n\n"
                        .'This package was built using Repoman (https://github.com/craftsmancoding/repoman/)';
                }
                $this->modx->log(modX::LOG_LEVEL_INFO, "Adding doc $stub for $f");
            }            
        }
        else {
            $this->modx->log(modX::LOG_LEVEL_INFO, 'No documents found in '.$dir);
        }
        $builder->setPackageAttributes($docs);        
        // Zip up the package
        $builder->pack();

        $zip = $this->get('namespace').'-'.$this->get('version').'-'.$this->get('release').'.transport.zip';
        $this->modx->log(modX::LOG_LEVEL_INFO, 'Build complete: '. MODX_CORE_PATH.'packages/'.$zip);
    }
    
    /**
     * Extract objects (Settings, Snippets, Pages et al) from MODX and store them in the
     * repository as either object or seed data.
     *
     * --classname 
     * --where
     * --overwrite
     * --package : package_name, model_path, and optionally table_prefix  
     *      e.g. `tiles:[[++core_path]]components/tiles/model/:tiles_` or 
     *      If only the package name is supplied, the path is assumed to be "[[++core_path]]components/$package_name/model/"
     */
    public function export($repo_path) {

        $classname = $this->get('classname');
        $where = $this->get('where');
        // if --seed=xxx is defined, we export the data as seed data, otherwise as object data
        $seed = $this->get('seed');
        $graph = $this->get('graph');
        
        if (empty($classname)) {
            throw new Exception('Parameter "classname" is required.');
        }
        if (preg_match('/[^a-zA-Z0-0_\-]/', $seed)) {
            throw new Exception('Parameter "seed" can contain only letters and numbers.');
        }
        $is_element = false;
        $Parser = null;
        if (in_array($classname, $this->get('export_elements'))) {
            require_once dirname(__FILE__).'/repoman_parser.class.php';
            require_once dirname(__FILE__).'/objecttypes/'.strtolower($classname).'_parser.class.php';
            $is_element = true;
            $element_class = strtolower($classname).'_parser';
            $Parser = new $element_class($this);
        }
        
        $where = json_decode($where, true);

        $package = $this->get('package');
        if ($package) {
            $parts = explode(':',$package);
            if (isset($parts[2])) {
                $this->modx->log(modX::LOG_LEVEL_INFO,'Adding package '.$parts[0].' at '.$parts[1].' with prefix '.$parts[2]);
                $this->modx->addPackage($parts[0],$parts[1],$parts[2]);     
            }
            elseif(isset($parts[1])) {
                $this->modx->log(modX::LOG_LEVEL_INFO,'Adding package '.$parts[0].' at '.$parts[1]);
                $this->modx->addPackage($parts[0],$parts[1]);
            }
            else {
                $this->modx->log(modX::LOG_LEVEL_INFO,'Adding package '.$parts[0]); 
                $this->modx->addPackage($parts[0],MODX_CORE_PATH.'components/'.$parts[0].'/model/');
            }
        }
    

        $criteria = $this->modx->newQuery($classname);
        if (!empty($where)) {
            $criteria->where($where);
        }
        $total_pages = $this->modx->getCount($classname,$criteria);
    
        $results = array();
        $related = array();
        if ($graph) {
            $results = $this->modx->getCollectionGraph($classname,$graph,$criteria);
            $related = json_decode($graph,true);
//            print_r($related); exit;
        }
        else {
            $results = $this->modx->getCollection($classname,$criteria);
        }
        if ($this->get('debug')) {
            $criteria->prepare();
            $out = "----------------------------------\n";
            $out .= "Export Debugging Info\n";
            $out .= "----------------------------------\n\n";
            $out .= "Raw Where filters:\n".print_r($where,true)."\n";
            $out .= "Raw SQL Query:\n";
            $out .= $criteria->toSQL();
            $out .= "\n\nResults found : {$total_pages}\n\n";
            return $out;
        }

        if (!$is_element) {
            $dir = $repo_path. '/core/components/'.$this->get('namespace').'/';
            if ($seed) {
                $dir .= $this->get('seeds_dir').'/'.$this->get('seed');
            }
            else {
                $dir .= $this->get('objects_dir');
            }
            
            if (!file_exists($dir)) {
                if (false === mkdir($dir, $this->get('dir_mode'), true)) {
                    throw new Exception('Could not create directory '.$dir);
                }
                else {
                    $this->modx->log(modX::LOG_LEVEL_DEBUG,'Created directory '.$dir);
                }
            }
            elseif (!is_dir($dir)) {
                throw new Exception('Path is not a directory '.$dir);
            }
        }

        if ($results) {
            $i = 0;
            foreach ($results as $r) {
                if ($is_element) {
                    $Parser->create($repo_path,$r,$graph);
                }
                else {
                    $i++;
                    $array = $r->toArray('',false,false,$graph);
                    $content = json_encode($array, JSON_PRETTY_PRINT);
                    $filename = $dir .'/'.$classname.'.'.$i.'.json';            
                    //print "\n".$filename."\n"; exit;
                    if (file_exists($filename) && !$this->get('overwrite')) {
                        throw new Exception('Overwrite not permitted '.$filename);
                    }

                    if (false === file_put_contents($filename, $content)) {
                        throw new Exception('Could not write to file '.$filename);
                    }
                    else {
                        $this->modx->log(modX::LOG_LEVEL_INFO,'Created object file at '. $filename);        
                    }
                }
            }   
        }
        else {
            $this->modx->log(modX::LOG_LEVEL_ERROR, 'No matching results found for '.$classname);
        }
    }
    
    /**
     * Our take-off from xPDO's fromArray() function, but one that can import whatever toArray() 
     * spits out.
     *
     * @param string $classname
     * @param array $objectdata
     * @param boolean $rawvalues e.g. for modUser, you'd enter the password plaintext and it gets hashed. 
     *      Set to true if you want to store the literal hash.
     * @return object
     */
    function fromDeepArray($classname, $objectdata, $rawvalues=false) {
        
        $Object = $this->modx->newObject($classname);
        $Object->fromArray($objectdata,'',false,$rawvalues);
        $related = array_merge($this->modx->getAggregates($classname), $this->modx->getComposites($classname));
        foreach ($objectdata as $k => $v) {
            if (isset($related[$k])) {
                $alias = $k;
                $rel_data = $v;
                $def = $related[$alias];
                
                if (!is_array($def)) {
                    $this->modx->log(modX::LOG_LEVEL_WARN, 'Data in '.$classname.'['.$alias.'] not an array.');
                    continue;
                }
                if ($def['cardinality'] == 'one') {
                    $one = $this->fromDeepArray($def['class'],$rel_data,$rawvalues);
                    $Object->addOne($one);
                }
                else {
                    if (!isset($rel_data[0])) {
                        $rel_data = array($rel_data);
                    }
                    $many = array();
                    foreach ($rel_data as $r) {
                        $many[] = $this->fromDeepArray($def['class'],$r,$rawvalues);   
                    }
                    $Object->addMany($many);
                }
                
            }
        }
        return $Object;
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
        $i = 0;
        $objects_dir = $pkg_dir.'/core/components/'.$this->get('namespace').'/'.$this->get('objects_dir');        
        $objects = self::_get_objects($objects_dir);
        foreach ($objects as $classname => $info) {
            foreach ($info as $k => $Object) {
                $data = $this->get_criteria($classname, $Object->toArray());
                Repoman::$queue[$classname][] = implode('-',$data);
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
        
 
        if ($this->get('dry_run')) {
            $msg = "\n==================================\n";
            $msg .= "    Dry Run Enqueued objects:\n";
            $msg .= "===================================\n";
            foreach (Repoman::$queue as $classname => $list) {
                $msg .= "\n".$classname."\n".str_repeat('-', strlen($classname))."\n"; 
                foreach ($list as $l) {
                    $msg .= "    ".$l."\n";
                }
            }
            $this->modx->log(modX::LOG_LEVEL_INFO, $msg);		
        }
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
     * Given a filename, return the array of records stored in the file.
     *
     * @param string $fullpath
     * @param boolean $json if true, the file contains json data so it will be decoded
     * @return array
     */
    public function load_data($fullpath, $json=false) {
        $this->modx->log(modX::LOG_LEVEL_DEBUG,'Processing object(s) in '.$fullpath);                                
            
        if ($json) {
            $data = json_decode(file_get_contents($fullpath),true);
        }
        else {
            $data = include $fullpath;
        }        
        
        if (!is_array($data)) {
            $this->modx->log(modX::LOG_LEVEL_ERROR,'Data in '.$fullpath.' not an array.');
            return array();
        }
        if (!isset($data[0])) {
            $data = array($data);
        }
        
        return $data;
    }


    /**
     * Run database migrations: create/remove custom database tables.
     *
     */
    public function migrate($pkg_dir) {
        
        global $modx;
        // For compatibility
        $object = $this->config;
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
            
        // Optionally Load Seed data
        if ($seed = $this->get('seed')) {
            if (!is_array($seed)) {
                $seed = explode(',',$seed);
            }
            foreach ($seed as $s) {
                if (file_exists($seeds_path.'/'.$s) && is_dir($seeds_path.'/'.$s)) {
                    $this->modx->log(modX::LOG_LEVEL_INFO,'Walking seed directory '.$seeds_path.'/'.$s);
                    $files = glob($seeds_path.'/'.$s.'/*{.php,.json}',GLOB_BRACE);
                    foreach ($files as $f) {
                        preg_match('/^(\w+)(.?\w+)?\.(\w+)$/', basename($f), $matches);
                        if (!isset($matches[3])) {
                            $this->modx->log(modX::LOG_LEVEL_ERROR, 'Invalid filename '.$f);
                            continue;
                        }
                        $classname = $matches[1];
                        $ext = $matches[3];            
                        $is_json = (strtolower($ext) == 'php')? false : true;
                        if (!$fields = $this->modx->getFields($classname)) {
                            $this->modx->log(modX::LOG_LEVEL_ERROR,'Unrecognized object classname: '.$classname);
                            continue;
                        } 

                        if (!$data = $this->load_data($f, $is_json)) {
                            continue;
                        }
                        foreach ($data as $objectdata) {
                            if($Obj = $this->fromDeepArray($classname, $objectdata)) {
                                if (!$Obj->save()) {
                                    $this->modx->log(modX::LOG_LEVEL_ERROR,'Error saving object in '.$f);
                                }
                            }
                            else {
                                $this->modx->log(modX::LOG_LEVEL_ERROR,'Error extracting object from '.$f);
                            }
                        }
                    }
                }
            }
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
        $regenerate_mysql = $this->get('regenerate_mysql');
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
        }
        if ($regenerate_mysql) {    
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
            $this->modx->log(modX::LOG_LEVEL_INFO, "Running migrations/uninstall.php");
            include $migrations_path.'/uninstall.php';
        }        
    }
	
}
/*EOF*/