<?php
/**
 * Example Install Validator
 *
 * If you create a file named install.php and place it in your core/components/<namespace>/tests directory
 * the code will be executed *before* your package is installed.  That means that when this bit of code
 * runs, your package's files have not yet been copied to the local MODX directories where you are
 * installing the package.
 *
 * You CAN NOT include other files from this file!
 *
 * See the 'abort_install_on_fail' configuration option: if true, returning false from this validator will
 * trigger package installation to stop with a generic error. Verbosity however is a problem: the error
 * does not include any specific information and anything you send here to the MODX log is not displayed
 * in the console.
 *
 * A more verbose behavior (albeit less graceful) is to send error messages to the PHP log file via error_log()
 * and hope that someone will discover the problem there.  See https://github.com/modxcms/revolution/issues/771
 *
 * @param object $transport contains the xpdo reference
 * @param array  $options   useful primarily for determining whether the current action is install or update,
 *                          but it also contains stuff like the license and readme file and some Ext JS stuff
 * @param array  $object    is a copy of your Repman package config.php -- the variable is unfortunately named
 *                          "$object" even though it's an array. This is a good place for you to read custom
 *                          configuration options like the a minimum PHP version (see the below example).
 *
 * @return boolean true on success, false on fail
 */

$modx =& $transport->xpdo;

$out = true;
$running_msg = '';
switch ($options[xPDOTransport::PACKAGE_ACTION]) {
    case xPDOTransport::ACTION_INSTALL:
        $modx_version = $modx->getOption('settings_version');
        if (version_compare($modx_version, $object['min_version_modx'], '<')) {
            $msg = 'The ' . $object['package_name'] . ' package requires at least MODX version ' . $object['min_version_modx'];
            error_log($msg);
            $modx->log(modX::LOG_LEVEL_ERROR, $msg);
            $running_msg .= ' ' . $msg;
            $out = false;
        }

        if (version_compare(phpversion(), $object['min_version_php'], '<')) {
            $msg = 'The ' . $object['package_name'] . ' package requires at least PHP version ' . $object['min_version_php'];
            error_log($msg);
            $modx->log(modX::LOG_LEVEL_ERROR, $msg);
            $running_msg .= ' ' . $msg;
            $out = false;
        }
        // Dumb workaround for issue #771 by hijacking the lexicon.
        // Warning: this leaves the package_err_install string in an unusable state.
        if (!$out) {
            $language = $modx->getOption('manager_language');
            $params = array('name' => 'package_err_install', 'topic' => 'workspace', 'namespace' => 'core', 'language' => $language);
            $LE = $modx->getObject('modLexiconEntry', $params);
            if (!$LE) {
                $LE = $modx->newObject('modLexiconEntry');
                $LE->fromArray($params);
            }
            $LE->set('value', 'Could not install package with signature: [[+signature]]' . $running_msg);
            $LE->save();
            $modx->cacheManager->clearCache(array('lexicon_topics/'));
        }
        break;

    case xPDOTransport::ACTION_UPGRADE:
        break;
    case xPDOTransport::ACTION_UNINSTALL:
        break;
}

return $out;

/*EOF*/