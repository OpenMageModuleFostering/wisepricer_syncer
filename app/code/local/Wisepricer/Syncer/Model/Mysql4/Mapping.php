<?php

class Wisepricer_Syncer_Model_Mysql4_Mapping extends Mage_Core_Model_Mysql4_Abstract
{
     /**
     * Initialize resource
     *
     */
    protected function _construct()
    {
        $this->_init('wisepricer_syncer/wisepricer_syncer_mapping', 'mapping_id');
    }
    
    public function loadIdByWsfield(Wisepricer_Syncer_Model_Mapping $mapping, $wsfield, $testOnly = false)
    {  
        $adapter = $this->_getReadAdapter();
        $tableName = Mage::getSingleton('core/resource')->getTableName('wisepricer_syncer_mapping');
        $bind    = array('wsp_field' => $wsfield);
        $select  = $adapter->select()
            ->from($tableName)
            ->where('wsp_field = :wsp_field');



        $mappingId = $adapter->fetchOne($select, $bind);

        return $mappingId;

        
    }
}
?>