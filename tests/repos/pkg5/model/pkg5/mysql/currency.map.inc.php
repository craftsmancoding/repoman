<?php
$xpdo_meta_map['Currency']= array (
  'package' => 'moxycart',
  'version' => '1.0',
  'table' => 'currencies',
  'extends' => 'xPDOObject',
  'fields' => 
  array (
    'currency_id' => NULL,
    'code' => NULL,
    'name' => NULL,
    'symbol' => NULL,
    'is_active' => 1,
    'seq' => NULL,
  ),
  'fieldMeta' => 
  array (
    'currency_id' => 
    array (
      'dbtype' => 'int',
      'precision' => '4',
      'phptype' => 'integer',
      'null' => false,
      'index' => 'pk',
      'generated' => 'native',
    ),
    'code' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '10',
      'phptype' => 'string',
      'null' => false,
    ),
    'name' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '256',
      'phptype' => 'string',
      'null' => false,
    ),
    'symbol' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '4',
      'phptype' => 'string',
      'null' => true,
    ),
    'is_active' => 
    array (
      'dbtype' => 'tinyint',
      'precision' => '1',
      'attributes' => 'unsigned',
      'phptype' => 'boolean',
      'null' => false,
      'default' => 1,
    ),
    'seq' => 
    array (
      'dbtype' => 'int',
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
        'currency_id' => 
        array (
          'collation' => 'A',
          'null' => false,
        ),
      ),
    ),
  ),
  'composites' => 
  array (
    'Products' => 
    array (
      'class' => 'Product',
      'local' => 'currency_id',
      'foreign' => 'currency_id',
      'cardinality' => 'many',
      'owner' => 'local',
    ),
  ),
);
