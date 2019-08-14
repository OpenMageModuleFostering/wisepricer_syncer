<?php
/**
 * Performs integration between Wisepricer and Magento
 *
 */

require_once 'Wisepricer/Syncer/controllers/BaseController.php';

class Wisepricer_Syncer_ProductsController extends Wisepricer_Syncer_BaseController
{

    private $productOrigData=array();

    public function sendAction(){
        // Mage::log('start sending',null,'wplog.log');
        set_time_limit (1800);
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

        $magentoSessionId=Mage::getModel('core/cookie')->get('wpsession');

        if((!$magentoSessionId)||($magentoSessionId!=$post['sesssionid'])){

            $returnArr=array(
                'status'=>'failure',
                'error_code'=>'771',
                'error_details'=>'Unauthorized access.'
            );
            echo json_encode($returnArr);
            die;
        }


        $startInd     = $post['start'];
        if(!$startInd){
            $startInd=0;
        }

        $count        = $post['count'];
        if(!$count||$count>200){
            $count=200;
        }

        $pageNum=($startInd/$count)+1;


        $fieldsEncoded= $post['params'];
        $fields       =json_decode(urldecode ($fieldsEncoded));

        $mappings      =Mage::getModel('wisepricer_syncer/mapping')->getCollection()->getData();

        $collection=Mage::getModel('catalog/product')->getCollection();

        $requiredFields=array();
        foreach ($mappings as $fieldsArr) {
            if($fields[0]=='all'||in_array($fieldsArr['wsp_field'],$fields)){
                $requiredFields[]=$fieldsArr;
            }

            if(!is_numeric($fieldsArr['magento_field'])){
                $collection->addAttributeToSelect($fieldsArr['magento_field']);
            }

        }
        $collection->addFieldToFilter('status', Mage_Catalog_Model_Product_Status::STATUS_ENABLED);
        $collection->addAttributeToFilter('type_id', array('eq' => 'simple'));

        try{

            $collection->addAttributeToFilter(
                array(
                    array('attribute' => 'ignore_wisepricer', 'neq' => 1),
                    array('attribute' => 'ignore_wisepricer', 'null' => true),
                )
                ,
                '',
                'left'
            );

        }catch(Exception $e){

        }


        if($licenseData->getWebsite()!=0){
            $collection->addStoreFilter($licenseData->getWebsite());
        }

        if($licenseData->getImport_outofstock()){
            Mage::getSingleton('cataloginventory/stock')->addInStockFilterToCollection($collection);
        }

        $collection->setPage($pageNum, $count)->load();
        // echo '<pre>'.print_r($collection->getData(),true).'</pre>'; die;
        //echo $collection->getSelect();die;

        $productsOutput=array();



        foreach ($collection as $product) {

            $productCollData=$product->getData();
            $productModel=Mage::getModel('catalog/product')->load($productCollData['entity_id']);

            $this->productOrigData=$productModel->getData();
            $productData=array();

            foreach ($requiredFields as $field) {

                if($field['wsp_field']=='stock'){

                    $qtyStock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($product)->getQty();
                    $productData['stock']=(int)$qtyStock;

                }elseif($field['wsp_field']=='productimage'){

                    if($field['magento_field']=='image'||$field['magento_field']=='small_image'){

                        //TODO add valid media url in case that website uses store code in url check this Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA);

                        try{

                            $siteUrl=substr_replace(Mage::getUrl('media/catalog/product') ,"",-1);
                            $productData['productimage']=$siteUrl.$this->productOrigData['image'];

                        }catch(Exception $e){

                        }

                    }else{
                        $productData['productimage']=$this->productOrigData['image'];
                    }


                }elseif($field['wsp_field']=='shipping'){

                    if(is_numeric($field['magento_field'])){
                        $productData['shipping']=$field['magento_field'];
                    }else{
                        if(isset($this->productOrigData[$field['magento_field']])){
                            $productData['shipping']=$this->productOrigData[$field['magento_field']];
                        }

                    }

                }elseif($field['wsp_field']=='minprice'){
                    if(is_numeric($field['magento_field'])){

                        $productData[$field['wsp_field']]=$this->_calculateMinPrice($field);

                    }else{
                        if(isset($this->productOrigData[$field['magento_field']])){
                            $productData[$field['wsp_field']]=$this->productOrigData[$field['magento_field']];
                        }

                    }
                }else{
                    $attributeInfo = Mage::getResourceModel('eav/entity_attribute_collection')
                        ->setCodeFilter($field['magento_field'])
                        ->getFirstItem();


                    if($attributeInfo->getfrontend_input()=='select'){
                        $attrLabel=$productModel->getAttributeText($field['magento_field']);
                        $productData[$field['wsp_field']]=$attrLabel;
                    }else{
                        if(!isset($this->productOrigData[$field['magento_field']])){continue;}
                        $productData[$field['wsp_field']]=$this->productOrigData[$field['magento_field']];
                    }


                }


            }

            $productData['producturl']=Mage::helper('catalog/product')->getProductUrl($productCollData['entity_id']);

            $productsOutput[]=$productData;
        }

        $returnArr=array(
            'status'=>'success',
            'error_code'=>'0',
            'data'=>$productsOutput
        );
        Mage::log('sending '.count($productsOutput).' products',null,'wplog.log');
        echo json_encode($returnArr);
        die;
    }

    private function _calculateMinPrice($field){

        if(!isset($field['extra'])){
            $field['extra']='-1:a';
        }
        $ruleArr= explode(':',$field['extra']);

        if($ruleArr[1]=='a'){
            $costField=$this->_getMagentoFieldByWsField('cost');
            if($costField){
                $cost=$this->productOrigData[$costField];
                if($ruleArr[0]=='-1'){
                    $minPrice=($cost*$field['magento_field'])/100+$cost;
                }else{
                    $minPrice=$cost+$field['magento_field'];
                }
            }
        }else{
            $priceField=$this->_getMagentoFieldByWsField('price');
            $price=$this->productOrigData[$priceField];
            if($ruleArr[0]=='-1'){
                $minPrice=$price-($price*$field['magento_field'])/100;
            }else{
                $minPrice=$price-$field['magento_field'];
            }
        }
        return $minPrice;

    }

    private function _getMagentoFieldByWsField($wsfield){

        $model = Mage::getModel('wisepricer_syncer/mapping');
        $mappingId=$model->loadIdByWsfield($wsfield);
        if($mappingId){
            $mapping=$model->load($mappingId);
            return $mapping->getmagento_field();
        }

        return false;
    }

    private function _randString( $length ) {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str='';
        $size = strlen( $chars );
        for( $i = 0; $i < $length; $i++ ) {
            $str .= $chars[ rand( 0, $size - 1 ) ];
        }

        return $str;
    }

    public function loginAction(){

        $this->_checkAccess();

        $sessionId=$this->_randString(8);

        Mage::getModel('core/cookie')->set('wpsession',$sessionId , true);

        $returnArr=array(
            'status'=>'success',
            'error_code'=>'0',
            'session_id'=>$sessionId
        );
        Mage::log(print_r('=========================================================',true),null,'wplog.log');
        echo json_encode($returnArr);
    }

    public function logoutAction(){

        $post         = $this->getRequest()->getParams();

        $magentoSessionId=Mage::getModel('core/cookie')->get('wpsession');

        if((!$magentoSessionId)||($magentoSessionId!=$post['sesssionid'])){
            $returnArr=array(
                'status'=>'failure',
                'error_code'=>'771',
                'error_details'=>'Unauthorized access.'
            );
            echo json_encode($returnArr);
            die;
        }

        Mage::getModel('core/cookie')->delete('wpsession');
        $returnArr=array(
            'status'=>'success',
            'error_code'=>'0'
        );

        echo json_encode($returnArr);
    }
    public function getpublicAction(){

        $publickey=$this->_getpublickey();

        $returnArr=array(
            'status'=>'success',
            'error_code'=>'0',
            'publickey'=>$publickey
        );

        echo json_encode($returnArr);
    }


    public function repriceAction(){
        Mage::log(print_r('Entered reprice action',true),null,'wplog.log');
        set_time_limit (1800);
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

        $magentoSessionId=Mage::getModel('core/cookie')->get('wpsession');

        if((!$magentoSessionId)||($magentoSessionId!=$post['sesssionid'])){

            $returnArr=array(
                'status'=>'failure',
                'error_code'=>'771',
                'error_details'=>'Unauthorized access.'
            );
            echo json_encode($returnArr);
            die;
        }

        $productsEncoded= $post['products'];
        $products   =json_decode(urldecode ($productsEncoded));
        $responseArr=array();
        $sucessCounter=0;
        $failedCounter=0;
        Mage::log(print_r('repricing '.count($products),true),null,'wplog.log');
        $repriceModel=Mage::getModel('wisepricer_syncer/reprice');
        $id_type= $this->_getMagentoFieldByWsField('sku');
        $price_field= $this->_getMagentoFieldByWsField('price');

        if(strtolower($id_type)=='sku'){

            foreach ($products as $prodArr) {

                if ($repriceModel->checkIfSkuExists($prodArr->sku)) {
                    try {

                        $repriceModel->updatePricesBySku($prodArr,$price_field);
                        $responseArr[]=array('sku'=>$prodArr->sku,'price'=>$prodArr->price,'error_code'=>'0');
                        $sucessCounter++;

                    } catch (Exception $exc) {
                        $responseArr[]=array('sku'=>$prodArr->sku,'error_code'=>'444','error_details'=>$exc->getMessage());
                        $failedCounter++;
                    }
                }else{
                    $responseArr[]=array('sku'=>$prodArr->sku,'error_code'=>'333','error_details'=>'SKU could not be loaded');
                    $failedCounter++;

                }

            }

        }elseif(strtolower($id_type)=='entity_id'){

            foreach ($products as $prodArr) {

                if ($repriceModel->checkIfIdExists($prodArr->sku)) {
                    try {

                        $repriceModel->updatePricesById($prodArr,$price_field);
                        $responseArr[]=array('sku'=>$prodArr->sku,'price'=>$prodArr->price,'error_code'=>'0');
                        $sucessCounter++;

                    } catch (Exception $exc) {
                        $responseArr[]=array('sku'=>$prodArr->sku,'error_code'=>'444','error_details'=>$exc->getMessage());
                        $failedCounter++;
                    }
                }else{
                    $responseArr[]=array('sku'=>$prodArr->sku,'error_code'=>'333','error_details'=>'ID could not be loaded');
                    $failedCounter++;

                }

            }

        }

        $parrentsIds=$repriceModel->getParrentIds();
        foreach($parrentsIds as $parId){
            try{
                $minPrice=$repriceModel->repriceConfigurable($parId);
                $responseArr[]=array('sku'=>$parId,'price'=>$minPrice,'error_code'=>'0');
                $sucessCounter++;
            }catch (Exception $exc) {
                $responseArr[]=array('sku'=>$parId,'error_code'=>'444','error_details'=>$exc->getMessage());
                $failedCounter++;
            }

        }


        Mage::log(print_r('finished repricing',true),null,'wplog.log');
        if($sucessCounter==0){
            $error_code=-1;
            $error_details='Magento could not update any product.';
        }else{
            $error_code=0;
            $error_details='';
        }

        $returnArr=array();
        $returnArr['ResultData']= $responseArr;
        $returnArr['Succeeded']=$sucessCounter;
        $returnArr['Failed']=$failedCounter;
        $returnArr['error_code']=$error_code;
        $returnArr['error_details']=$error_details;
        echo json_encode($returnArr);
        die;
    }

    public function reindexAction(){

        Mage::log(print_r('Entered reindex action',true),null,'wplog.log');

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

        $magentoSessionId=Mage::getModel('core/cookie')->get('wpsession');

        if((!$magentoSessionId)||($magentoSessionId!=$post['sesssionid'])){

            $returnArr=array(
                'status'=>'failure',
                'error_code'=>'771',
                'error_details'=>'Unauthorized access.'
            );
            echo json_encode($returnArr);
            die;
        }
        Mage::getSingleton('index/indexer')->getProcessByCode('catalog_product_price')->reindexAll();
    }

    public function generatejasonAction(){
        $result['products']=array(
            array(
                'sku'=>'zol_r_sm',
                'price'=>'303.3'
            ),
            array(
                'sku'=>'4fasd5f5',
                'price'=>'770.0'
            )
        );
        echo json_encode($result['products']);
        die;
    }

    public function pingAction(){

        $result['storeurl']=Mage::getUrl();
        echo json_encode($result);
        die;
    }

    public function versAction(){
        $mage=Mage::getVersion();
        $ext=(string) Mage::getConfig()->getNode()->modules->Wisepricer_Syncer->version;
        $result=array('mage'=>$mage,'ext'=>$ext);
        echo json_encode($result);die;
    }

}
?>