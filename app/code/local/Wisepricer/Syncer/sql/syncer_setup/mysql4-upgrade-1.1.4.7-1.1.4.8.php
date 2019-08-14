<?php

$installer = $this;

$installer->startSetup();

$attributeId=$installer->getAttribute('catalog_product','ignore_wisepricer');

$allAttributeSetIds=$installer->getAllAttributeSetIds('catalog_product');

if(is_array($attributeId)){
    foreach ($allAttributeSetIds as $attributeSetId) {
        try{
            $attributeGroupId=$installer->getAttributeGroup('catalog_product',$attributeSetId,'General');
        }
        catch(Exception $e)
        {
            $attributeGroupId=$installer->getDefaultAttributeGroupId('catalog/product',$attributeSetId);
        }

        if(is_array($attributeGroupId)){
            $installer->addAttributeToSet('catalog_product',$attributeSetId,$attributeGroupId['attribute_group_id'],$attributeId['attribute_id']);
        }
    }
}


$installer->endSetup();