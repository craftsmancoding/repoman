<?php
$xpdo_meta_map['Taxonomy']= array (
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
    'Terms' => 
    array (
      'class' => 'Term',
      'local' => 'id',
      'foreign' => 'parent',
      'cardinality' => 'many',
      'owner' => 'local',
    ),
  ),
  'validation' => 
  array (
    'rules' => 
    array (
      'parent' => 
      array (
        'parent' => 
        array (
          'type' => 'xPDOValidationRule',
          'rule' => 'TaxonomyParents',
          'message' => 'Invalid parent',
        ),
      ),
    ),
  ),
);
