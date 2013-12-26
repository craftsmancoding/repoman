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
 * Colorize text for cleaner CLI UX. 
 * TODO: Windows compatible?
 *
 * Adapted from 
 * http://softkube.com/blog/generating-command-line-colors-with-php/
 * http://www.if-not-true-then-false.com/2010/php-class-for-coloring-php-command-line-cli-scripts-output-php-output-colorizing-using-bash-shell-colors/
 * 
 * @param string $text
 * @param string $status
 * @return string
 */
function message($text, $status) {
    $out = '';
    // 
    switch($status) {
        case 'SUCCESS':
            $out = '[42m SUCCESS: '.chr(27).'[0;32m '; //Green background
            break;
        case 'ERROR':
            $out = '[41m ERROR: '. chr(27).'[0;31m '; //Red
            break;
        case 'WARNING':
            $out = '[43m WARNING: '; //Yellow background
            break;
        case 'INFO':
            $out = '[46m NOTE: '. chr(27).'[0;34m '; //Blue
            break;
        default:
            throw new Exception('Invalid status: ' . $status);
    }
    return "\n".chr(27) . $out . $text .' '. chr(27) . '[0m'."\n\n";
}

//------------------------------------------------------------------------------
//! MAIN
//------------------------------------------------------------------------------
require_once dirname(__FILE__).'/model/repoman/repoman.class.php'; 

if (php_sapi_name() !== 'cli') {
    error_log('Repoman CLI script can only be executed from the command line.');
    die('CLI access only.');
}

// Find MODX...

// As long as this script is built placed inside a MODX docroot, this will sniff out
// a valid MODX_CORE_PATH.  This will effectively force the MODX_CONFIG_KEY too.
// The config key controls which config file will be loaded. 
// Syntax: {$config_key}.inc.php
// 99.9% of the time this will be "config", but it's useful when dealing with
// dev/prod pushes to have a config.inc.php and a prod.inc.php, stg.inc.php etc.
$dir = '';
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
            print message("Could not find a valid MODX config.core.php file.\n"
            ."Make sure your repo is inside a MODX webroot and try again.",'ERROR');
            die(1);
        }
	}
}


if (!defined('MODX_CORE_PATH') || !defined('MODX_CONFIG_KEY')) {    
    print message("Could not load MODX.\n"
    ."MODX_CORE_PATH or MODX_CONFIG_KEY undefined in\n"
    ."{$dir}/config.core.php",'ERROR');
    die(2);
}

if (!file_exists(MODX_CORE_PATH.'model/modx/modx.class.php')) {
    print message("modx.class.php not found at ".MODX_CORE_PATH,'ERROR');
    die(3);
}


// fire up MODX
require_once MODX_CORE_PATH.'config/'.MODX_CONFIG_KEY.'.inc.php';
require_once MODX_CORE_PATH.'model/modx/modx.class.php';

$modx = new modx();
$modx->initialize('mgr');
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget('ECHO'); 
/*
$xpdo->setLogTarget(array(
   'target' => 'FILE',
   'options' => array(
       'filename' => 'install.' . strftime('%Y-%m-%dT%H:%M:%S')
    )
));
*/
flush();

// Get req'd <function> parameter
if (!isset($argv[1])) {
    print message('Missing required <function> parameter.','INFO');
    print Repoman::rtfm('usage');
    exit(1);
}




// Disambiguation:
$function = strtolower($argv[1]);
$pkg_path = '';
switch ($function) {
    case 'build':
    case 'import':
    case 'install':
    case 'migrate':
    case 'seed':
    case 'uninstall':
        if (!isset($argv[2])) {
            print message('Missing <pkg_path> parameter.','ERROR');
            print Repoman::rtfm($function);
            exit(2);
        }
        try {
            $pkg_path = Repoman::getdir($argv[2]);
        }  
        catch (Exception $e) {
            print message($e->getMessage(),'ERROR');
        }

        break;
    case 'help':
        if (isset($argv[2])) {
            print Repoman::rtfm($argv[2]);
        }
        else {
            print Repoman::rtfm('usage');   
        }
        exit();
    default:
        print message('Unknown function','ERROR');
        print Repoman::rtfm('usage');
        exit(1);
}

// Interpret any command-line run-time arguments
// eg. php repoman.php build <pkg_path> --pkg_name=Something
unset($argv[0]);
unset($argv[2]);
$overrides = array();
foreach($argv as $a) {
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

$config = Repoman::load_config($pkg_path,$overrides);

// Run time stuff
$modx->setLogLevel($config['log_level']);

try {
    $Repoman = new Repoman($modx,$config);
    $Repoman->$function($pkg_path);
}  
catch (Exception $e) {
    print message($e->getMessage(),'ERROR');
}

print message($function .' complete.','SUCCESS');
exit;



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
$rel_path = str_replace(MODX_BASE_PATH,'',$pkg_path); // convert path to url
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
        $attributes = Repoman::repossess($content);
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
//! Objects (General)
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
// ! Objects (general)
//------------------------------------------------------------------------------
$dir = $pkg_path.'/core/components/'.$package_name_lower.'/database/objects/';
if (file_exists($dir) && is_dir($dir)) {
    $modx->log(modX::LOG_LEVEL_INFO,'Crawling directory '.$dir); 
    $files = glob($dir.'*.php');
    foreach($files as $f) {
        $classname = basename($f,'.php');
        $fields = $modx->getFields($classname);
        if (empty($fields)) {
            $modx->log(modX::LOG_LEVEL_ERROR,'Unrecognized object classname: '.$classname); 
            continue;
        }
        $data = include $f;
        if (!is_array($data)) {
            $modx->log(modX::LOG_LEVEL_ERROR,'Data in '.$f.' not an array.');
            continue; 
        }

        $aggs = $modx->getAggregates($classname);
        $comps = $modx->getComposites($classname);

        // Loop through each object defined in the array in this file
        $modx->log(modX::LOG_LEVEL_INFO,'Adding '.$classname.' objects from file.');
        foreach ($data as $d) {

            // Object already exists?
            $obj = $modx->getObject($classname, get_criteria($classname,$d));
            if (!$obj) {
                $modx->log(modX::LOG_LEVEL_INFO,'Creating new '.$classname.' object.');
                $obj = $modx->newObject($classname);
            }
            else {
                $pk = $modx->getPK($classname);
                if ($pk) {
                    if (!is_array($pk)) {
                        $modx->log(modX::LOG_LEVEL_INFO,'Updating existing '.$classname.' object ('.$obj->get($pk).')');
                    }
                    else {
                        $modx->log(modX::LOG_LEVEL_INFO,'Updating existing '.$classname.' object.');
                    }
                }
            }
            $obj->fromArray($d);

            // Add Aggregates
            foreach ($aggs as $a => $def) {  
                if (isset($d[$a])) {
                    if ($def['cardinality'] == 'many') {
                        $many = array();
                        if (is_array($d[$a])) {
                            foreach ($d[$a] as $rel) {
                                $Related = $modx->newObject($def['class']);
                                $Related->fromArray($rel);
                                $modx->log(modX::LOG_LEVEL_INFO,'Adding aggregate '.$def['class']);
                                // TODO: Wormhole!!!
                                $many[] = $Related;
                            }
                            $obj->addMany($manys);
                        }
                        else {
                            $Related = $modx->newObject($def['class']);
                            $Related->fromArray($d[$a]);
                            $modx->log(modX::LOG_LEVEL_INFO,'Adding aggregate '.$def['class']);
                            // TODO: Wormhole!!!
                            $obj->addMany(array($Related));
                        }
                    }
                    elseif ($def['cardinality'] == 'one') {
                        if (!is_array($d[$a])) {
                            $Related = $modx->newObject($def['class']);
                            $Related->fromArray($d[$a]);
                            $modx->log(modX::LOG_LEVEL_INFO,'Adding aggregate '.$def['class']);
                            // TODO: Wormhole!!!
                            $obj->addOne($Related);
                        }
                    }
                    else {
                        $modx->log(modX::LOG_LEVEL_ERROR,'Incompatible cardinality for '.$a);
                        continue; 
                    }
                } 
            }


            // Add Composites
            foreach ($comps as $c => $def) {  
                if (isset($d[$c])) {
                    $many = array();
                    if ($def['cardinality'] == 'many') {
                        $many = array();
                        if (is_array($d[$c])) {
                            foreach ($d[$c] as $rel) {
                                $Related = $modx->newObject($def['class']);
                                $Related->fromArray($rel);
                                $modx->log(modX::LOG_LEVEL_INFO,'Adding aggregate '.$def['class']);
                                // TODO: Wormhole!!!
                                $many[] = $Related;
                            }
                            $obj->addMany($manys);
                        }
                        else {
                            $Related = $modx->newObject($def['class']);
                            $Related->fromArray($d[$c]);
                            $modx->log(modX::LOG_LEVEL_INFO,'Adding aggregate '.$def['class']);
                            // TODO: Wormhole!!!
                            $obj->addMany(array($Related));
                        }
                    }
                    elseif ($def['cardinality'] == 'one') {
                        if (!is_array($d[$c])) {
                            $Related = $modx->newObject($def['class']);
                            $Related->fromArray($d[$c]);
                            $modx->log(modX::LOG_LEVEL_INFO,'Adding aggregate '.$def['class']);
                            // TODO: Wormhole!!!
                            $obj->addOne($Related);
                        }
                    }
                    else {
                        $modx->log(modX::LOG_LEVEL_ERROR,'Incompatible cardinality for '.$c);
                        continue; 
                    }
                } 
            }
            
            if($obj->save()) {
                $modx->log(modX::LOG_LEVEL_INFO,'Saved '.$def['class']);
            }
            else {
                $modx->log(modX::LOG_LEVEL_ERROR,'Could not save '.$def['class']);
            }
        }
                
        

        
        

        foreach ($comps as $c => $def) {
            if (isset($fields[$c])) {
                 
            }        
        }
        
    }
}
else {
    $modx->log(modX::LOG_LEVEL_INFO, "No General Objects found. Directory does not exist: $dir");
}

exit;

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