<form action="<?php print $this->getUrl('home'); ?>" method="post">

	<h4 style="margin-top:20px;">Repository Path</h4>

    <div class="repoman_settings <?php print $class;?>">
        <?php print $msg; ?>
    	<code>MODX_BASE_PATH/</code><input type="text" name="repoman_dir" value="<?php print $this->modx->getOption('repoman.dir'); ?>" size="60"/>
    	<input type="submit" class="repoman_button" value="Update Setting" />	
    </div>
		

</form>
<div class="repo-wrapper">
	<h2 style="margin-top:20px;">Your Repositories</h2>
	<a class="green-btn" href="<?php print $this->getUrl('create'); ?>" class="repoman_button">Create New Repo</a>
</div>


<?php print $repos; ?>