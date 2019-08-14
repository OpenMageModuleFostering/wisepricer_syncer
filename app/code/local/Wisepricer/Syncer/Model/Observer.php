<?php

class Wisepricer_Syncer_Model_Observer extends Mage_Core_Model_Abstract
{
    public function catalog_controller_product_init($observer){

        $product= $observer->getProduct();
        $sku    = $product->getSku();
        $dt     = strtotime('now');
        $mysqldate = date( 'Y-m-d', $dt );
        try{

            $connection     = $this->_getConnection('core_write');
            $sql    ="SELECT * FROM " . $this->_getTableName('wisepricer_syncer_hits_counter') . " WHERE sku = ? AND hit_date=?";
            $hits=$connection->fetchAll($sql, array($sku,$mysqldate));

            if($hits&&count($hits)!=0){
                $counter=$hits[0]['hits'];
                $counter++;
                $sql    ="UPDATE " . $this->_getTableName('wisepricer_syncer_hits_counter') . " SET hits=? WHERE hits_id = ?";
                $connection->query($sql, array($counter,$hits[0]['hits_id']));
            }else{
                $hits=1;
                $sql    ="INSERT INTO " . $this->_getTableName('wisepricer_syncer_hits_counter') . " (sku,hits,hit_date) VALUES(?,?,?)";
                $connection->query($sql, array($sku,$hits,$mysqldate));
            }

        }catch(Exception $e){
            Mage::log($e->getMessage(),null,'wplog.log');
        }

    }

    public function sales_order_payment_pay($observer){

        $payment = $observer->getPayment();
        $order   = $payment->getOrder();
        $orderId = $order->getIncrementId();
      try{

        $connection     = $this->_getConnection('core_write');
        $sql    ="SELECT * FROM " . $this->_getTableName('wisepricer_syncer_sales') . " WHERE order_id = ?";
        $orderCheck=$connection->fetchOne($sql, array($orderId));

        if(!$orderCheck){
            $sql    ="INSERT INTO " . $this->_getTableName('wisepricer_syncer_sales') . " (order_id,status) VALUES (?,0) ?";
            $connection->query($sql, array($orderId));
        }

      }catch(Exception $e){
            Mage::log($e->getMessage(),null,'wplog.log');
      }

    }

    private function _getConnection($type = 'core_read'){
        return Mage::getSingleton('core/resource')->getConnection($type);
    }

    private function _getTableName($tableName){
        return Mage::getSingleton('core/resource')->getTableName($tableName);
    }

    public function checkout_type_onepage_save_order($observer){

        $order   = $observer->getOrder();
        $orderId = $order->getIncrementId();

        $connection     = $this->_getConnection('core_write');
        $sql    ="SELECT * FROM " . $this->_getTableName('wisepricer_syncer_sales') . " WHERE order_id = ?";
        $orderCheck=$connection->fetchOne($sql, array($orderId));

        if(!$orderCheck){
            $sql    ="INSERT INTO " . $this->_getTableName('wisepricer_syncer_sales') . " (order_id,status) VALUES (?,'0')";
            $connection->query($sql, array($orderId));
        }
    }
}