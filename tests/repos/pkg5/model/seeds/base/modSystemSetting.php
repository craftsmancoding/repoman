<?php
/*-----------------------------------------------------------------
For descriptions here, you must create some lexicon entries:
Name: setting_ + $key
Description: setting_ + $key + _desc
-----------------------------------------------------------------*/
return array(
    array(
        'key'  =>     'moxycart.domain',
		'value'=>     '',
		'xtype'=>     'textfield',
		'namespace' => 'moxycart',
		'area' => 'moxycart:default'
    ),
    array(
        'key'  =>     'moxycart.upload_dir',
		'value'=>     'products/',
		'xtype'=>     'textfield',
		'namespace' => 'moxycart',
		'area' => 'moxycart:default'
    ),
    array(
        'key'  =>     'moxycart.api_key',
		'value'=>     '',
		'xtype'=>     'textfield',
		'namespace' => 'moxycart',
		'area' => 'moxycart:default'
    ),
    array(
        'key'  =>     'moxycart.enable_reviews',
		'value'=>     0,
		'xtype'=>     'combo-boolean',
		'namespace' => 'moxycart',
		'area' => 'moxycart:default'
    ),
    array(
        'key'  =>     'moxycart.auto_approve_reviews',
		'value'=>     0,
		'xtype'=>     'combo-boolean',
		'namespace' => 'moxycart',
		'area' => 'moxycart:default'
    ),
    array(
        'key'  =>     'moxycart.enable_variations',
		'value'=>     0,
		'xtype'=>     'combo-boolean',
		'namespace' => 'moxycart',
		'area' => 'moxycart:default'
    ),
    array(
        'key'  =>     'moxycart.thumbnail_width',
		'value'=>     '240',
		'xtype'=>     'textfield',
		'namespace' => 'moxycart',
		'area' => 'moxycart:default'
    ),
    array(
        'key'  =>     'moxycart.thumbnail_height',
		'value'=>     '180',
		'xtype'=>     'textfield',
		'namespace' => 'moxycart',
		'area' => 'moxycart:default'
    ),
    array(
        'key'  =>     'moxycart.foxycart_categories',
		'value'=>     '["Default"]',
		'xtype'=>     'textfield',
		'namespace' => 'moxycart',
		'area' => 'moxycart:default'
    ),
);
/*EOF*/