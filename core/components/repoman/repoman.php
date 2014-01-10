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
 * php repoman.php <function> [arguments]
 *
 * php repoman.php help
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
        case 'HELP':
            $out = '[46m HELP: '. chr(27).'[0;34m '; //Blue
            break;
        default:
            throw new Exception('Invalid status: ' . $status);
    }
    return "\n".chr(27) . $out . $text .' '. chr(27) . '[0m'."\n\n";
}

//------------------------------------------------------------------------------
//! MAIN
//------------------------------------------------------------------------------


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
require_once dirname(__FILE__).'/model/repoman/repoman.class.php'; 

$modx = new modx();
$modx->initialize('mgr');
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget('ECHO'); 
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
    case 'graph':
        $classname = (isset($argv[2]))? $argv[2] : '';
        unset($argv[0]);
        unset($argv[1]);
        unset($argv[2]);
        $args = Repoman::parse_args($argv);
        try {
            print Repoman::graph($classname, $args);
            exit;
        }  
        catch (Exception $e) {
            print message($e->getMessage(),'ERROR');
            exit(1);
        }
        
        break;
    case 'uninstall':
        // But only the namespace is required... prob'ly safer to require a pkg_path param
    case 'build':
    case 'import':
    case 'install':
    case 'migrate':
    case 'parse':
    case 'export':
        if (!isset($argv[2])) {
            print message('Missing <repo_path> parameter.','ERROR');
            print Repoman::rtfm($function);
            exit(2);
        }
        try {
            $pkg_path = Repoman::get_dir($argv[2]);
        }  
        catch (Exception $e) {
            print message($e->getMessage(),'ERROR');
            exit(3);
        }

        break;
    case 'help':
        if (isset($argv[2])) {
            print message($argv[2],'HELP');
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
$overrides = Repoman::parse_args($argv);

if (!file_exists($pkg_path.'/config.php')) {
    print message('No config.php file detected.','WARNING');
}

$config = Repoman::load_config($pkg_path,$overrides);

// Run time stuff
$modx->setLogLevel($config['log_level']);

try {
    $Repoman = new Repoman($modx,$config);
    print $Repoman->$function($pkg_path);
}  
catch (Exception $e) {
    print message($e->getMessage(),'ERROR');
    exit(4);
}

print message(ucfirst(strtolower($function)) .' complete '.date('Y-m-d H:i:s'),'SUCCESS');

/*EOF*/