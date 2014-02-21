<style>
	.repo-wrapper a,
	.repo-wrapper h2 {
		float: left;
	}
	.repo-wrapper h2 {
		margin-right: 20px;
	}
	.repo-wrapper a {
		margin-top: 23px;
	}

	.create-btn {
		background-color: hsl(80, 46%, 33%) !important;
      background-repeat: repeat-x;
      filter: progid:DXImageTransform.Microsoft.gradient(startColorstr="#90b643", endColorstr="#617a2d");
      background-image: -khtml-gradient(linear, left top, left bottom, from(#90b643), to(#617a2d));
      background-image: -moz-linear-gradient(top, #90b643, #617a2d);
      background-image: -ms-linear-gradient(top, #90b643, #617a2d);
      background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0%, #90b643), color-stop(100%, #617a2d));
      background-image: -webkit-linear-gradient(top, #90b643, #617a2d);
      background-image: -o-linear-gradient(top, #90b643, #617a2d);
      background-image: linear-gradient(#90b643, #617a2d);
      border-color: #617a2d #617a2d hsl(80, 46%, 29%);
      color: #fff !important;
      text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.26);
      -webkit-font-smoothing: antialiased;
      border: none;
      padding: 4px 10px;
		font-size: 12px;
		text-decoration: none;
	}
</style>
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
    	<a class="create-btn" href="<?php print $this->getUrl('create'); ?>" class="repoman_button">Create New Repo</a>
    </div>
    
    <?php print $repos; ?>

<?php endif; ?>