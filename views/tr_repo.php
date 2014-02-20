<tr class="repoman_row <?php print $class; ?>">
	<td class="repoman_name_cell">
		<h4><?php print $package_name; ?></h4>
		<p><?php print $description; ?></p>
	</td>
	<td class="repoman_view_cell">
	   
        <?php print $this->get_repo_links($namespace); ?>
        
	</td>
</li>