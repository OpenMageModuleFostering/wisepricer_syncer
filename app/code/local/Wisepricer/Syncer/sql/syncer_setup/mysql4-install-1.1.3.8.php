<?php

$installer = $this;

$installer->startSetup();

$installer->run("

CREATE TABLE IF NOT EXISTS {$this->getTable('wisepricer_syncer_config')} (

  `licensekey_id` int(11) NOT NULL auto_increment,

  `licensekey` varchar(255) character set utf8 NOT NULL,

  `is_confirmed` smallint(5) unsigned NOT NULL default '0',

  `publickey` text character set utf8 NOT NULL,

  `privatekey` text character set utf8 NOT NULL,

  `website` int(11) NOT NULL DEFAULT '0',

  `product_type` varchar(255) CHARACTER SET utf8 NOT NULL DEFAULT 'simple',

  `reprice_configurable` int(1) NOT NULL DEFAULT '1',

   PRIMARY KEY  (`licensekey_id`)

) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS {$this->getTable('wisepricer_syncer_mapping')} (

      `mapping_id` int(11) NOT NULL auto_increment,

      `wsp_field` varchar(255) character set utf8 NOT NULL,

      `magento_field` varchar(255) character set utf8 NOT NULL,

      `extra` varchar(255) character set utf8 NULL,

       PRIMARY KEY  (`mapping_id`)

  ) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

");

$installer->endSetup(); 