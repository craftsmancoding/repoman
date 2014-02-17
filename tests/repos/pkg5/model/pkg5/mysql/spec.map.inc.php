<?php
$xpdo_meta_map['Spec']= array (
  'package' => 'moxycart',
  'version' => '1.0',
  'table' => 'specs',
  'extends' => 'xPDOObject',
  'fields' => 
  array (
    'spec_id' => NULL,
    'identifier' => NULL,
    'name' => NULL,
    'description' => NULL,
    'seq' => NULL,
    'group' => NULL,
    'type' => 'text',
  ),
  'fieldMeta' => 
  array (
    'spec_id' => 
    array (
      'dbtype' => 'int',
      'precision' => '11',
      'phptype' => 'integer',
      'null' => false,
      'index' => 'pk',
      'generated' => 'native',
    ),
    'identifier' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '32',
      'phptype' => 'string',
      'null' => false,
      'comment' => 'Lowercase slug',
    ),
    'name' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '64',
      'phptype' => 'string',
      'null' => false,
      'comment' => 'Human readable, translated.',
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
      'dbtype' => 'tinyint',
      'precision' => '3',
      'phptype' => 'integer',
      'null' => true,
    ),
    'group' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '32',
      'phptype' => 'string',
      'null' => true,
    ),
    'type' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '32',
      'phptype' => 'string',
      'null' => false,
      'default' => 'text',
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
        'spec_id' => 
        array (
          'collation' => 'A',
          'null' => false,
        ),
      ),
    ),
    'identifier' => 
    array (
      'alias' => 'identifier',
      'primary' => false,
      'unique' => true,
      'type' => 'BTREE',
      'columns' => 
      array (
        'identifier' => 
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
      'class' => 'ProductSpec',
      'local' => 'spec_id',
      'foreign' => 'spec_id',
      'cardinality' => 'many',
      'owner' => 'local',
    ),
  ),
);
