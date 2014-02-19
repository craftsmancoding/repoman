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
	table.repoman_table td {
		padding: 10px;
	}
	tr.repoman_row {
		height: 30px;
	}
	td.repoman_name_cell {
		width:400px;
	}
	td.repoman_view_cell {
		width:80px;
	}
	tr.repoman_even {
		background-color: #F8F8F8;
	}
	tr.repoman_odd {
		background-color: #C8C8C8;	
	}
	
	a.repoman_button {
	    appearance: button;
	    -moz-appearance: button;
	    -webkit-appearance: button;
	    text-decoration: none; font: menu; color: ButtonText;
	    display: inline-block; padding: 2px 8px;	
	}
</style>

<div id="repoman_mgr_page">
	<h2><a href="<?php print $this->getUrl('index'); ?>">Repoman</a></h2>
	
	<h3><?php print $pagetitle; ?></h3>
	
	<?php print $msg; ?>
	
	<?php print $content; ?>
	
	<div id="repoman_footer">
		&copy; 2014 <a href="http://craftsmancoding.com/">Craftsman Coding</a>
	</div>
</div>