<?php
$xpdo_meta_map['Product']= array (
  'package' => 'moxycart',
  'version' => '1.0',
  'table' => 'products',
  'extends' => 'xPDOObject',
  'fields' => 
  array (
    'product_id' => NULL,
    'store_id' => NULL,
    'parent_id' => NULL,
    'template_id' => NULL,
    'currency_id' => NULL,
    'name' => NULL,
    'title' => NULL,
    'description' => NULL,
    'content' => '',
    'type' => NULL,
    'sku' => NULL,
    'sku_vendor' => NULL,
    'variant_matrix' => NULL,
    'alias' => NULL,
    'uri' => NULL,
    'track_inventory' => 0,
    'qty_inventory' => NULL,
    'qty_alert' => NULL,
    'qty_min' => NULL,
    'qty_max' => NULL,
    'qty_backorder_max' => NULL,
    'price' => NULL,
    'price_strike_thru' => NULL,
    'price_sale' => NULL,
    'sale_start' => NULL,
    'sale_end' => NULL,
    'category' => NULL,
    'image_id' => NULL,
    'is_active' => 1,
    'in_menu' => 1,
    'billing_unit' => NULL,
    'billing_interval' => 1,
    'duration_unit' => NULL,
    'duration_interval' => 1,
    'user_group_id' => NULL,
    'role_id' => NULL,
    'payload_id' => NULL,
    'author_id' => NULL,
    'timestamp_created' => 'CURRENT_TIMESTAMP',
    'timestamp_modified' => NULL,
    'seq' => NULL,
  ),
  'fieldMeta' => 
  array (
    'product_id' => 
    array (
      'dbtype' => 'int',
      'precision' => '11',
      'phptype' => 'integer',
      'null' => false,
      'index' => 'pk',
      'generated' => 'native',
    ),
    'store_id' => 
    array (
      'dbtype' => 'int',
      'precision' => '11',
      'phptype' => 'integer',
      'null' => true,
    ),
    'parent_id' => 
    array (
      'dbtype' => 'int',
      'precision' => '11',
      'phptype' => 'integer',
      'null' => true,
      'comment' => 'variations are stored as children',
    ),
    'template_id' => 
    array (
      'dbtype' => 'int',
      'precision' => '11',
      'phptype' => 'integer',
      'null' => true,
    ),
    'currency_id' => 
    array (
      'dbtype' => 'int',
      'precision' => '4',
      'phptype' => 'integer',
      'null' => true,
    ),
    'name' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '60',
      'phptype' => 'string',
      'null' => false,
    ),
    'title' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '60',
      'phptype' => 'string',
      'null' => false,
      'comment' => 'For the webpage',
    ),
    'description' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '255',
      'phptype' => 'string',
      'null' => true,
    ),
    'content' => 
    array (
      'dbtype' => 'mediumtext',
      'phptype' => 'string',
      'null' => false,
      'default' => '',
    ),
    'type' => 
    array (
      'dbtype' => 'enum',
      'precision' => '\'regular\',\'subscription\',\'download\'',
      'phptype' => 'string',
      'null' => true,
    ),
    'sku' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '255',
      'phptype' => 'string',
      'null' => false,
    ),
    'sku_vendor' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '255',
      'phptype' => 'string',
      'null' => false,
      'comment' => 'SKU from your provider',
    ),
    'variant_matrix' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '255',
      'phptype' => 'string',
      'null' => false,
      'comment' => 'JSON hash to identify specific type:term combo(s)',
    ),
    'alias' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '255',
      'phptype' => 'string',
      'null' => false,
    ),
    'uri' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '255',
      'phptype' => 'string',
      'null' => false,
    ),
    'track_inventory' => 
    array (
      'dbtype' => 'tinyint',
      'precision' => '1',
      'phptype' => 'integer',
      'null' => false,
      'default' => 0,
      'comment' => 'Sum of child variants',
    ),
    'qty_inventory' => 
    array (
      'dbtype' => 'int',
      'precision' => '11',
      'phptype' => 'integer',
      'null' => false,
    ),
    'qty_alert' => 
    array (
      'dbtype' => 'int',
      'precision' => '11',
      'phptype' => 'integer',
      'null' => false,
      'comment' => 'Stock count at which you need to reorder',
    ),
    'qty_min' => 
    array (
      'dbtype' => 'int',
      'precision' => '11',
      'phptype' => 'integer',
      'null' => false,
      'comment' => 'Minimum quantity that should be allowed per product, per cart.',
    ),
    'qty_max' => 
    array (
      'dbtype' => 'int',
      'precision' => '11',
      'phptype' => 'integer',
      'null' => false,
      'comment' => 'Maximum quantity that should be allowed per product, per cart.',
    ),
    'qty_backorder_max' => 
    array (
      'dbtype' => 'int',
      'precision' => '11',
      'phptype' => 'integer',
      'null' => false,
      'comment' => 'Number of units you can oversell.',
    ),
    'price' => 
    array (
      'dbtype' => 'decimal',
      'precision' => '8,2',
      'phptype' => 'float',
      'null' => true,
    ),
    'price_strike_thru' => 
    array (
      'dbtype' => 'decimal',
      'precision' => '8,2',
      'phptype' => 'float',
      'null' => true,
      'comment' => 'Eye candy only',
    ),
    'price_sale' => 
    array (
      'dbtype' => 'decimal',
      'precision' => '8,2',
      'phptype' => 'float',
      'null' => true,
      'comment' => 'Used when on sale',
    ),
    'sale_start' => 
    array (
      'dbtype' => 'datetime',
      'phptype' => 'datetime',
    ),
    'sale_end' => 
    array (
      'dbtype' => 'datetime',
      'phptype' => 'datetime',
    ),
    'category' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '32',
      'phptype' => 'string',
      'null' => true,
      'comment' => 'Foxycart category (not a taxonomy)',
    ),
    'image_id' => 
    array (
      'dbtype' => 'int',
      'precision' => '11',
      'phptype' => 'integer',
      'null' => true,
      'comment' => 'Thumbnail image',
    ),
    'is_active' => 
    array (
      'dbtype' => 'tinyint',
      'precision' => '1',
      'phptype' => 'integer',
      'null' => false,
      'default' => 1,
      'comment' => 'Used to disable/enable products',
    ),
    'in_menu' => 
    array (
      'dbtype' => 'tinyint',
      'precision' => '1',
      'phptype' => 'integer',
      'null' => false,
      'default' => 1,
      'comment' => 'For hiding products from menu',
    ),
    'billing_unit' => 
    array (
      'dbtype' => 'enum',
      'precision' => '\'hours\',\'days\',\'weeks\',\'months\',\'years\'',
      'phptype' => 'string',
      'null' => true,
    ),
    'billing_interval' => 
    array (
      'dbtype' => 'int',
      'precision' => '3',
      'phptype' => 'integer',
      'null' => false,
      'default' => 1,
    ),
    'duration_unit' => 
    array (
      'dbtype' => 'enum',
      'precision' => '\'hours\',\'days\',\'weeks\',\'months\',\'years\'',
      'phptype' => 'string',
      'null' => false,
    ),
    'duration_interval' => 
    array (
      'dbtype' => 'int',
      'precision' => '3',
      'phptype' => 'integer',
      'null' => false,
      'default' => 1,
    ),
    'user_group_id' => 
    array (
      'dbtype' => 'int',
      'precision' => '11',
      'phptype' => 'integer',
      'null' => true,
    ),
    'role_id' => 
    array (
      'dbtype' => 'int',
      'precision' => '11',
      'phptype' => 'integer',
      'null' => true,
    ),
    'payload_id' => 
    array (
      'dbtype' => 'int',
      'precision' => '11',
      'phptype' => 'integer',
      'null' => true,
    ),
    'author_id' => 
    array (
      'dbtype' => 'int',
      'precision' => '11',
      'phptype' => 'integer',
      'null' => true,
    ),
    'timestamp_created' => 
    array (
      'dbtype' => 'timestamp',
      'phptype' => 'timestamp',
      'null' => true,
      'default' => 'CURRENT_TIMESTAMP',
    ),
    'timestamp_modified' => 
    array (
      'dbtype' => 'timestamp',
      'phptype' => 'timestamp',
      'null' => true,
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
        'product_id' => 
        array (
          'collation' => 'A',
          'null' => false,
        ),
      ),
    ),
    'sku' => 
    array (
      'alias' => 'sku',
      'primary' => false,
      'unique' => false,
      'columns' => 
      array (
        'sku' => 
        array (
          'collation' => 'A',
          'null' => true,
        ),
      ),
    ),
    'factory_sku' => 
    array (
      'alias' => 'factory_sku',
      'primary' => false,
      'unique' => false,
      'columns' => 
      array (
        'factory_sku' => 
        array (
          'collation' => 'A',
          'null' => true,
        ),
      ),
    ),
    'alias' => 
    array (
      'alias' => 'alias',
      'primary' => false,
      'unique' => true,
      'type' => 'BTREE',
      'columns' => 
      array (
        'store_id' => 
        array (
          'collation' => 'A',
          'null' => true,
        ),
        'alias' => 
        array (
          'collation' => 'A',
          'null' => true,
        ),
      ),
    ),
    'uri' => 
    array (
      'alias' => 'uri',
      'primary' => false,
      'unique' => true,
      'type' => 'BTREE',
      'columns' => 
      array (
        'uri' => 
        array (
          'collation' => 'A',
          'null' => false,
        ),
      ),
    ),
    'image_id' => 
    array (
      'alias' => 'image_id',
      'primary' => false,
      'unique' => false,
      'columns' => 
      array (
        'image_id' => 
        array (
          'collation' => 'A',
          'null' => true,
        ),
      ),
    ),
  ),
  'composites' => 
  array (
    'Parent' => 
    array (
      'class' => 'Product',
      'local' => 'parent_id',
      'foreign' => 'product_id',
      'cardinality' => 'one',
      'owner' => 'foreign',
    ),
    'Images' => 
    array (
      'class' => 'Image',
      'local' => 'product_id',
      'foreign' => 'product_id',
      'cardinality' => 'many',
      'owner' => 'local',
    ),
    'Terms' => 
    array (
      'class' => 'ProductTerm',
      'local' => 'product_id',
      'foreign' => 'product_id',
      'cardinality' => 'many',
      'owner' => 'local',
    ),
    'Taxonomies' => 
    array (
      'class' => 'ProductTaxonomy',
      'local' => 'product_id',
      'foreign' => 'product_id',
      'cardinality' => 'many',
      'owner' => 'local',
    ),
    'Specs' => 
    array (
      'class' => 'ProductSpec',
      'local' => 'product_id',
      'foreign' => 'product_id',
      'cardinality' => 'many',
      'owner' => 'local',
    ),
    'Relations' => 
    array (
      'class' => 'ProductRelation',
      'local' => 'product_id',
      'foreign' => 'product_id',
      'cardinality' => 'many',
      'owner' => 'local',
    ),
    'Reviews' => 
    array (
      'class' => 'Review',
      'local' => 'product_id',
      'foreign' => 'product_id',
      'cardinality' => 'many',
      'owner' => 'local',
    ),
  ),
  'aggregates' => 
  array (
    'Store' => 
    array (
      'class' => 'Store',
      'local' => 'store_id',
      'foreign' => 'id',
      'cardinality' => 'one',
      'owner' => 'foreign',
    ),
    'Template' => 
    array (
      'class' => 'modTemplate',
      'local' => 'template_id',
      'foreign' => 'id',
      'cardinality' => 'one',
      'owner' => 'foreign',
    ),
    'Currency' => 
    array (
      'class' => 'Currency',
      'local' => 'currency_id',
      'foreign' => 'currency_id',
      'cardinality' => 'one',
      'owner' => 'foreign',
    ),
    'Payload' => 
    array (
      'class' => 'modResource',
      'local' => 'payload_id',
      'foreign' => 'id',
      'cardinality' => 'one',
      'owner' => 'foreign',
    ),
    'Author' => 
    array (
      'class' => 'modUser',
      'local' => 'author_id',
      'foreign' => 'id',
      'cardinality' => 'one',
      'owner' => 'foreign',
    ),
    'UserGroup' => 
    array (
      'class' => 'modUserGroup',
      'local' => 'user_group_id',
      'foreign' => 'id',
      'cardinality' => 'one',
      'owner' => 'foreign',
    ),
    'Role' => 
    array (
      'class' => 'modUserGroupRole',
      'local' => 'role_id',
      'foreign' => 'id',
      'cardinality' => 'one',
      'owner' => 'foreign',
    ),
    'Thumbnail' => 
    array (
      'class' => 'Image',
      'local' => 'image_id',
      'foreign' => 'image_id',
      'cardinality' => 'one',
      'owner' => 'foreign',
    ),
    'Variant' => 
    array (
      'class' => 'Product',
      'local' => 'product_id',
      'foreign' => 'parent_id',
      'cardinality' => 'many',
      'owner' => 'local',
    ),
  ),
);
