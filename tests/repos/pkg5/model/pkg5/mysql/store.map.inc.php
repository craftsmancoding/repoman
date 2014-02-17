<?php
$xpdo_meta_map['Store']= array (
  'package' => 'moxycart',
  'version' => '1.0',
  'extends' => 'modResource',
  'fields' => 
  array (
  ),
  'fieldMeta' => 
  array (
  ),
  'composites' => 
  array (
    'Products' => 
    array (
      'class' => 'Product',
      'local' => 'id',
      'foreign' => 'store_id',
      'cardinality' => 'many',
      'owner' => 'local',
    ),
  ),
);
