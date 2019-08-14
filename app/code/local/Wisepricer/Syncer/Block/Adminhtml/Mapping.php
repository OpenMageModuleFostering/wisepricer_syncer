<?php

/******************************************
*        wisepricer         *
******************************************/

class Wisepricer_Syncer_Block_Adminhtml_Mapping extends Mage_Adminhtml_Block_Widget_Form_Container
{
    
    public function getHeader(){
        $header = "Wisepricer Mapping";
        return $header;
    }
    
    public function renderAttributesSelect($name,$id,$value=0,$class=''){

      $attributesModel=Mage::getModel('wisepricer_syncer/adminhtml_attributes');

      $options=$attributesModel->toOptionArray();

      $mappingModel=Mage::getModel('wisepricer_syncer/mapping');

      $selectHtml='<select style="width:209px" class="select '.$class.'" name="'.$name.'" id="'.$id.'">';
      
      $row=$mappingModel->load($id,'wsp_field');
      if($row->getData()){
        $value=$row->getmagento_field();
      }
      foreach($options as $option){
       
        if($option['value']==$value){
          $selected='selected';
        }else{
          $selected='';
        } 
        
        if($option['value']=='enable_googlecheckout'){
           continue;
        }
        
        $selectHtml.='<option value="'.$option['value'].'" '.$selected.'>';
        $selectHtml.=$option['label'];        
        $selectHtml.='</option>';
      }
      $selectHtml.='</select>';
      
      return $selectHtml;
    }

    public function renderPriceAttributesSelect($name,$id,$value=0,$class=''){

        $attributesModel=Mage::getModel('wisepricer_syncer/adminhtml_attributes');

        $options=$attributesModel->getDecimalOptions();

        $mappingModel=Mage::getModel('wisepricer_syncer/mapping');

        $selectHtml='<select style="width:209px" class="select '.$class.'" name="'.$name.'" id="'.$id.'">';

        $row=$mappingModel->load($id,'wsp_field');
        if($row->getData()){
            $value=$row->getmagento_field();
        }
        foreach($options as $option){

            if($option['value']==$value){
                $selected='selected';
            }else{
                $selected='';
            }

            $selectHtml.='<option value="'.$option['value'].'" '.$selected.'>';
            $selectHtml.=$option['label'];
            $selectHtml.='</option>';
        }
        $selectHtml.='</select>';

        return $selectHtml;
    }

    public function renderSkuIdSelect($name,$id,$value=0,$class=''){

        $options=array();
        $options[]=array('value'=>'entity_id','label'=>'Product ID');
        $options[]=array('value'=>'sku','label'=>'SKU');

        $mappingModel=Mage::getModel('wisepricer_syncer/mapping');
        $selectHtml='<select style="width:209px" class="select '.$class.'" name="'.$name.'" id="'.$id.'">';

        $row=$mappingModel->load($id,'wsp_field');
        if($row->getData()){
            $value=$row->getmagento_field();
        }
        foreach($options as $option){

            if($option['value']==$value){
                $selected='selected';
            }else{
                $selected='';
            }


            $selectHtml.='<option value="'.$option['value'].'" '.$selected.'>';
            $selectHtml.=$option['label'];
            $selectHtml.='</option>';
        }
        $selectHtml.='</select>';

        return $selectHtml;
    }

    public function getShippingFixedRate(){

      $shipping=$this->_getMagentoFieldByWsField('shipping');
      if($shipping&&is_numeric($shipping)){
        return $shipping;
      }
      
      return '';
    }
    
    public function getFixedMinPrice(){
      
      $minprice=$this->_getMagentoFieldByWsField('minprice'); 
      if($minprice&&is_numeric($minprice)){
        return $minprice;
      }
      
      return '';
    }
    
    public function renderMinPriceRuleSelects(){
        
     $model = Mage::getModel('wisepricer_syncer/mapping');
     $mappingId=$model->loadIdByWsfield('minprice');
     $valuesArr=array('','');
     
     if($mappingId){
        $minpriceRow=$model->load($mappingId);

        $extra=$minpriceRow->getExtra();
        if($extra){
            $valuesArr=explode(':',$extra);
        }else{
            $valuesArr=array('1','a');
        }


     }else{
       $valuesArr=array('1','a');
     }
 
     $cur=Mage::app()->getLocale()->currency(Mage::app()->getStore()->getCurrentCurrencyCode())->getSymbol();
     
     $typeOptions=array(
         array('label'=>'%','value'=>'-1'),
         array('label'=>$cur,'value'=>'1')        
     );
     
     $html=$this->_getCustomSelect('mapping_form[type]',$typeOptions,$valuesArr[0],'chzn-select function-select','width:50px');
     
     $ruleOptions=array(
         array('label'=>'Above cost','value'=>'a'),
         array('label'=>'Below price','value'=>'b')
     );

     $html.=$this->_getCustomSelect('mapping_form[rule]',$ruleOptions,$valuesArr[1],'chzn-select function-select','width:136px');
 
     return $html;
    }
    
    public function renderWebsitesSelect($selected=0){
    
      $switcher=new Mage_Adminhtml_Block_Store_Switcher;
      $websites = $switcher->getWebsites();
      
      $options=array();
      $options[]=array('label'=>'All','value'=>0);
      foreach($websites as $website){          
        foreach($website->getGroups() as $group){   

             $name=$group->getName();
             $options[]=array('label'=>$name,'value'=>$group->getdefault_store_id());

        }
      }

      
      $html=$this->_getCustomSelect('register_form[website]',$options,$selected,'chzn-select function-select','width:250px');
      return $html;
    }
    
    public function renderTypesSelect($selected='simple'){

      $options=array();
      $options[]=array('label'=>'All','value'=>'all');
      $options[]=array('label'=>'Simple','value'=>'simple');
      $options[]=array('label'=>'Configurable','value'=>'configurable');
      $options[]=array('label'=>'Bundle','value'=>'bundle');
      $options[]=array('label'=>'Grouped','value'=>'grouped');
      
      $html=$this->_getCustomSelect('register_form[product_type]',$options,$selected,'chzn-select function-select','width:250px');
      return $html;
    }
    
    private function _getCustomSelect($name,$options,$default=0,$class='',$style='',$id=''){
        
       $select='<select name="'.$name.'" id="'.$id.'" class="'.$class.'" style="'.$style.'">';
       foreach ($options as $option) {
          $selected='';
          if($option['value']==$default){
            $selected='selected';  
          }
          $select.='<option value="'.$option['value'].'" '.$selected.'>'.$option['label'].'</option>';
       }
       
       $select.='</select>';
       
       return $select;
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

    public function getImportOutStockSet(){
        $model   = Mage::getModel('wisepricer_syncer/config');
        $lisenceData=$model->load(1);
        if($lisenceData->getImport_outofstock()!='0'){
           return 'checked';
        }else{
            return '';
        }
    }
}
?>
