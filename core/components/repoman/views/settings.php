<?php
/**
 * Custom printing of System Settings (redundant for convenience)
 *
 *
 */
$cache_scripts_yes = '';
$cache_scripts_no = '';
$cache_resource_yes = '';
$cache_resource_no = '';

if ($cache_scripts) {
	$cache_scripts_yes = ' selected="selected"';	
}
else {
	$cache_scripts_no = ' selected="selected"';	
}

if ($cache_resource) {
	$cache_resource_yes = ' selected="selected"';	
}
else {
	$cache_resource_no = ' selected="selected"';	
}

$class = 'repoman_success';
if ($cache_scripts && $cache_resource) {
	$class = 'repoman_error';
}
elseif ($cache_scripts || $cache_resource) {
	$class = 'repoman_warning';
}
//------------------------------------------------------------------------------
?>
<div class="repoman_settings <?php print $class;?>">	
	<form action="<?php print REPOMAN_MGR_URL; ?>" method="post">
		<label class="repoman_label" for="cache_scripts">Cache Scripts?</label>
		<select name="cache_scripts" id="cache_scripts">
			<option value="1"<?php print $cache_scripts_yes; ?>>Yes</option>
			<option value="0"<?php print $cache_scripts_no; ?>>No</option>
		</select>
		<p class="repoman_desc">RepoMan recommends setting this to No. This will ensure that Snippets and Plugins are read from your files.</p>
		
		<label class="repoman_label" for="cache_resource">Cache Resources?</label>
		<select name="cache_resource" id="cache_resource">
			<option value="1"<?php print $cache_resource_yes; ?>>Yes</option>
			<option value="0"<?php print $cache_resource_no; ?>>No</option>
		</select>
		<p class="repoman_desc">RepoMan recommends setting this to No. This will ensure that your Chunks and Templates will be read from your files.</p>
		
		<p class="repoman_extra">These settings are printed here for your convenience. You can also edit them under <a href="<?php print MODX_MANAGER_URL; ?>?a=70">System Settings</a>.  Disabling the cache enables you to develop more easily, but it does make MODX slower.  If you are comfortable with manually clearing the cache, you are free to keep MODX's default caching settings in place.</p>
		<input type="submit" value="Update Settings" />		
	</form>
</div>