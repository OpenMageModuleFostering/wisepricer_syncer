<?php

class Wisepricer_Syncer_Model_Reprice extends Mage_Core_Model_Abstract{

    private $_parrentIds=array();

    private function _getConnection($type = 'core_read'){
        return Mage::getSingleton('core/resource')->getConnection($type);
    }

    private function _getTableName($tableName){
        return Mage::getSingleton('core/resource')->getTableName($tableName);
    }

    private function _getAttributeId($attribute_code = 'price'){
        $connection = $this->_getConnection('core_read');
        $sql = "SELECT attribute_id
                    FROM " . $this->_getTableName('eav_attribute') . "
                WHERE
                    entity_type_id = ?
                    AND attribute_code = ?";
        $entity_type_id = $this->_getEntityTypeId();
        return $connection->fetchOne($sql, array($entity_type_id, $attribute_code));
    }

    private function _getEntityTypeId($entity_type_code = 'catalog_product'){
        $connection = $this->_getConnection('core_read');
        $sql        = "SELECT entity_type_id FROM " . $this->_getTableName('eav_entity_type') . " WHERE entity_type_code = ?";
        return $connection->fetchOne($sql, array($entity_type_code));
    }

    public function getIdFromSku($sku){
        $connection = $this->_getConnection('core_read');
        $sql        = "SELECT entity_id FROM " . $this->_getTableName('catalog_product_entity') . " WHERE sku = ?";
        return $connection->fetchOne($sql, array($sku));

    }

    private function _getConfigurableIds($productId, $newPrice){

        $parentIds = Mage::getResourceSingleton('catalog/product_type_configurable')->getParentIdsByChild($productId);

        foreach($parentIds as $parId) {

            if(!array_key_exists($parId,$this->_parrentIds) || $this->_parrentIds[$parId] > $newPrice){
                $this->_parrentIds[$parId]= $newPrice;
            }
        }
    }

    private function _getSpecialPrice($prodId,$spAttrId){

       $connection     = $this->_getConnection('core_write');
       $sql    ="SELECT value FROM " . $this->_getTableName('catalog_product_entity_decimal') . " WHERE entity_id = ? AND attribute_id = ?";
       $res=$connection->fetchOne($sql, array($prodId,$spAttrId));
       return $res;
    }

    public function checkIfSkuExists($sku){
        $connection = $this->_getConnection('core_read');
        $sql        = "SELECT COUNT(*) AS count_no FROM " . $this->_getTableName('catalog_product_entity') . " WHERE sku = ?";
        $count      = $connection->fetchOne($sql, array($sku));
        if($count > 0){
            return true;
        }else{
            return false;
        }
    }

    public function checkIfIdExists($sku){
        $connection = $this->_getConnection('core_read');
        $sql        = "SELECT COUNT(*) AS count_no FROM " . $this->_getTableName('catalog_product_entity') . " WHERE entity_id = ?";
        $count      = $connection->fetchOne($sql, array($sku));
        if($count > 0){
            return true;
        }else{
            return false;
        }
    }

    public function updatePricesById($prodArr,$entityTypeId,$store_id,$mappings){
    
        $connection     = $this->_getConnection('core_write');

        if(!is_array($prodArr)){
            $productId      = $prodArr->sku;
        }else{
            $productId      = $prodArr['sku'];
        }

        $sql='';

        foreach($prodArr as $pricesName=>$priceValue){

              try{
                    if($pricesName=='sku'){
                        continue;
                    }

                    $mPriceName=isset($mappings[$pricesName])? $mappings[$pricesName]['magento_field'] : '';

                    $attributeId    = $this->_getAttributeId($mPriceName);

                    if(!$attributeId){

                        Mage::log('Product '.$productId.', could not find field '.$pricesName,null,'wplog.log');
                        continue;
                    }

                    if($priceValue == 0 || $pricesName==''){

                        if($mPriceName=='price'){
                            throw new Exception("Price [$priceValue] is invalid Product Id: $productId");
                        }else{
                            $sql="DELETE FROM " . $this->_getTableName('catalog_product_entity_decimal') . " WHERE entity_type_id=? AND attribute_id=? AND store_id=? AND entity_id=?";

                            $res=$connection->query($sql, array($entityTypeId, $attributeId,$store_id,$productId));
                        }

                    }else{

                        $sql="SELECT value_id FROM ".$this->_getTableName('catalog_product_entity_decimal')
                            ." WHERE attribute_id=? AND entity_id=? AND store_id=?";

                        $res=$connection->fetchOne($sql, array($attributeId,$productId,$store_id));

                        if(!is_numeric($res)){
                            $sql="INSERT INTO " . $this->_getTableName('catalog_product_entity_decimal') . "
                        (entity_type_id,attribute_id,store_id,entity_id,value)
                        VALUES (?,?,?,?,?)";

                            $res=$connection->query($sql, array($entityTypeId, $attributeId,$store_id,$productId,$priceValue));

                        }else{
                            $sql = "UPDATE " . $this->_getTableName('catalog_product_entity_decimal') . " cped
                            SET  cped.value = {$priceValue}
                        WHERE  cped.attribute_id = {$attributeId}
                        AND cped.entity_id = {$productId}";
                            //if($attributeId=='76') {echo $sql; die;}
                            $res=$connection->query($sql);
                        }
                    }



              }catch(Exception $e){
                Mage::log($e->getMessage(),null,'wplog.log');

                // echo $sql;
                // echo 'Line '.__LINE__.' '.$e->getMessage();
              }

        }
            
    }

    public function getParrentIds(){
        return $this->_parrentIds;
    }

    private function getProductPrice($product){
        $calcPriceRule = Mage::getModel('catalogrule/rule')->calcProductPriceRule($product,$product->getPrice());
        if(isset($calcPriceRule) && $calcPriceRule > 0){
            return $calcPriceRule;
        }

        $specialPrice = $product->getSpecialPrice();
        if(isset($specialPrice) && $specialPrice > 0){
            return $specialPrice;
        }

        $finalPrice = $product->getFinalPrice();
        if(isset($finalPrice) && $finalPrice > 0){
            return $finalPrice;
        }

        return $product->getPrice();
    }
}
?>