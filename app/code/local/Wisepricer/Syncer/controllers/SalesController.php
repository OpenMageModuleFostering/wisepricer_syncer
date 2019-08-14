<?php

require_once 'Wisepricer/Syncer/controllers/BaseController.php';

class Wisepricer_Syncer_SalesController extends Wisepricer_Syncer_BaseController
{
    public function getsalesAction(){

        $this->_checkAccess();

        $post = $this->getRequest()->getParams();

        try{
            $connection     = $this->_getConnection('core_write');
            $sql    ="SELECT * FROM " . $this->_getTableName('wisepricer_syncer_sales'). " WHERE status = 0";
            $orders=$connection->fetchAll($sql);

            $output=array();
            $ordersOut=array();
            foreach($orders as $orderArr){

                $orderIncrementId=$orderArr['order_id'];
                $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
                $items = $order->getAllVisibleItems();

                $itemArr=array();
                foreach($items as $item){

                    $itemArr['sku']             = $item->getSku();
                    $itemArr['original_price']  = $item->getOriginalPrice();
                    $itemArr['price']           = $item->getPrice();
                    $itemArr['qty']             = $item->getQtyOrdered();
                    $itemArr['discount_amount'] = $item->getDiscountAmount();
                    $itemArr['created_at']      = $item->getCreatedAt();
                    $itemArr['subtotal']        = $item->getrow_total();
                    $row_total_incl_tax        = $item->getrow_total_incl_tax();
                    $itemArr['row_total']       = $row_total_incl_tax-$itemArr['discount_amount'];


                    $ordersOut[$orderIncrementId][]=$itemArr;
                }

                if(!isset($post['debug'])||$post['debug']!=1){
                    $this->_updateOrderAsSent($orderIncrementId);
                }


            }

            $checksum=md5(json_encode($ordersOut));
            $output['orders']=$ordersOut;
            $output['checksum']=$checksum;
            echo json_encode($output);

        }catch(Exception $e){
            Mage::log($e->getMessage(),null,'wplog.log');
            echo $e->getMessage();
        }
    }

    public function getsalesbydateAction(){

        $this->_checkAccess();
        $post = $this->getRequest()->getParams();

        $from = $post['from'];
        if(!isset($post['from'])){
            $returnArr=array(
                'status'=>'failure',
                'error_code'=>'767',
                'error_details'=>'The "from" parameter is mandatory'
            );
            echo json_encode($returnArr);
            die;
        }

        if(isset($post['to'])){
            $to   = $post['to'];
        }else{
            $to   = strtotime('now');
        }

        $fromMysqldate = date( 'Y-m-d', $from );
        $toMysqldate   = date( 'Y-m-d', $to );

        try{
            $connection     = $this->_getConnection('core_write');
            $sql    ="SELECT * FROM " . $this->_getTableName('wisepricer_syncer_sales'). " WHERE order_date BETWEEN ? AND ?";
            $orders=$connection->fetchAll($sql,array($fromMysqldate,$toMysqldate));

            $output=array();
            $ordersOut=array();
            foreach($orders as $orderArr){

                $orderIncrementId=$orderArr['order_id'];
                $order = Mage::getModel('sales/order')->loadByIncrementId($orderIncrementId);
                $items = $order->getAllVisibleItems();

                $itemArr=array();
                foreach($items as $item){

                    $itemArr['sku']             = $item->getSku();
                    $itemArr['original_price']  = $item->getOriginalPrice();
                    $itemArr['price']           = $item->getPrice();
                    $itemArr['qty']             = $item->getQtyOrdered();
                    $itemArr['discount_amount'] = $item->getDiscountAmount();
                    $itemArr['created_at']      = $item->getCreatedAt();
                    $itemArr['subtotal']        = $item->getrow_total();
                    $row_total_incl_tax        = $item->getrow_total_incl_tax();
                    $itemArr['row_total']       = $row_total_incl_tax-$itemArr['discount_amount'];


                    $ordersOut[$orderIncrementId][]=$itemArr;
                }

            }

            $checksum=md5(json_encode($ordersOut));
            $output['orders']=$ordersOut;
            $output['checksum']=$checksum;

            echo json_encode($output);

        }catch(Exception $e){
            Mage::log($e->getMessage(),null,'wplog.log');
            echo $e->getMessage();
        }
    }

    private function _updateOrderAsSent($orderId){
        $connection     = $this->_getConnection('core_write');
        $sql    ="UPDATE " . $this->_getTableName('wisepricer_syncer_sales')." SET status=1 WHERE order_id=?";
        $connection->query($sql,array($orderId));
    }

    public function gethitsAction(){

        $this->_checkAccess();

        try{

            $connection     = $this->_getConnection('core_write');
            $sql    ="SELECT * FROM " . $this->_getTableName('wisepricer_syncer_hits_counter');
            $hits=$connection->fetchAll($sql);


            $output=array();
            $checksum=md5(json_encode($hits));

            $output['checksum']=$checksum;
            $output['hits']=$hits;
            echo json_encode($output);

        }catch(Exception $e){
            Mage::log($e->getMessage(),null,'wplog.log');
            echo $e->getMessage(); die;
        }


    }

    public function gethitsbydateAction(){

        $this->_checkAccess();

        $post = $this->getRequest()->getParams();

        $from = $post['from'];
        if(!isset($post['from'])){
            $returnArr=array(
                'status'=>'failure',
                'error_code'=>'767',
                'error_details'=>'The "from" parameter is mandatory'
            );
            echo json_encode($returnArr);
            die;
        }

        if(isset($post['to'])){
            $to   = $post['to'];
        }else{
            $to   = strtotime('now');
        }

        $fromMysqldate = date( 'Y-m-d', $from );
        $toMysqldate   = date( 'Y-m-d', $to );

        try{

            $connection     = $this->_getConnection('core_write');
            $sql    ="SELECT * FROM " . $this->_getTableName('wisepricer_syncer_hits_counter')." WHERE hit_date BETWEEN ? AND ?";
            $hits=$connection->fetchAll($sql,array($fromMysqldate,$toMysqldate));


            $output=array();
            $checksum=md5(json_encode($hits));

            $output['checksum']=$checksum;
            $output['hits']=$hits;
            echo json_encode($output);

        }catch(Exception $e){
            Mage::log($e->getMessage(),null,'wplog.log');
            echo $e->getMessage(); die;
        }


    }

}