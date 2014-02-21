<form action="<?php print $this->getUrl('create'); ?>" method="post">
    
    <label for="namespace">Namespace</label>
    <input type="text" name="namespace" id="namespace" value="" size="20"/>
    <p class="repoman_note">The namespace should be all one word, lowercase, letters and numbers only.</p>

    <label for="package_name">Package Name</label>
    <input type="text" name="package_name" id="package_name" value="" size="60"/>
    <p class="repoman_note">Human readable name of the package.</p>

    <label for="description">Description</label>
    <input type="text" name="description" id="description" value="" size="60"/>
    <p class="repoman_note">What does the package do?</p>

	
	<input type="submit" class="repoman_button" value="Create New Repository" />		

</form>
