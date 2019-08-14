<?php

$installer = $this;

$installer->startSetup();

$installer->addAttribute('catalog_product', 'ignore_wisepricer', array(
    'type'              => 'int',
    'backend'           => '',
    'frontend'          => '',
    'label'             => 'Ignore Wiser',
    'input'             => 'select',
    'class'             => '',
    'source'            => 'eav/entity_attribute_source_boolean',
    'global'            => 0,
    'visible'           => 1,
    'required'          => 0,
    'user_defined'      => 1,
    'default'           => 0,
    'searchable'        => 0,
    'filterable'        => 0,
    'comparable'        => 0,
    'visible_on_front'  => 0,
    'unique'            => 0,
    'position'          => 1,
));

$installer->endSetup();