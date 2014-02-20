<form action="<?php print $this->getUrl('home'); ?>" method="post">

	<h4 style="margin-top:20px;">Repository Path</h4>

    <div class="repoman_settings <?php print $class;?>">
        <?php print $msg; ?>
    	<code>MODX_BASE_PATH/</code><input type="text" name="repoman_dir" value="<?php print $this->modx->getOption('repoman.dir'); ?>" size="60"/>
    </div>
	<br/>
	
	<input type="submit" value="Update Settings" />		

</form>

<h2 style="margin-top:20px;">Your Repositories</h2>

<?php print $repos; ?>