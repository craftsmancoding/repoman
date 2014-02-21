<?php
//------------------------------------------------------------------------------
//! View
//------------------------------------------------------------------------------
?>
<a href="<?php print $this->getUrl('view', array('repo'=> $namespace)); ?>" class="repoman_button">View</a>

<?php
//------------------------------------------------------------------------------
//! Install
//------------------------------------------------------------------------------
if ($installed):
?>
    <span class="repoman_button_disabled" title="Already installed">Install</span>
<?php
else:
?>
    <span class="repoman_button install-btn" onclick="javascript:repo_install('<?php print $namespace; ?>');" title="Install this package">Install</span>
<?php
endif;
?>

<?php
//------------------------------------------------------------------------------
//! Update
//------------------------------------------------------------------------------
if ($installed && $update_available):
?>
    <span class="repoman_button update-btn" title="Update this package" onclick="javascript:repo_update('<?php print $namespace; ?>','Updating <?php print $namespace; ?>');">Update</span>
<?php
elseif ($installed):
?>
    <span class="repoman_button refresh-btn" title="Refresh the package" onclick="javascript:repo_update('<?php print $namespace; ?>','Refreshing <?php print $namespace; ?>');">Refresh</span>
<?php
else:
?>
    <span class="repoman_button_disabled" title="Package not Installed">Update</span>

<?php
endif;
?>

<?php
//------------------------------------------------------------------------------
//! Uninstall
//------------------------------------------------------------------------------
if ($installed):
?>
    <span class="repoman_button uninstall-btn" title="Uninstall this package" onclick="javascript:confirm_uninstall('<?php print $namespace; ?>');">Uninstall</span>
<?php
else:
?>
    <span class="repoman_button_disabled" title="Package not installed">Uninstall</span>
<?php
endif;
?>

<?php
//------------------------------------------------------------------------------
//! Build
//------------------------------------------------------------------------------
?>
<span class="repoman_button build-btn" title="Build a MODx transport package" onclick="javascript:build_package('<?php print $namespace; ?>');">Build</span>


