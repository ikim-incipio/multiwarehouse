<?php
/* @var $this Mage_Core_Model_Resource_Setup */
$this->startSetup();


$createDefaultWarehouse = !$this->tableExists('aitoc_multilocationinventory/warehouse');

$this->run("CREATE TABLE IF NOT EXISTS `" . $this->getTable('aitoc_multilocationinventory/warehouse') . "` (
        `warehouse_id` smallint(5) unsigned NOT NULL AUTO_INCREMENT,
        `code` varchar(128) NOT NULL default '',
        `name` varchar(255) NOT NULL default '',
        `description` varchar(2040) NOT NULL default '',
        `customer_group_ids` varchar(255) NOT NULL default '',
        `conditions_serialized` MEDIUMTEXT NOT NULL,
        `priority` TINYINT(3) unsigned NOT NULL default '1',
        `status` TINYINT(1) NOT NULL DEFAULT '1',
        `contact_name` varchar(255) NOT NULL default '',
        `contact_email` varchar(255) NOT NULL default '',
        `country_id` varchar(2) NOT NULL default '',
        `region_id` smallint(5) unsigned NOT NULL,
        `postcode` varchar(64) NOT NULL default '',
        `city` varchar(64) NOT NULL default '',
        `street` varchar(255) NOT NULL default '',
        `sort_order` TINYINT(3) unsigned NOT NULL,
        PRIMARY KEY (`warehouse_id`),
        UNIQUE KEY `code` (`code`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

$this->run("CREATE TABLE IF NOT EXISTS `" . $this->getTable('aitoc_multilocationinventory/warehouse_store') . "` (
        `store_id` smallint(5) unsigned NOT NULL,
        `warehouse_id` smallint(5) unsigned NOT NULL,
        PRIMARY KEY (`store_id`, `warehouse_id`),
        KEY `warehouse_id` (`warehouse_id`),
        CONSTRAINT `FK_AITOC_MULTILOCATIONINVENTORY_WAREHOUSE_STORE_WAREHOUSE_ID`
            FOREIGN KEY (`warehouse_id`)
            REFERENCES `" . $this->getTable('aitoc_multilocationinventory/warehouse') . "` (`warehouse_id`)
            ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `FK_AITOC_MULTILOCATIONINVENTORY_STORE_ID_CORE_STORE_STORE_ID`
            FOREIGN KEY (`store_id`)
            REFERENCES `" . $this->getTable('core/store') . "` (`store_id`)
            ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

$this->run("CREATE TABLE IF NOT EXISTS `" . $this->getTable('aitoc_multilocationinventory/warehouse_product') . "` (
        `warehouse_id` smallint(5) unsigned NOT NULL,
        `product_id` int(10) unsigned NOT NULL,
        `qty` decimal(12,4) NOT NULL DEFAULT '0.0000',
        PRIMARY KEY (`warehouse_id`, `product_id`),
        KEY `product_id` (`product_id`),
        CONSTRAINT `FK_AITOC_MULTILOCATIONINVENTORY_WAREHOUSE_PRODUCT_WAREHOUSE_ID`
            FOREIGN KEY (`warehouse_id`)
            REFERENCES `" . $this->getTable('aitoc_multilocationinventory/warehouse') . "` (`warehouse_id`)
            ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `FK_AITOC_MULTILOCATIONINVENTORY_WAREHOUSE_PRODUCT_PRODUCT_ID`
            FOREIGN KEY (`product_id`)
            REFERENCES `" . $this->getTable('catalog/product') . "` (`entity_id`)
            ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");


$this->run("CREATE TABLE IF NOT EXISTS `" . $this->getTable('aitoc_multilocationinventory/warehouse_order_item') . "` (
        `order_item_id` int(10) unsigned NOT NULL,
        `warehouse_id` smallint(5) unsigned NOT NULL,
        `qty` decimal(12,4) NOT NULL DEFAULT '0.0000',
        PRIMARY KEY (`order_item_id`, `warehouse_id`),
        KEY `warehouse_id` (`warehouse_id`),
        CONSTRAINT `FK_AITOC_MULTILOCATIONINVENTORY_WAREHOUSE_ORDER_ITEM_ID`
            FOREIGN KEY (`order_item_id`)
            REFERENCES `" . $this->getTable('sales/order_item') . "` (`item_id`)
            ON DELETE CASCADE ON UPDATE CASCADE,
        CONSTRAINT `FK_AITOC_MULTILOCATIONINVENTORY_ORDER_ITEM_WAREHOUSE_ID`
            FOREIGN KEY (`warehouse_id`)
            REFERENCES `" . $this->getTable('aitoc_multilocationinventory/warehouse') . "` (`warehouse_id`)
            ON DELETE CASCADE ON UPDATE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8;");

if ($createDefaultWarehouse) {
    $this->run("INSERT INTO `" . $this->getTable('aitoc_multilocationinventory/warehouse') . "`
        (`warehouse_id`, `code`, `name`, `status`)
        VALUES
        (1, 'default_warehouse', 'Default Warehouse', 1);");

    $this->run("INSERT INTO `" . $this->getTable('aitoc_multilocationinventory/warehouse_store') . "`
        (`store_id`, `warehouse_id`) VALUES (0, 1);");

    $this->run("INSERT IGNORE INTO `" . $this->getTable('aitoc_multilocationinventory/warehouse_product') . "`
        (`warehouse_id`, `product_id`, `qty`)
        SELECT '1' AS warehouse_id, `product_id`, `qty` FROM `" . $this->getTable('cataloginventory/stock_item') . "`;");
}

$this->endSetup();
