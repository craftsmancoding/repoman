<style>
	/* In-line Styles for convenience (being a bit lazy here) */
	#repoman_mgr_page{
		margin-top:20px;
	}
	ul.repoman_list {}
	li.repoman_item {}
	div.repoman_error{
		border: 2px dotted red;
		padding: 10px;
		margin-top: 20px;
		background-color: #ff9999;
		width: 70%;
	}
	div.repoman_success{
		border: 2px dotted green;
		padding: 10px;
		margin-top: 20px;
		background-color: #99FF99;
		width: 70%;	
	}
	div.repoman_warning {
		border: 2px dotted #ffcc00;
		padding: 10px;
		margin-top: 20px;
		background-color: #FFFF99;
		width: 70%;
	}

	#repoman_footer {
		margin-top:20px;
	}
	.repoman_label {
		font-weight: bold;
	}
	.repoman_desc {
		font-style: italic;
	}
</style>

<div id="repoman_mgr_page">
	<h2>Repo Man</h2>
	
	<h3><?php print $pagetitle; ?></h3>
	
	<?php print $msg; ?>
	
	<?php print $content; ?>
	
	<div id="repoman_footer">
		&copy; 2013 <a href="http://craftsmancoding.com/">Craftsman Coding</a>
	</div>
</div>