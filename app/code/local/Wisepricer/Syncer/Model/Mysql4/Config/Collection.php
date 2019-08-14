<?php

class Wisepricer_Syncer_Model_Mysql4_Config_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
        /**
     * Initialize collection
     *
     */
    public function _construct()
    {
        $this->_init('wisepricer_syncer/wisepricer_syncer_config');
    }
}

?>