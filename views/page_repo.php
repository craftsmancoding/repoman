<script src="https://ajax.googleapis.com/ajax/libs/jquery/2.0.2/jquery.min.js"></script>
<script src="//ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js"></script>
<script>
    $(function(){
	    $("#toggle-repo_info").click(function () {
	        var effect = 'slide';
	        var options = { direction: 'right' };
	        var duration = 700;
	        $('#repo_info').toggle(effect, options, duration);
	    });

    })
</script>
<a id="toggle-repo_info">
    <div class="line">--</div>
    <div class="line">--</div>
    <div class="line">--</div>
</a>
<div id="repo_info">
    <?php print $info; ?>
</div>
<div id="repo_readme">
	<h2 class="readme">Readme</h2>
    <?php print $readme; ?>
</div>

<div style="float:right;margin-top:20px;margin-right:20px;">
	<a href="<?php print $this->getUrl('home'); ?>" class="repoman_button">&larr; Back</a>
</div>