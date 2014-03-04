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
    <input type="text" name="namespace" id="namespace" value="<?php print htmlentities($namespace); ?>" size="20"/>
    <p class="repoman_note">The namespace is required, it should be all one word (underscore accepted), lowercase, letters and numbers only. Numbers are not allowed as the first character</p>

    <label for="package_name">Package Name:</label>
    <input type="text" name="package_name" id="package_name" 
        value="<?php print htmlentities($package_name); ?>" 
        size="60"/>
    <p class="repoman_note">Human readable name of the package.</p>

    <label for="description">Description:</label>
    <input type="text" name="description" id="description" value="<?php print htmlentities($description); ?>" size="80"
        placeholder="Tell us what this thing does..."/>
    <p class="repoman_note">What does the package do? (Keep it short)</p>

    <hr/>
    
    <h2>Author</h2>

    <label for="author_name">Author:</label>
    <input type="text" name="author_name" id="author_name" value="<?php print htmlentities($author_name); ?>" size="40"/>
    <p class="repoman_note">Your name.</p>

    <label for="author_email">Email:</label>
    <input type="text" name="author_email" id="author_email" value="<?php print htmlentities($author_email); ?>" size="40"/>
    <p class="repoman_note">Your email address.</p>

    <label for="author_homepage">Homepage:</label>
    <input type="text" name="author_homepage" id="author_homepage" value="<?php print htmlentities($author_homepage); ?>" size="40"/>
    <p class="repoman_note">URL of your homepage.</p>

    <hr/>

<?php
/*    
    <h2>Support</h2>
    
    <label for="support_email">Support Email:</label>
    <input type="text" name="support_email" id="support_email" value="" size="40"/>
    <p class="repoman_note">Email address for support requests.</p>

    <label for="support_issues">Bug Tracker:</label>
    <input type="text" name="support_issues" id="support_issues" value="" size="60"/>
    <p class="repoman_note">URL where users can file bug reports.</p>

    <label for="support_forum">Forum:</label>
    <input type="text" name="support_forum" id="support_forum" value="" size="60"/>
    <p class="repoman_note">URL for a forum where users can discuss this package.</p>

    <label for="support_wiki">Wiki:</label>
    <input type="text" name="support_wiki" id="support_wiki" value="" size="60"/>
    <p class="repoman_note">URL where users can read the documentation for this package.</p>    
*/
?>
    <h2>Export Existing Data</h2>
        <p class="repoman_note">You can move existing objects into your repository by specifying them here.</p>
        
        <label style="width:200px;" for="category_id">Export all Elements in Category</label>
        <select name="category_id">
            <option value=""></option>    
            <?php print $category_options; ?>
        </select>
        <br style="clear:both;"/>
        <label for="settings">System Settings</label>
        <input type="text" name="settings" id="settings" value="<?php print htmlentities($settings); ?>" size="60"/>
        <p class="repoman_note">To export System Settings, list them by name here, comma separated.</p>
        <p class="repoman_note">You can use the "export" command to export other objects into your repository.</p>
    <hr/>
    
    <h2>Sample Data</h2>
        <p class="repoman_note">You can start your repo off with some sample data. 
        You can always add other elements and objects to your repository later, you just need to follow
        Repoman's conventions.</p>
    
        <label for="sample_chunks">Chunks</label>
        <input type="checkbox" name="sample_chunks" id="sample_chunks" value="1" <?php print (isset($_POST['sample_chunks']) && $_POST['sample_chunks']) ? ' checked="checked"' : '' ?>/>
        <p class="repoman_note">Include sample chunks.</p>
    
        <label for="sample_plugins">Plugins</label>
        <input type="checkbox" name="sample_plugins" id="sample_plugins" value="1" <?php print (isset($_POST['sample_plugins']) && $_POST['sample_plugins']) ? ' checked="checked"' : '' ?>/>
        <p class="repoman_note">Include sample plugins.</p>
    
        <label for="sample_snippets">Snippets</label>
        <input type="checkbox" name="sample_snippets" id="sample_snippets" value="1" <?php print (isset($_POST['sample_snippets']) && $_POST['sample_snippets']) ? ' checked="checked"' : '' ?>/>
        <p class="repoman_note">Include sample snippets.</p>
    
        <label for="sample_templates">Templates</label>
        <input type="checkbox" name="sample_templates" id="sample_templates" value="1" <?php print (isset($_POST['sample_templates']) && $_POST['sample_templates']) ? ' checked="checked"' : '' ?>/>
        <p class="repoman_note">Include sample templates.</p>
    
        <label for="sample_tvs">TVs</label>
        <input type="checkbox" name="sample_tvs" id="sample_tvs" value="1" <?php print (isset($_POST['sample_tvs']) && $_POST['sample_tvs']) ? ' checked="checked"' : '' ?>/>
        <p class="repoman_note">Include sample TVs.</p>
    
    	
    	<input type="submit" onclick="return javascript:repo_validate();" class="green-btn" value="Create New Repository" />		
    

    <a href="<?php print $this->getUrl('home'); ?>" class="">&larr; Back</a>
</form>
