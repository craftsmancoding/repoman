<?php
$xpdo_meta_map['ProductVariationTypes']= array (
  'package' => 'moxycart',
  'version' => '1.0',
  'table' => 'product_variation_types',
  'extends' => 'xPDOSimpleObject',
  'fields' => 
  array (
    'product_id' => NULL,
    'vtype_id' => NULL,
    'name' => NULL,
    'seq' => NULL,
    'is_variant' => 0,
  ),
  'fieldMeta' => 
  array (
    'product_id' => 
    array (
      'dbtype' => 'int',
      'precision' => '11',
      'phptype' => 'integer',
      'null' => false,
    ),
    'vtype_id' => 
    array (
      'dbtype' => 'int',
      'precision' => '11',
      'phptype' => 'integer',
      'null' => false,
    ),
    'name' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '255',
      'phptype' => 'string',
      'null' => false,
    ),
    'seq' => 
    array (
      'dbtype' => 'tinyint',
      'precision' => '3',
      'phptype' => 'integer',
      'null' => true,
    ),
    'is_variant' => 
    array (
      'dbtype' => 'tinyint',
      'precision' => '1',
      'phptype' => 'integer',
      'null' => false,
      'default' => 0,
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
        'id' => 
        array (
          'collation' => 'A',
          'null' => false,
        ),
      ),
    ),
  ),
  'aggregates' => 
  array (
    'Type' => 
    array (
      'class' => 'VariationType',
      'local' => 'vtype_id',
      'foreign' => 'vtype_id',
      'cardinality' => 'one',
      'owner' => 'foreign',
    ),
    'Product' => 
    array (
      'class' => 'Product',
      'local' => 'product_id',
      'foreign' => 'product_id',
      'cardinality' => 'one',
      'owner' => 'foreign',
    ),
  ),
);
