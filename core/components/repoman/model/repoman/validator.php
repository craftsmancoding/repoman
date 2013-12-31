<?php
/**
 * Our Swiss-Army Validator.
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

switch ($options[xPDOTransport::PACKAGE_ACTION]) {

    case xPDOTransport::ACTION_INSTALL:
        break;
        
    case xPDOTransport::ACTION_UPGRADE:
        break;
        
    case xPDOTransport::ACTION_UNINSTALL:
        break;
}

return true;