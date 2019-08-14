<?php

class Wisepricer_Syncer_Model_Mysql4_Config extends Mage_Core_Model_Mysql4_Abstract
{
     /**
     * Initialize resource
     *
     */
    protected function _construct()
    {
        $this->_init('wisepricer_syncer/wisepricer_syncer_config', 'licensekey_id');
    }   
}
?>