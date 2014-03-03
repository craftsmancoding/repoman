return array(
    'type' => '<?php print $array['type']; ?>',
    'name' => '<?php print $array['name']; ?>',
    'caption' => '<?php print $array['caption']; ?>',
    'description' => '<?php print $array['description']; ?>',
    'editor_type' => 0,
    'display' => '<?php print $array['display']; ?>',
    'default_text' => '<?php print $array['default_text']; ?>',
    'properties' => '<?php print $array['properties']; ?>',
    'input_properties' => '<?php print serialize($array['input_properties']); ?>', // serialized
    'output_properties' => '<?php print serialize($array['output_properties']); ?>', // serialized
);