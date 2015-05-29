<?php
/*------------------------------------------------------------------------------
This file is used as a template for TV definitions.  We need it to generate a 
PHP file.  We could store data as JSON, but PHP is nicer because you can check
for syntax errors and add comments (like this one!)
------------------------------------------------------------------------------*/
?>
return array(
    'type' => '<?php print $array['type']; ?>',
    'name' => '<?php print $array['name']; ?>',
    'caption' => '<?php print $array['caption']; ?>',
    'description' => '<?php print $array['description']; ?>',
    'editor_type' => 0,
    'display' => '<?php print $array['display']; ?>',
    'default_text' => '<?php print $array['default_text']; ?>',
    'properties' => '<?php print serialize($array['properties']); ?>', // serialized
    'input_properties' => '<?php print serialize($array['input_properties']); ?>', // serialized
    'output_properties' => '<?php print serialize($array['output_properties']); ?>', // serialized
);
