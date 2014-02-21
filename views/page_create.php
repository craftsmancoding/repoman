<style>
    form {
        background: #fff;
        padding: 20px;
    }
    label {
        width: 110px;
        float: left;
        font-weight: bold;
        margin-top: 4px;
    }

    input[type="text"] {
        border: 1px solid #ddd;
        padding: 4px;
    }
    .repoman_note {
        color: #909090;
        font-size: 11px;
        font-style: italic;
        margin-bottom: 10px;
    }

    .repoman_button {
      background-color: hsl(80, 46%, 33%) !important;
      background-repeat: repeat-x;
      filter: progid:DXImageTransform.Microsoft.gradient(startColorstr="#90b643", endColorstr="#617a2d");
      background-image: -khtml-gradient(linear, left top, left bottom, from(#90b643), to(#617a2d));
      background-image: -moz-linear-gradient(top, #90b643, #617a2d);
      background-image: -ms-linear-gradient(top, #90b643, #617a2d);
      background-image: -webkit-gradient(linear, left top, left bottom, color-stop(0%, #90b643), color-stop(100%, #617a2d));
      background-image: -webkit-linear-gradient(top, #90b643, #617a2d);
      background-image: -o-linear-gradient(top, #90b643, #617a2d);
      background-image: linear-gradient(#90b643, #617a2d);
      border-color: #617a2d #617a2d hsl(80, 46%, 29%);
      color: #fff !important;
      text-shadow: 0 -1px 0 rgba(0, 0, 0, 0.26);
      -webkit-font-smoothing: antialiased;
      border: none;
    }
</style>
<form action="<?php print $this->getUrl('create'); ?>" method="post">
    
    <label for="namespace">Namespace: </label>
    <input type="text" name="namespace" id="namespace" value="" size="20"/>
    <p class="repoman_note">The namespace should be all one word, lowercase, letters and numbers only.</p>

    <label for="package_name">Package Name:</label>
    <input type="text" name="package_name" id="package_name" value="" size="60"/>
    <p class="repoman_note">Human readable name of the package.</p>

    <label for="description">Description:</label>
    <input type="text" name="description" id="description" value="" size="60"/>
    <p class="repoman_note">What does the package do?</p>

	
	<input type="submit" class="repoman_button" value="Create New Repository" />		

</form>
