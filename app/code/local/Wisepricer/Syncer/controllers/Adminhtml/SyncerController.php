<?php
/**
 * Performs integration between Wisepricer and Magento
 *
 */
class Wisepricer_Syncer_Adminhtml_SyncerController extends Mage_Adminhtml_Controller_Action
{
    public function registrateAction()
    {
        $model   = Mage::getModel('wisepricer_syncer/config');
        $lisenceData=$model->load(1);
        if(count($lisenceData->getData())>0){
            if($lisenceData->getis_confirmed()==0){
                Mage::getSingleton('adminhtml/session')->addError('Integration not complete.');
            }
        }else{
            Mage::getSingleton('adminhtml/session')->addError('Integration not complete.');
        }

        $this->loadLayout()->_setActiveMenu('wisepricer');
        $this->renderLayout();
    }
    public function mappingAction()
    {
        $model   = Mage::getModel('wisepricer_syncer/config');
        $lisenceData=$model->load(1);
        if(count($lisenceData->getData())>0){
            if($lisenceData->getis_confirmed()==0){
                Mage::getSingleton('adminhtml/session')->addError('Integration not complete.');
            }
        }else{
            Mage::getSingleton('adminhtml/session')->addError('Integration not complete.');
        }

        $this->loadLayout()->_setActiveMenu('wisepricer');
        $this->renderLayout();
    }
    public function _savekey()
    {
        $post                    = $this->getRequest()->getPost('register_form');
        $lisensekey              = $post['licensekey'];
        $website                 = $post['website'];
        $type                    = $post['product_type'];

        if(isset($post['reprice_configurable'])){
            $reprConf   = $post['reprice_configurable'];
            if(!$reprConf){
                $reprConf=0;
            }else{
                $reprConf=1;
            }
        }else{
            $reprConf=0;
        }

        if(isset($post['import_outofstock'])){
            $import_outofstock   = $post['import_outofstock'];
            if(!$import_outofstock){
                $import_outofstock=0;
            }else{
                $import_outofstock=1;
            }
        }else{
            $import_outofstock=0;
        }

        try {
            if (empty($lisensekey)) {
                Mage::throwException($this->__('Invalid form data. The license key is missing!'));
            }
            $model   = Mage::getModel('wisepricer_syncer/config');
            $lisenceData=$model->load(1);
            if(count($lisenceData->getData())>0){
                $lisenceData->setLicensekey($lisensekey);
                $lisenceData->setWebsite($website);
                $lisenceData->setProduct_type($type);
                $lisenceData->setReprice_configurable($reprConf);
                $lisenceData->setImport_outofstock($import_outofstock);
                $lisenceData->save();
            }else{
                $model->setLicensekey($lisensekey)->save();
            }

        }
        catch (Exception $e) {
            Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
        }
    }
    protected function _validateMapping($post){

        $isValid=false;
        if(
            $post['upc']||
            $post['asin']||
            ($post['brand']&&$post['model'])||
            ($post['brand']&&$post['mpn'])
        ){
            $isValid=true;
        }
        if($post['sku']==''||$post['title']==''||$post['price']==''){
            $isValid=false;
        }

        if(($post['cost']==''||$post['minprice_man']=='')&&$post['minprice']==''){
            $isValid=false;
        }

        return $isValid;
    }
    public function savemappingAction(){
        $post= $this->getRequest()->getPost('mapping_form');
        $this->_savekey();

        $isValid=$this->_validateMapping($post);
        if(!$post||!$isValid){
            Mage::getSingleton('adminhtml/session')->addError('Your mapping is not valid.Please fill the data according to the instruction below.');
            $this->_redirect('*/*/mapping');
            return;
        }
        $data=array();
        $model = Mage::getModel('wisepricer_syncer/mapping');
        $allEntered=true;

        foreach($post as $name=>$value){

            if(!$value||$name=='type'||$name=='rule'){
                continue;
            }

            $fieldName=$name;

            if($name=='shipping_man'){
                $fieldName='shipping';
            }


            $data=array('wsp_field'=>$fieldName,'magento_field'=>$value);

            if($name=='minprice_man'){
                $fieldName='minprice';
                $function=$post['type'].':'.$post['rule'];
                $data=array('wsp_field'=>$fieldName,'magento_field'=>$value,'extra'=>$function);
            }

            $mappingId=$model->loadIdByWsfield($fieldName);

            if($mappingId){

                $mapping=$model->load($mappingId);
                $mapping->setmagento_field($value);

                if($name=='minprice_man'){
                    $mapping->setExtra($function);
                }

                try {
                    $mapping->save()->getId();
                } catch (Exception $e){
                    Mage::getSingleton('adminhtml/session')->addError('Insert of the field "'.$fieldName.'" failed with a following message: '.$e->getMessage());
                    $allEntered=false;
                }

            }else{
                $model->setData($data);
                try {
                    $model->save()->getId();
                } catch (Exception $e){
                    Mage::getSingleton('adminhtml/session')->addError('Insert of the field "'.$fieldName.'" failed with a following message: '.$e->getMessage());
                    $allEntered=false;
                }
            }



        }

        $this->_redirect('*/*/mapping');
        if($allEntered){
            $lisenceModel=Mage::getModel('wisepricer_syncer/config')->load(1);
            if($lisenceModel->getData()){
                if($lisenceModel->getis_confirmed()==0){
                    $this->_createKeys($lisenceModel);
                    $lisenceModel->setis_confirmed(1)->save();
                    Mage::getSingleton('adminhtml/session')->addSuccess('Your integration with Wisepricer  is now complete!');
                }
            }else{
                Mage::throwException($this->__('Invalid form data. The license key is missing!'));
            }
        }

    }

    public function checkcompatAction(){

    }

    protected function _createKeys($lisenceModel){

        set_include_path(get_include_path().PS .BP.DS . 'lib'.DS.'phpseclib' . PS.BP.DS.'app'.DS.'code'.DS.'local'.DS.'Wisepricer'.DS.'Syncer'.DS.'lib'.DS.'phpseclib' );
        include('Crypt'.DS.'RSA.php');

        $rsa = new Crypt_RSA();

        // Create the keypair
        $keyArr = $rsa->createKey();
        $lisenceModel->setpublickey($keyArr['publickey']);
        $lisenceModel->setprivatekey($keyArr['privatekey']);
    }

}
?>