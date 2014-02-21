<script>
function repo_validate() {
  var ns = document.getElementById("namespace").value;
  var ns_ex=/^[a-z][a-z0-9_]*$/ //regular expression defined fro namespace
  if (!ns_ex.test(ns)) {
    alert('Please check your namespace value.'); 
    return false;
  }
}
</script>
<form class="createrepo" action="<?php print $this->getUrl('create'); ?>" method="post">
    
    <h2>General</h2>
    
    <label for="namespace">Namespace: </label>
    <input type="text" name="namespace" id="namespace" value="" size="20"/>
    <p class="repoman_note">The namespace is required, it should be all one word (underscore accepted), lowercase, letters and numbers only. Numbers are not allowed as first Character</p>

    <label for="package_name">Package Name:</label>
    <input type="text" name="package_name" id="package_name" value="" size="60"/>
    <p class="repoman_note">Human readable name of the package.</p>

    <label for="description">Description:</label>
    <input type="text" name="description" id="description" value="" size="80"/>
    <p class="repoman_note">What does the package do? (Keep it short)</p>

    <hr/>
    
    <h2>Author</h2>

    <label for="author_name">Author:</label>
    <input type="text" name="author_name" id="author_name" value="" size="40"/>
    <p class="repoman_note">Your name.</p>

    <label for="author_email">Email:</label>
    <input type="text" name="author_email" id="author_email" value="" size="40"/>
    <p class="repoman_note">Your email address.</p>

    <label for="author_homepage">Homepage:</label>
    <input type="text" name="author_homepage" id="author_homepage" value="" size="40"/>
    <p class="repoman_note">URL of your homepage.</p>

    <hr/>
    
    <h2>Support</h2>
    
    <label for="support_email">Support Email:</label>
    <input type="text" name="support[email]" id="support_email" value="" size="40"/>
    <p class="repoman_note">Email address for support requests.</p>

    <label for="support_issues">Bug Tracker:</label>
    <input type="text" name="support[issues]" id="support_issues" value="" size="60"/>
    <p class="repoman_note">URL where users can file bug reports.</p>

    <label for="support_forum">Forum:</label>
    <input type="text" name="support[forum]" id="support_forum" value="" size="60"/>
    <p class="repoman_note">URL for a forum where users can discuss this package.</p>

    <label for="support_wiki">Wiki:</label>
    <input type="text" name="support[wiki]" id="support_wiki" value="" size="60"/>
    <p class="repoman_note">URL where users can read the documentation for this package.</p>    

	
	<input type="submit" onclick="return repo_validate();" class="green-btn" value="Create New Repository" />		


    <a href="<?php print $this->getUrl('home'); ?>" class="">&larr; Back</a>
</form>
