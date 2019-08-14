<?php

class Wisepricer_Syncer_Model_Config extends Mage_Core_Model_Abstract
{

    public function _construct()
    {
        parent::_construct();
        $this->_init('wisepricer_syncer/config');
    }
}

?>