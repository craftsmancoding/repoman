<?php

$core_path = $modx->getOption('moxycart.core_path','',MODX_CORE_PATH);

// Add the package to the MODX extension_packages array
$modx->addExtensionPackage($object['namespace'],"{$core_path}components/{$object['namespace']}/model/", array('tablePrefix'=>'moxy_'));
$modx->addPackage('moxycart',"{$core_path}components/{$object['namespace']}/model/",'moxy_');
$modx->addPackage('foxycart',"{$core_path}components/{$object['namespace']}/model/",'foxy_');

$manager = $modx->getManager();

// Moxycart Stuff
$manager->createObjectContainer('Currency');
$manager->createObjectContainer('Product');
$manager->createObjectContainer('Spec');
$manager->createObjectContainer('Unit');
$manager->createObjectContainer('VariationType'); 
$manager->createObjectContainer('VariationTerm');
$manager->createObjectContainer('ProductVariationTypes');
$manager->createObjectContainer('ProductTerm');
$manager->createObjectContainer('ProductTaxonomy');
$manager->createObjectContainer('ProductSpec');
$manager->createObjectContainer('ProductRelation');
$manager->createObjectContainer('Cart');
$manager->createObjectContainer('Image');
$manager->createObjectContainer('Review');

// Foxycart Stuff
$manager->createObjectContainer('Foxydata');
$manager->createObjectContainer('Transaction');
$manager->createObjectContainer('Tax');
$manager->createObjectContainer('Discount');
$manager->createObjectContainer('CustomField');
$manager->createObjectContainer('Attribute');
$manager->createObjectContainer('TransactionDetail');
$manager->createObjectContainer('TransactionDetailOption');
$manager->createObjectContainer('ShiptoAddress');
