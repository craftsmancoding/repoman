<form action="<?php print $this->getUrl('home'); ?>" method="post">

	<h4 style="margin-top:20px;">Repository Path</h4>

    <div class="repoman_settings <?php print $class;?>">
        <?php print $msg; ?>
    	<code>MODX_BASE_PATH/</code><input type="text" name="repoman_dir" value="<?php print $this->modx->getOption('repoman.dir'); ?>" size="60"/>
    	<input type="submit" class="repoman_button" value="Update Setting" />	
    </div>
		
</form>


<?php if ($error): ?>

    <p style="width:80%;">Define a directory within your MODx web root which contains your Git repositories for the packages you are working on. Common locations might be <code>packages/</code> or <code>assets/mycomponents/</code></p>

<?php else: ?>
    <div class="repo-wrapper">
    	<h2 style="margin-top:20px;">Your Repositories</h2>
    </div>
    
    <?php print $repos; ?>

<?php endif; ?>
