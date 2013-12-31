<?php
/**
 * Our Swiss-Army Resolver.
 *
 * We wrap our migrations in this code so the coding interface is cleaner for devs.
 * We write this file to the cache directory so it can get slurped (not executed)
 * when MODX packages it as a resolver.  We do set a line to specify the $namespace
 * of the package.
 *
 * $options contains the Repoman config for this package (including all global config)
 *
 * 
 */
    
$modx =& $transport->xpdo;

$modx->log(1,'Resolver... '.print_r($options,true));

switch ($options[xPDOTransport::PACKAGE_ACTION]) {

    case xPDOTransport::ACTION_INSTALL:
        $install_file = MODX_CORE_PATH.'components/'.$options['namespace'].'/'.$options['migrations_dir'].'/install.php';
        if (file_exists($install_file)) {
            include $install_file;
        }
        break;
        
    case xPDOTransport::ACTION_UPGRADE:
        $dir = MODX_CORE_PATH.'components/'.$options['namespace'].'/'.$options['migrations_dir'].'/';
        if (file_exists($dir) && is_dir($dir)) {
        
        }
        break;
        
    case xPDOTransport::ACTION_UNINSTALL:
        $uninstall_file = MODX_CORE_PATH.'components/'.$options['namespace'].'/'.$options['migrations_dir'].'/uninstall.php';
        if (file_exists($uninstall_file)) {
            include $uninstall_file;
        }
        break;
}

return true;