<?php

class Wisepricer_Syncer_Model_Mapping extends Mage_Core_Model_Abstract
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('wisepricer_syncer/mapping');
    }
    
    public function loadIdByWsfield($ws_field){
        return $this->_getResource()->loadIdByWsfield($this, $ws_field);        
    }
}

?>