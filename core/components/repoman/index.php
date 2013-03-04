<?php
// Block access... just in case
if (!defined('MODX_CORE_PATH')) { die('Unauthorized'); }
/**
 * Sync/Import from project on filesystem
 *
 * 1. Identify project base directory (if not known)
 * 2. Create System settings (if not already created): pkg.core_path, pkg.asset_path, pkg.asset_url
 * 3. Register namespace (if not already registered). Remember: {base_path}, {core_path}, {assets_path} are oddities. No other settings are supported.
 * e.g. {assets_path}components/repoman/core/components/repoman/
 * 4. Scan directory for objects: for each, create or update
 */

/*
// Substitutions
$this->source_dir = str_replace('[+ABSPATH+]', ABSPATH, $this->source_dir);
$this->source_dir = preg_replace('#/$#','',$this->source_dir); // strip trailing slash

// Generate the regex pattern
$exts = explode(',',$this->pattern);
$exts = array_map('trim', $exts);
$exts = array_map('preg_quote', $exts);
$pattern = implode('|',$exts);


// Get the files
$options = array();
foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->source_dir)) as $filename) {
	if (preg_match('/('.$pattern.')$/i',$filename)) {
		// Make the stored file relative to the source_dir
		$options[] = preg_replace('#^'.$this->source_dir.'/#','',$filename);
	}   
}

*/

/*
$repo_dir = $modx->getOption('repoman.repo_dir');

if (empty($repo_dir)) {
	return 'Please set the repoman.repo_dir directory.';
}
if (!file_exists($repo_dir)) {
	return $repo_dir .' does not exist!';
}
if (!is_dir($repo_dir)) {
	return $repo_dir .' must be a directory!';
}

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
 