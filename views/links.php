<a href="<?php print $this->getUrl('view', array('repo'=> $namespace)); ?>" class="repoman_button">View</a>

<?php
//------------------------------------------------------------------------------
// Install
//------------------------------------------------------------------------------
if ($installed):
?>
    <span class="repoman_button_disabled" title="Already installed">Install</span>
<?php
else:
?>
    <span class="repoman_button" onclick="javascript:repo_install('<?php print $namespace; ?>');">Install</span>
<?php
endif;
?>

<?php
//------------------------------------------------------------------------------
// Update
//------------------------------------------------------------------------------
if ($update_available):
?>
    <span class="repoman_button" onclick="javascript:repo_update('<?php print $namespace; ?>');">Update</span>
<?php
else:
?>
    <span class="repoman_button_disabled" title="At latest version">Update</span>
<?php
endif;
?>

<?php
//------------------------------------------------------------------------------
// Uninstall
//------------------------------------------------------------------------------
if ($installed):
?>
    <span class="repoman_button" onclick="javascript:confirm_uninstall('<?php print $namespace; ?>');">Uninstall</span>
<?php
else:
?>
    <span class="repoman_button_disabled" title="Package not installed">Uninstall</span>
<?php
endif;
?>


