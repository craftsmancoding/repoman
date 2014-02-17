<?php
$xpdo_meta_map['ProductSpec']= array (
  'package' => 'moxycart',
  'version' => '1.0',
  'table' => 'product_specs',
  'extends' => 'xPDOSimpleObject',
  'fields' => 
  array (
    'product_id' => NULL,
    'spec_id' => NULL,
    'value' => NULL,
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
    'spec_id' => 
    array (
      'dbtype' => 'int',
      'precision' => '11',
      'phptype' => 'integer',
      'null' => false,
    ),
    'value' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '255',
      'phptype' => 'string',
      'null' => false,
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
    'Product' => 
    array (
      'class' => 'Product',
      'local' => 'product_id',
      'foreign' => 'product_id',
      'cardinality' => 'one',
      'owner' => 'foreign',
    ),
    'Spec' => 
    array (
      'class' => 'Spec',
      'local' => 'spec_id',
      'foreign' => 'spec_id',
      'cardinality' => 'one',
      'owner' => 'foreign',
    ),
  ),
);
