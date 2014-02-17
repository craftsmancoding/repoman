<?php
$xpdo_meta_map['VariationTerm']= array (
  'package' => 'moxycart',
  'version' => '1.0',
  'table' => 'variation_terms',
  'extends' => 'xPDOObject',
  'fields' => 
  array (
    'vterm_id' => NULL,
    'vtype_id' => NULL,
    'name' => NULL,
    'sku_prefix' => NULL,
    'sku_suffix' => NULL,
    'seq' => NULL,
  ),
  'fieldMeta' => 
  array (
    'vterm_id' => 
    array (
      'dbtype' => 'int',
      'precision' => '11',
      'phptype' => 'integer',
      'null' => false,
      'index' => 'pk',
      'generated' => 'native',
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
    'sku_prefix' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '16',
      'phptype' => 'string',
      'null' => false,
    ),
    'sku_suffix' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '16',
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
        'vterm_id' => 
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
  ),
);
