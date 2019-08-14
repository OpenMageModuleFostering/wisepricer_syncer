<?php

$installer = $this;

$installer->startSetup();

$installer->run("

CREATE TABLE IF NOT EXISTS {$this->getTable('wisepricer_syncer_hits_counter')} (

  `hits_id` int(11) NOT NULL auto_increment,

  `sku` varchar(255) character set utf8 NOT NULL,

  `hits` int(11) unsigned NOT NULL default '0',

   PRIMARY KEY  (`hits_id`)

) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS {$this->getTable('wisepricer_syncer_sales')} (

      `sales_id` int(11) NOT NULL auto_increment,

      `order_id`  varchar( 1024 )  NOT NULL,

      `status` int(1) unsigned NOT NULL default '0',

       PRIMARY KEY  (`sales_id`)

  ) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

");

$installer->endSetup();