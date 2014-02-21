<style>
	/* In-line Styles for convenience (being a bit lazy here) */
	#repoman_mgr_page{
		margin-top:20px;
	}

	#repoman_content form {
		margin-bottom: 20px;
	}

	#repoman_mgr_page ol,
	#repoman_mgr_page ul {
		margin: 1em !important;
		padding: 1em !important;
	}
	
	#repoman_mgr_page ul {
		list-style : disc !important;
	}
	#repoman_mgr_page ol {
		list-style : decimal !important;
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
	
	.repoman_table {
		border-collapse: collapse;
		width: 98%;
	}
	#repoman_footer {
		margin: 20px auto;
		width: 320px;
	}

	#repoman_footer a {
		width: 60px;
		float: left;
		text-align: center;
		margin-right: 20px;
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

	td.repoman_view_cell {
		padding-right: 20px !important;
	}

	td.repoman_view_cell a,
	td.repoman_view_cell span  {
		margin-right: 5px;
	}
	tr.repoman_row {
		height: 30px;
	}
	td.repoman_name_cell {
		width:400px;
	}

	tr.repoman_even {
		background-color: #F8F8F8;
	}
	tr.repoman_odd {
		background-color: #C8C8C8;	
	}
	
	.repoman_button {
        cursor:pointer;
	    appearance: button;
	    -moz-appearance: button;
	    -webkit-appearance: button;
	    text-decoration: none; 
	    font: menu; 
	    color: ButtonText;
	    display: inline; 
	    padding: 4px 8px;
	    color: black;
	    background-color: #dadada;
	}

	.repoman_button_disabled {
	    appearance: button;
	    -moz-appearance: button;
	    -webkit-appearance: button;
	    text-decoration: none; 
	    font: menu; 
	    color: ButtonText;
	    display: inline; 
	    padding: 4px 8px;
	    color: #C8C8C8;
	    background-color: grey;
	    cursor: not-allowed;
	}
	

	a.repoman_discrete {
	   text-decoration: none;
	   color: #72C272;
	}
	a.repoman_discrete:visited {
	   color:#72C272;
	}

	.install-btn {
		background: #5ebb00;
	}

	.uninstall-btn {
		background:#bf0707;
		color: #fff;
	}

	.update-btn {
		background: #ff8000;
	}

	.copyright {
		float: none;
		display: block;
		width: 100% !important;
		color: #72C272;
		margin-top: 15px;
		text-decoration: none;
	}
</style>

<script>
function confirm_uninstall(namespace) {
    Ext.Msg.confirm('Confirm Uninstall', "Are you sure you want to uninstall this package? This will not touch any files inside your repository, but it will try to remove any of the package's objects and data from the local MODx install.  This cannot be undone.", function(buttonText) {
    	if (buttonText == "yes") {
            console.log('Repoman: Uninstalling '+namespace);
            
            var loadMask = new Ext.LoadMask(Ext.get('repoman_content'), {msg:'Installing '+namespace});
            loadMask.show();
                        
            Ext.Ajax.request({
                params: {namespace: namespace},
                url: "<?php print $this->getUrl('ajax', array('f'=>'uninstall')); ?>", 
                success: function (resp) {
                    var data;
                    data = Ext.decode(resp.responseText);
                    if (data.success === true) {
                        Ext.MessageBox.alert('Success', data.msg);
                        setTimeout(function () {
                            location.reload(); // will reload newly installed data
                        }, 1000);
                    } else {
                        Ext.MessageBox.alert('Error', data.msg);
                    }
                    loadMask.hide();
                },
                failure: function () {
                    Ext.MessageBox.alert('Error', 'A problem occurred.');
                }
            });
        }
    });
}

/**
 * Update a given package identified by its namespace (i.e. by its sub-folder)
 * @param string namespace
 */
function repo_update(namespace) {

    var loadMask = new Ext.LoadMask(Ext.get('repoman_content'), {msg:'Installing '+namespace});
    loadMask.show();
    
	Ext.Ajax.request({
        params: {namespace: namespace},
        url: "<?php print $this->getUrl('ajax', array('f'=>'update')); ?>", 
        success: function (resp) {
            var data;
            data = Ext.decode(resp.responseText);
            if (data.success === true) {
                Ext.MessageBox.alert('Success', data.msg);
                setTimeout(function () {
                    location.reload(); // will reload newly installed data
                }, 1000);
            } else {
                Ext.MessageBox.alert('Error', data.msg);
            }
            loadMask.hide();
        },
        failure: function () {
            Ext.MessageBox.alert('Error', 'A problem occurred.');
        }
    });

}

/**
 * Install a given package identified by its namespace (i.e. by its sub-folder)
 * @param string namespace
 */
function repo_install(namespace) {
    Ext.Msg.confirm('Confirm Install', "You should only install packages from providers you trust: the installation process executes code on your site and has access to your local database.  Are you sure you want to install this package?", function(buttonText) {
    	if (buttonText == "yes") {
            console.log('Repoman: installing '+namespace);
            
            var loadMask = new Ext.LoadMask(Ext.get('repoman_content'), {msg:'Installing '+namespace});
            loadMask.show();
            
            Ext.Ajax.request({
                params: {namespace: namespace},
                url: "<?php print $this->getUrl('ajax', array('f'=>'install')); ?>", 
                success: function (resp) {
                    var data;
                    data = Ext.decode(resp.responseText);
                    if (data.success === true) {
                        Ext.MessageBox.alert('Success', data.msg);
                        setTimeout(function () {
                            location.reload(); // will reload newly installed data
                        }, 1000);
                    } else {
                        Ext.MessageBox.alert('Error', data.msg);
                    }
                    loadMask.hide();
                },
                failure: function () {
                    Ext.MessageBox.alert('Error', 'A problem occurred.');
                }
                
            });
    	}
    });    
}

</script>
<div id="repoman_mgr_page">
    <!-- see http://webcodertools.com/imagetobase64converter/Create -->
	<div style="background-image: url(data:image/jpeg;base64,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); background-repeat:no-repeat; background-color:#1E1A17; height:66px;"><h2 style="padding-left: 100px; padding-top:20px;"><a href="<?php print $this->getUrl('home'); ?>" style="color:#72C272; text-decoration: none;">Repoman</a> <?php print $pagetitle; ?></h2>
	   </div>
	   
	<?php print $msg; ?>
	
	<div id="repoman_content">
	<?php print $content; ?>
	</div>
	
	<div id="repoman_footer">

		<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=RYA32APBJRYBA" class="repoman_discrete"><img alt="" src="data:image/png;base64,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" />Donate</a>
		<a href="https://github.com/craftsmancoding/repoman/wiki" class="repoman_discrete"><img alt="" src="data:image/png;base64,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" />Docs</a>
		<a href="http://forums.modx.com/" class="repoman_discrete"><img alt="" src="data:image/png;base64,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" />Forum</a>
		<a href="https://github.com/craftsmancoding/repoman/issues" class="repoman_discrete"><img alt="" src="data:image/png;base64,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" />Bugs</a>

        <a  class="copyright" href="http://craftsmancoding.com/" class="repoman_discrete">&copy; 2014 Craftsman Coding</a>
	</div>
</div>