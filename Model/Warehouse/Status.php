<?php

class Aitoc_MultiLocationInventory_Model_Warehouse_Status
{
    public function toArray() {
        $helper = Mage::helper('aitoc_multilocationinventory');
        return array(
            1 => $helper->__('Active'),
            0 => $helper->__('Inactive'),
        );
    }
}
