<?php

class Wisepricer_Syncer_BaseController extends Mage_Core_Controller_Front_Action
{

    protected function _getConnection($type = 'core_read'){
        return Mage::getSingleton('core/resource')->getConnection($type);
    }

    protected function _getTableName($tableName){
        return Mage::getSingleton('core/resource')->getTableName($tableName);
    }

    protected function _decryptstring($str){

        set_include_path(get_include_path().PS .BP.DS . 'lib'.DS.'phpseclib' . PS.BP.DS.'app'.DS.'code'.DS.'local'.DS.'Wisepricer'.DS.'Syncer'.DS.'lib'.DS.'phpseclib' );
        include('Crypt'.DS.'RSA.php');

        $rsa = new Crypt_RSA();
        $rsa->loadKey($this->_getprivatekey());
        return $rsa->decrypt($str);
    }

    protected function _getprivatekey(){

        $licenseData  =Mage::getModel('wisepricer_syncer/config')->load(1);
        if(!$licenseData->getData()||$licenseData->getIs_confirmed()==0){

            $returnArr=array(
                'status'=>'failure',
                'error_code'=>'769',
                'error_details'=>'The user has not completed the integration.'
            );
            echo json_encode($returnArr);
            die;
        }

        return $licenseData->getprivatekey();
    }

    protected function _getpublickey(){

        $licenseData  =Mage::getModel('wisepricer_syncer/config')->load(1);
        if(!$licenseData->getData()||$licenseData->getIs_confirmed()==0){

            $returnArr=array(
                'status'=>'failure',
                'error_code'=>'769',
                'error_details'=>'The user has not completed the integration.'
            );
            echo json_encode($returnArr);
            die;
        }

        return $licenseData->getpublickey();
    }

    protected function _checkAccess(){

        $licenseData  =Mage::getModel('wisepricer_syncer/config')->load(1);
        if(!$licenseData->getData()||$licenseData->getIs_confirmed()==0){

            $returnArr=array(
                'status'=>'failure',
                'error_code'=>'769',
                'error_details'=>'The user has not completed the integration.'
            );
            echo json_encode($returnArr);
            die;
        }

        $post         = $this->getRequest()->getParams();

        $lisensekeyEncr   = $post['licensekey'];
        $lisensekeyEncr=pack('H*', $lisensekeyEncr);
        $lisensekey=$this->_decryptstring($lisensekeyEncr);
        //Mage::log('Received: '.print_r($lisensekey,true),null,'wplog.log');
        if($licenseData->getLicensekey()!=$lisensekey){

            $returnArr=array(
                'status'=>'failure',
                'error_code'=>'771',
                'error_details'=>'Unauthorized access.'
            );
            echo json_encode($returnArr);
            die;
        }

    }

}