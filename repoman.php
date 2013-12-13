<?php
/**
 * Repoman 
 *
 * Command line tool for pulling in MODX Revo dev projects into the local environment.
 * Place this script anywhere inside your MODX web root.
 *
 * This script creates the following for your dev packages:
 *
 *  namespace: including a core path and an assets path pointing to your dev location 
 *  system setting: your_pkg.core_path
 *  system setting: your_pkg.assets_url
 *
 * If you are re-running this script, the namespace and system settings are updated
 * and any discovered elements/objects are updated.  If you change the name of an 
 * element, a new element will be created: this script does not attempt to do house-keeping.
 *
 * USAGE
 * -------
 *
 * php repoman.php /path/to/modx/assets/repos/mypkg
 *
 */
 
/**
 * Used to get parameters out of a (PHP) docblock.
 *
 * @param string $string the unparsed contents of a file
 * @param string $dox_start string designating the start of a comment (dox) block
 * @param string $dox_start string designating the start of a comment (dox) block 
 * @return array on success | false on no doc block found
 */
function get_attributes_from_dox($string,$dox_start='/*',$dox_end='*/') {
    
    $dox_start = preg_quote($dox_start,'#');
    $dox_end = preg_quote($dox_end,'#');


    // Any tags to skip in the doc block, e.g. @param, that may have significance for PHPDoc and 
    // for general documentation, but which are not intended for RepoMan and do not describe
    // object attributes. Omit "@" from the attribute names.
    // See http://en.wikipedia.org/wiki/PHPDoc
    $skip_tags = array('param','return','abstract','access','author','copyright','deprecated',
        'deprec','example','exception','global','ignore','internal','link','magic',
        'package','see','since','staticvar','subpackage','throws','todo','var','version'
    );

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
        if (isset($m[1]) && isset($m[2]) && !in_array($m[1], $skip_tags)) {
                $a[$m[1]] = trim($m[2]);
        }
    }
    
    return $a;
}

/**
 * Lookup a category id given either its id or its name
 *
 * @return integer
 */
function get_category_id($str) {
    global $modx;
    
    if (is_numeric($str)) {
        $C = $modx->getObject('modCategory', $str);
        return $C->get('id');
    }
    else {
        $C = $modx->getObject('modCategory', array('category'=>$str));
        return $C->get('id');
    }
    return 0;    
}

/**
 * Given an absolute path, e.g. /home/user/public_html/assets/file.php
 * return the file path relative to the MODX base path, e.g. assets/file.php
 * @param string $path
 * @return string
 */
function path_to_rel($path) {
    return preg_replace('#^'.MODX_BASE_PATH.'#','',$path); // convert path to url
}

/**
 *
 *
 */
function usage() {
    print "Usage:\n";
    print "php repoman.php /path/to/modx/assets/repos/mypkg\n";
}


//------------------------------------------------------------------------------

if (php_sapi_name() !== 'cli') {
    die('CLI access only');
}


if (!isset($argv[1])) {
    print "Missing required parameter.\n";
    usage();
    exit(1);
}

$pkg_path = realpath($argv[1]);

if (!file_exists($pkg_path) || !is_dir($pkg_path)) {
    print "ERROR: Package does not exist or is not a directory.\n";
    exit(2);
}

// As long as this script is built placed inside a MODX docroot, this will sniff out
// a valid MODX_CORE_PATH.  This will effectively force the MODX_CONFIG_KEY too.
// The config key controls which config file will be loaded. 
// Syntax: {$config_key}.inc.php
// 99.9% of the time this will be "config", but it's useful when dealing with
// dev/prod pushes to have a config.inc.php and a prod.inc.php, stg.inc.php etc.
if (!defined('MODX_CORE_PATH') && !defined('MODX_CONFIG_KEY')) {
    $max = 10;
    $i = 0;
    $dir = dirname(__FILE__);
    while(true) {
        if (file_exists($dir.'/config.core.php')) {
            include $dir.'/config.core.php';
            break;
        }
        $i++;
        $dir = dirname($dir);
        if ($i >= $max) {
            print "Could not find a valid config.core.php file.\n";
            print "Make sure your repo is inside a MODX webroot and try again.\n";
            die();
        }
	}
}


if (!defined('MODX_CORE_PATH') || !defined('MODX_CONFIG_KEY')) {
    print "Somehow the loaded config.core.php did not define both MODX_CORE_PATH and MODX_CONFIG_KEY constants.\n";
    die();    
}

if (!file_exists(MODX_CORE_PATH.'model/modx/modx.class.php')) {
    print "modx.class.php not found at ".MODX_CORE_PATH."\n";
    die();
}
print "-----------------------------------\n";
print "Welcome to Repoman\n";
print "-----------------------------------\n";

require_once(MODX_CORE_PATH.'config/'.MODX_CONFIG_KEY.'.inc.php');

// fire up MODX
require_once MODX_CORE_PATH.'model/modx/modx.class.php';
$modx = new modx();
$modx->initialize('mgr');
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget('ECHO'); 
flush();

$modx->log(modX::LOG_LEVEL_INFO, 'Processing package at '.$pkg_path);

$package_name = strtolower(basename($pkg_path));

//------------------------------------------------------------------------------
//! Repoman Namespace and Settings
//------------------------------------------------------------------------------

// Create/Update Namespace
$N = $modx->getObject('modNamespace',$package_name);
if (!$N) {
	$N = $modx->newObject('modNamespace');
	$N->set('name', $package_name);
}
$N->set('path', $pkg_path.'/core/components/'.$package_name.'/'); // a.k.a. core_path
$N->set('assets_path',$pkg_path.'/assets/components/'.$package_name.'/');
$N->save();
$modx->log(modX::LOG_LEVEL_INFO, "Namespace created/updated: $package_name");


// Create/Update the package.assets_url setting (if not set already)
$key = $package_name .'.assets_url';
$rel_path = preg_replace('#^'.MODX_BASE_PATH.'#','',$pkg_path); // convert path to url
$assets_url = MODX_BASE_URL.$rel_path .'/assets/';

$Setting = $modx->getObject('modSystemSetting', $key);
if (!$Setting) {
    $Setting = $modx->newObject('modSystemSetting');	
    $Setting->set('key', $key);
    $Setting->set('xtype', 'textfield');
    $Setting->set('namespace', $package_name);
    $Setting->set('area', 'default');
}

$Setting->set('value', $assets_url);		

if (!$Setting->save()) {
    $modx->log(modX::LOG_LEVEL_ERROR, "Failed to save System Setting $key");		
}

$modx->log(modX::LOG_LEVEL_INFO, "Created/Updated System Setting $key: $assets_url");

if (!file_exists($pkg_path.'/assets/components/'.$package_name.'/')) {
    $modx->log(modX::LOG_LEVEL_WARN, "Asset directory did not exist ".$pkg_path.'/assets/components/'.$package_name.'/ -- you may ignore this warning if your package is not using assets. Otherwise verify your paths and make sure they are lowercase.');
}

// Create/Update the package.assets_path setting (if not set already)
$key = $package_name .'.assets_path';

$assets_path = $pkg_path .'/assets/';

$Setting = $modx->getObject('modSystemSetting', $key);
if (!$Setting) {
    $Setting = $modx->newObject('modSystemSetting');	
    $Setting->set('key', $key);
    $Setting->set('xtype', 'textfield');
    $Setting->set('namespace', $package_name);
    $Setting->set('area', 'default');
}

$Setting->set('value', $assets_path);

if (!$Setting->save()) {
    $modx->log(modX::LOG_LEVEL_ERROR, "Failed to save System Setting $key");		
}
$modx->log(modX::LOG_LEVEL_INFO, "Created/Updated System Setting $key: $assets_path");

// Create/Update the package.core_path setting (if not set already)
$key = $package_name .'.core_path';
$modx->log(modX::LOG_LEVEL_INFO, "Created/Updated System Setting $key: $assets_url");
$core_path = $pkg_path .'/core/';

$Setting = $modx->getObject('modSystemSetting', $key);
if (!$Setting) {
    $Setting = $modx->newObject('modSystemSetting');	
    $Setting->set('key', $key);
    $Setting->set('xtype', 'textfield');
    $Setting->set('namespace', $package_name);
    $Setting->set('area', 'default');
}

$Setting->set('value', $core_path);

if (!$Setting->save()) {
    $modx->log(modX::LOG_LEVEL_ERROR, "Failed to save System Setting $key");		
}

$modx->log(modX::LOG_LEVEL_INFO, "Created/Updated System Setting $key: $core_path");

if (!file_exists($pkg_path.'/core/components/'.$package_name.'/')) {
    $modx->log(modX::LOG_LEVEL_WARN, "Core directory did not exist ".$pkg_path.'/core/components/'.$package_name.'/ -- you may ignore this warning if your package is not using any core files (although this would be highly unusual). Verify your paths and make sure they are lowercase.');
}

//------------------------------------------------------------------------------
// !Migrations
//------------------------------------------------------------------------------
$migrations_path = $pkg_path .'/core/components/'.$package_name.'/migrations';
if (!file_exists($migrations_path) || !is_dir($migrations_path)) {
    $modx->log(modX::LOG_LEVEL_INFO, "No migrations detected at ".$migrations_path);
}
else {
    if (file_exists($migrations_path.'/uninstall.php')) {
        $modx->log(modX::LOG_LEVEL_INFO, "Running migrations/uninstall.php");
        include($migrations_path.'/uninstall.php');
    }
    if (file_exists($migrations_path.'/install.php')) {
        $modx->log(modX::LOG_LEVEL_INFO, "Running migrations/install.php");
        include($migrations_path.'/install.php');
    }
    if (file_exists($migrations_path.'/seed.php')) {
        $modx->log(modX::LOG_LEVEL_INFO, "Running migrations/seed.php");
        include($migrations_path.'/seed.php');
    }
}


//------------------------------------------------------------------------------
//! Categories
//------------------------------------------------------------------------------
$Category = $modx->getObject('modCategory', array('category'=>$package_name));
if (!$Category) {
    $Category = $modx->newObject('modCategory');
    $Category->set('category', $package_name);
    $Category->save();
    $modx->log(modX::LOG_LEVEL_INFO, "Category created.");
}


//------------------------------------------------------------------------------
//! Snippets
//------------------------------------------------------------------------------
$dir = $pkg_path.'/core/components/'.$package_name.'/elements/snippets/';
$objects = array();
if (file_exists($dir) && is_dir($dir)) {
    $files = glob($dir.'*.php');
    foreach($files as $f) {
        $content = file_get_contents($f);
        $attributes = get_attributes_from_dox($content);
        if (!isset($attributes['name'])) {
            $name = basename($f,'.php');
            $attributes['name'] = basename($name,'.snippet');
        }
        $Obj = $modx->getObject('modSnippet',array('name'=>$attributes['name']));
        if (!$Obj) {
            $Obj = $modx->newObject('modSnippet');
        }
        if (!isset($attributes['category'])) {
            $attributes['category'] = $Category->get('id'); // Default
        }
        else {
            $attributes['category'] = get_cagtegory_id($attributes['category']);
        }
        // Force Static
        $attributes['static'] = 1;
        $attributes['static_file'] = path_to_rel($f);
        
        $Obj->fromArray($attributes);
        $Obj->setContent($content);
        
        if(!$Obj->save()) {
           $modx->log(modX::LOG_LEVEL_ERROR,'Could not save Snippet: '.$attributes['name']);
        }
        else {
            $modx->log(modX::LOG_LEVEL_INFO,'Snippet created/updated: '.$attributes['name']);           
        }    
    }
}


//------------------------------------------------------------------------------
//! Chunks
//------------------------------------------------------------------------------
$dir = $pkg_path.'/core/components/'.$package_name.'/elements/chunks/';
$objects = array();
if (file_exists($dir) && is_dir($dir)) {
    $files = glob($dir.'*.*');
    foreach($files as $f) {
        $content = file_get_contents($f);
        $attributes = get_attributes_from_dox($content);
        if (!isset($attributes['name'])) {
            $name = basename($f,'.tpl');
            $attributes['name'] = basename($name,'.chunk');
        }
        $Obj = $modx->getObject('modChunk',array('name'=>$attributes['name']));
        if (!$Obj) {
            $Obj = $modx->newObject('modChunk');
        }
        if (!isset($attributes['category'])) {
            $attributes['category'] = $Category->get('id'); // Default
        }
        else {
            $attributes['category'] = get_cagtegory_id($attributes['category']);
        }
        // Force Static
        $attributes['static'] = 1;
        $attributes['static_file'] = path_to_rel($f);
        
        $Obj->fromArray($attributes);
        $Obj->setContent($content);
        
        if(!$Obj->save()) {
           $modx->log(modX::LOG_LEVEL_ERROR,'Could not save Chunk: '.$attributes['name']);
        }
        else {
            $modx->log(modX::LOG_LEVEL_INFO,'Chunk created/updated: '.$attributes['name']);           
        }    
    }
}

//------------------------------------------------------------------------------
//! Plugins
//------------------------------------------------------------------------------
$dir = $pkg_path.'/core/components/'.$package_name.'/elements/plugins/';
$objects = array();
if (file_exists($dir) && is_dir($dir)) {
    $files = glob($dir.'*.php');
    foreach($files as $f) {
        $events = array();
        $content = file_get_contents($f);
        $attributes = get_attributes_from_dox($content);
        if (!isset($attributes['name'])) {
            $name = basename($f,'.php');
            $attributes['name'] = basename($name,'.plugin');
        }
        $Obj = $modx->getObject('modPlugin',array('name'=>$attributes['name']));
        if (!$Obj) {
            $Obj = $modx->newObject('modPlugin');
        }
        if (!isset($attributes['category'])) {
            $attributes['category'] = $Category->get('id'); // Default
        }
        else {
            $attributes['category'] = get_cagtegory_id($attributes['category']);
        }
        // Force Static
        $attributes['static'] = 1;
        $attributes['static_file'] = path_to_rel($f);
        
        // if Events...
        if (isset($attributes['events'])) {
            $event_names = explode(',',$attributes['events']);
            foreach ($event_names as $e) {
                $Event = $modx->newObject('modPluginEvent');
                $Event->set('event',trim($e));
                $events[] = $Event;
            }
        }
        $Obj->fromArray($attributes);
        $Obj->setContent($content);
        $name = $Obj->get('name');
        if (empty($name)) {
            $name = basename($f,'.php');
            $name = basename($name,'.plugin');
            $Obj->set('name',$name);
        }
        $Obj->addMany($events);

        if(!$Obj->save()) {
           $modx->log(modX::LOG_LEVEL_ERROR,'Could not save Plugin: '.$attributes['name']);
        }
        else {
            $modx->log(modX::LOG_LEVEL_INFO,'Plugin created/updated: '.$attributes['name']);           
        }
    }
}

//------------------------------------------------------------------------------
//! Settings
//------------------------------------------------------------------------------
$file = $pkg_path.'/core/components/'.$package_name.'/objects/settings.php';
if (file_exists($file)) {
    $settings = include($file);
    if (is_array($settings)) {
        foreach($settings as $s) {
            if (!isset($s['key'])) {
                $modx->log(modX::LOG_LEVEL_ERROR,'Invalid setting: missing primary key (key)');
                continue;
            }
            $Setting = $modx->getObject('modSystemSetting',array('key'=>$s['key']));
            if (!$Setting) {
                $Setting = $modx->newObject('modSystemSetting');
            }
            else {
                unset($s['value']); // avoid overwriting any existing values
            }
            $Setting->fromArray($s,'',true,true);
            
            if(!$Setting->save()) {
               $modx->log(modX::LOG_LEVEL_ERROR,'Could not save System Setting: '.$s['key']);
            }
            else {
                $modx->log(modX::LOG_LEVEL_INFO,'System Setting created/updated: '.$s['key']);           
            }

        }
    }
    else {
        $modx->log(modX::LOG_LEVEL_ERROR,'settings.php did not contain an array! '.$file);
    }
}



//------------------------------------------------------------------------------
//! Menus
//------------------------------------------------------------------------------
$file = $pkg_path.'/core/components/'.$package_name.'/objects/menus.php';
if (file_exists($file)) {
    $menus = include($file);
    if (is_array($menus)) {
        foreach($menus as $m) {
            if (!isset($m['text'])) {
                $modx->log(modX::LOG_LEVEL_ERROR,'Invalid menu: missing primary key (text)');
                continue;
            }
            $Menu = $modx->getObject('modMenu',array('text'=>$m['text']));
            if (!$Menu) {
                $Menu = $modx->newObject('modMenu');
            }
            $Menu->fromArray($m,'',true,true);

            if (isset($m['Action'])) {
                $modx->log(modX::LOG_LEVEL_INFO,'Attaching an action to menu '.$m['text']); 
                $a = $m['Action'];
                if (!is_array($a)) {
                    $modx->log(modX::LOG_LEVEL_ERROR,'Menu Action must be an array.'); 
                }
                elseif(!isset($a['controller'])) {
                    $modx->log(modX::LOG_LEVEL_ERROR,'Action must specify a controller.'); 
                }
                else {
                    if (!isset($a['namespace'])) {
                        $modx->log(modX::LOG_LEVEL_INFO,'Using default namespace.'); 
                        $a['namespace'] = $package_name;
                    }
                    $Action = $modx->getObject('modAction',array('namespace'=>$a['namespace'],'controller'=>$a['controller']));
                    if (!$Action) {
                        $Action = $modx->newObject('modAction');
                        $modx->log(modX::LOG_LEVEL_ERROR,'Creating new Action.'); 
                    }
                    $Action->fromArray($a,'', true, true);
                    $Menu->addOne($Action);
                }
            }

            if(!$Menu->save()) {
               $modx->log(modX::LOG_LEVEL_ERROR,'Could not save Menu: '.$m['text']);
            }
            else {
                $modx->log(modX::LOG_LEVEL_INFO,'Menu created/updated: '.$m['text']);           
            }
        }
    }
    else {
        $modx->log(modX::LOG_LEVEL_ERROR,'menus.php did not contain an array! '.$file);
    }
}

print "\n";
print "Repoman import complete.\n";

/*EOF*/