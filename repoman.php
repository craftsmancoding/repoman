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
function usage() {
    print "Usage:\n";
    print "php repoman.php /path/to/modx/assets/repos/mypkg\n";
}

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

// Create/Update the package.core_path setting (if not set already)
$key = $package_name .'.core_path';

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


// Run any migrations
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
}



/*EOF*/