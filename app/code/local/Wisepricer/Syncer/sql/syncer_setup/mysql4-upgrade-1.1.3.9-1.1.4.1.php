<?php

$installer = $this;

$installer->startSetup();

$installer->run("

ALTER TABLE {$this->getTable('wisepricer_syncer_config')} ADD `import_outofstock` INT( 255 ) NULL default '1';
");

$installer->endSetup();