<?php
// Block access... just in case
if (!defined('MODX_CORE_PATH')) { die('Unauthorized'); }

/** 
 * RepoMan controller for CMP
 *
 */

define('REPOMAN_MGR_URL', MODX_MANAGER_URL.'?a='.$_GET['a']);
define('REPOMAN_PATH', dirname(__FILE__).'/');

if (!$modx->loadClass('RepoManMgrPage',REPOMAN_PATH.'model/repoman/',true,true)) {
	die('Could not load class RepoManMgrPage');
}
if (!$modx->loadClass('RepoMan',REPOMAN_PATH.'model/repoman/',true,true)) {
	die('Could not load class RepoMan');
}

$Page = new RepoManMgrPage($modx);

$action = 'index';
if (isset($_GET['action'])) {
	$action = $_GET['action'];
} 

return $Page->$action();
 