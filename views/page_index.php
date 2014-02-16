<form action="<?php print REPOMAN_MGR_URL; ?>" method="post">

	<h4 style="margin-top:20px;">Repo Path</h4>
	
	<?php print $repo_dir_settings; ?>
	
	<h4 style="margin-top:20px;">Cache Settings</h4>

	<?php print $cache_settings; ?>

	<br/>
	<input type="submit" value="Update Settings" />		

</form>

<h2 style="margin-top:20px;">Your Repositories</h2>

<?php print $repos; ?>