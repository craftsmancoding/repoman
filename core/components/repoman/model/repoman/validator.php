<?php
/**
 * Our Swiss-Army Validator.
 *
 * All projects can use the same basic validator if they follow Repoman's conventions.
 *
 * $options contains the Repoman config for this package (including all global config)
 * The xPDOTransport::ABORT_INSTALL_ON_VEHICLE_FAIL package parameter is controlled by the
 * 'abort_install_on_fail' configuration option.  The default is true so that package installation
 * can be halted if the validation tests do not pass.
 *
 * @return boolean true on success, false on fail
 */
    
$modx =& $transport->xpdo;


//$modx->log(1, 'Fatal error...');
//return false;

switch ($options[xPDOTransport::PACKAGE_ACTION]) {

    case xPDOTransport::ACTION_INSTALL:
        $install_file = MODX_CORE_PATH.'components/'.$object['namespace'].'/'.$object['validators_dir'].'/install.php';
        if (file_exists($install_file)) {
            include $install_file;
        }

        break;
        
    case xPDOTransport::ACTION_UPGRADE:
        break;
        
}
return true;