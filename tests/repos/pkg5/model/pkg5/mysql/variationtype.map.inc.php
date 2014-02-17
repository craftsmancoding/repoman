<?php
$xpdo_meta_map['VariationType']= array (
  'package' => 'moxycart',
  'version' => '1.0',
  'table' => 'variation_types',
  'extends' => 'xPDOObject',
  'fields' => 
  array (
    'vtype_id' => NULL,
    'name' => NULL,
    'description' => NULL,
    'seq' => NULL,
  ),
  'fieldMeta' => 
  array (
    'vtype_id' => 
    array (
      'dbtype' => 'int',
      'precision' => '11',
      'phptype' => 'integer',
      'null' => false,
      'index' => 'pk',
      'generated' => 'native',
    ),
    'name' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '32',
      'phptype' => 'string',
      'null' => false,
    ),
    'description' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '255',
      'phptype' => 'string',
      'null' => true,
    ),
    'seq' => 
    array (
      'dbtype' => 'int',
      'precision' => '4',
      'phptype' => 'integer',
      'null' => true,
    ),
  ),
  'indexes' => 
  array (
    'PRIMARY' => 
    array (
      'alias' => 'PRIMARY',
      'primary' => true,
      'unique' => true,
      'columns' => 
      array (
        'vtype_id' => 
        array (
          'collation' => 'A',
          'null' => false,
        ),
      ),
    ),
    'name' => 
    array (
      'alias' => 'name',
      'primary' => false,
      'unique' => true,
      'columns' => 
      array (
        'name' => 
        array (
          'collation' => 'A',
          'null' => true,
        ),
      ),
    ),
  ),
  'composites' => 
  array (
    'Terms' => 
    array (
      'class' => 'VariationTerm',
      'local' => 'vtype_id',
      'foreign' => 'vtype_id',
      'cardinality' => 'many',
      'owner' => 'local',
    ),
  ),
);
