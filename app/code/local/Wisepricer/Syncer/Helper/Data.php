<?php



class Wisepricer_Syncer_Helper_Data extends Mage_Core_Helper_Abstract{

    public function getConfigDataByFullPath($path){

        if (!$row = Mage::getSingleton('core/config_data')->getCollection()->getItemByColumnValue('path', $path)) {
            $conf = Mage::getSingleton('core/config')->init()->getXpath('/config/default/'.$path);
            if(is_array($conf)){
                $value = array_shift($conf);
            }else{
                return '';
            }

        } else {
            $value = $row->getValue();
        }

        return $value;

    }

    public function getConfigMultiDataByFullPath($path){

        if (!$rows = Mage::getSingleton('core/config_data')->getCollection()->getItemsByColumnValue('path', $path)) {
            $conf = Mage::getSingleton('core/config')->init()->getXpath('/config/default/'.$path);
            $value = array_shift($conf);
        } else {
            $values=array();
            foreach($rows as $row){
                $values[$row->getScopeId()]=$row->getValue();
            }
        }

        return $values;

    }

    public function getMultiStoreDataJson(){

        $websites=Mage::getModel('core/website')->getCollection();

        $multistoreData=array();
        $multistoreJson='';
        $useStoreCode=$this->getConfigDataByFullPath('web/url/use_store');
        $mage=Mage::getVersion();
        $ext=(string) Mage::getConfig()->getNode()->modules->Wisepricer_Syncer->version;
        $version=array('mage'=>$mage,'ext'=>$ext);

        //getting site url
        $url=$this->getConfigDataByFullPath('web/unsecure/base_url');

        $storesArr=array();
        foreach($websites as $website){
            $code=$website->getCode();
            $stores=$website->getStores();
            foreach($stores as $store){
                $storesArr[$store->getStoreId()]=$store->getData();
            }
        }

        if(count($storesArr)==1){
            try{
                $dataArr = array(
//                         'stores'  => array(array_pop($storesArr)),
                    'stores'  => array_pop($storesArr),
                    'version' => $version
                );
            } catch (Exception $e){
                $dataArr = array(
                    'stores'  => $multistoreData,
                    'version' => $version
                );
            }

            $dataArr['site']  = $url;
            $multistoreJson = json_encode($dataArr);

        }else{

            $storeUrls=$this->getConfigMultiDataByFullPath('web/unsecure/base_url');
            $locales=$this->getConfigMultiDataByFullPath('general/locale/code');
            $storeComplete=array();

            foreach($storesArr as $key=>$value){

                if(!$value['is_active']){
                    continue;
                }

                $storeComplete=$value;
                if(array_key_exists($key,$locales)){
                    $storeComplete['lang']=$locales[$key];
                }else{
                    $storeComplete['lang']=$locales[0];
                }

                if(array_key_exists($key,$storeUrls)){
                    $storeComplete['url']=$storeUrls[$key];
                }else{
                    $storeComplete['url']=$storeUrls[0];
                }

                if($useStoreCode){
                    $storeComplete['url']=$storeUrls[0].$value['code'];
                }

                $multistoreData[]=$storeComplete;
            }

            $dataArr=array(
                'stores'=>$multistoreData,
                'version'=>$version
            );

            $dataArr['site']=$url;

            $multistoreJson=json_encode($dataArr);

        }

        Mage::log($multistoreJson,null,'wplog.log');

        return $multistoreJson;
    }
}