<?php
/**
 * Our Swiss-Army Resolver.
 *
 * We can use the same resolver for all packages so long as they follow the correct 
 * Repoman syntax.
 *
 * 
 * @param $trasport - the transport class, containing the xpdo instance
 * @param $object - contains the Repoman config for the package being installed 
 *      (including all global config options)
 * @param $options - contains a few other options
 * 
 * @return boolean true on success
 */
    
$modx =& $transport->xpdo;

switch ($options[xPDOTransport::PACKAGE_ACTION]) {

    case xPDOTransport::ACTION_INSTALL:
        $install_file = MODX_CORE_PATH.'components/'.$object['namespace'].'/'.$object['migrations_dir'].'/install.php';
        if (file_exists($install_file)) {
            include $install_file;
        }
        break;
        
    case xPDOTransport::ACTION_UPGRADE:
        $dir = MODX_CORE_PATH.'components/'.$object['namespace'].'/'.$object['migrations_dir'].'/';
        if (file_exists($dir) && is_dir($dir)) {
            $files = glob($dir.'*.php');
            foreach($files as $f) {
                $file = strtolower(basename($f));
                if ($file == 'install.php' || $file == 'uninstall.php') {
                    continue;
                }
                include $f;
            }
        }
        break;
    // We never get here?
    case xPDOTransport::ACTION_UNINSTALL:
        $uninstall_file = MODX_CORE_PATH.'components/'.$object['namespace'].'/'.$object['migrations_dir'].'/uninstall.php';
        if (file_exists($uninstall_file)) {
            include $uninstall_file;
        }
        break;
}

return true;