<?php
class Aitoc_MultiLocationInventory_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function canUseAlternativeWarehouse()
    {
        return Mage::getStoreConfigFlag('aitoc_multilocationinventory/general/use_alternative_warehouse');
    }

    public function getAlternativeWarehouseExceptions()
    {
        return Mage::getStoreConfig('aitoc_multilocationinventory/general/alternative_warehouse_exceptions');
    }

    public function canUseDifferentWarehouses()
    {
        return Mage::getStoreConfigFlag('aitoc_multilocationinventory/general/use_in_order_different_warehouses');
    }

    public function getShowWarehouseInOrder()
    {
        return Mage::getStoreConfig('aitoc_multilocationinventory/general/show_warehouse_in_order');
    }

    public function canEditWarehouseInOrder()
    {
        return Mage::getStoreConfigFlag('aitoc_multilocationinventory/general/edit_warehouse_in_order');
    }
    
    public function getShowWarehouseInOrderGrid()
    {
        return Mage::getStoreConfigFlag('aitoc_multilocationinventory/general/show_warehouse_in_order_grid');
    }
}
