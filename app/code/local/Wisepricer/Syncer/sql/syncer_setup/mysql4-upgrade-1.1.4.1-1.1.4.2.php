<?php

$installer = $this;

$installer->startSetup();

$insertStr='';

try{

    $dt=strtotime('-30 days');
    $mysqldate = date( 'Y-m-d H:i:s', $dt );

    $orders=Mage::getModel('sales/order')->getCollection()
        ->addFieldToFilter('status',array('in'=> array('processing', 'processed', 'complete')))
        ->addFieldToFilter('created_at',array('gt'=>$mysqldate));

    foreach($orders as $order){

        $insertStr.= 'INSERT INTO '.$this->getTable('wisepricer_syncer_sales').' ( `sales_id` ,`order_id` ,`status`,order_date) VALUES (NULL , '.$order->getIncrementId().', "0","'.$order->getCreatedAt().'");';
    }

}catch(Exception $e){

}

$installer->run("


DROP TABLE IF EXISTS {$this->getTable('wisepricer_syncer_hits_counter')};

CREATE TABLE IF NOT EXISTS {$this->getTable('wisepricer_syncer_hits_counter')} (

  `hits_id` int(11) NOT NULL auto_increment,

  `sku` varchar(255) character set utf8 NOT NULL,

  `hits` int(11) unsigned NOT NULL default '0',

  `hit_date` date DEFAULT NULL,

   PRIMARY KEY  (`hits_id`)

) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

DROP TABLE IF EXISTS {$this->getTable('wisepricer_syncer_sales')};

CREATE TABLE IF NOT EXISTS {$this->getTable('wisepricer_syncer_sales')} (

      `sales_id` int(11) NOT NULL auto_increment,

      `order_id`  varchar( 1024 )  NOT NULL,

      `status` int(1) unsigned NOT NULL default '0',

      `order_date` date DEFAULT NULL,

       PRIMARY KEY  (`sales_id`)

  ) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
  
  ".$insertStr."
");

$installer->endSetup();