<?php
/*
Used to select the repo_dir System Setting

e.g. {base_path}repos/

NOTE: posting data would convert a name like repoman.repo_dir to repoman_repo_dir
*/
$class = 'repoman_success';
if (empty($repo_dir)) {
	$class='repoman_error';

}
?>

<div class="repoman_settings <?php print $class;?>">
	<label class="repoman_label">Repo Directory</label>
	<select name="repoman_repo_dir" id="repoman_repo_dir">
		<option value="">Please Select</option>
		<?php print $options; ?>
	</select>
	<p class="repoman_desc">This is a folder inside your web root where you clone repositories.</p>
</div>