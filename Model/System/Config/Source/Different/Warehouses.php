<?php

class Aitoc_MultiLocationInventory_Model_System_Config_Source_Different_Warehouses
{
    const FOR_SAME_PRODUCT = 1;
    const FOR_DIFFERENT_PRODUCT = 2;
    const FOR_BUNDLE = 3;

    public function toOptionArray()
    {
        $helper = Mage::helper('aitoc_multilocationinventory');
        return array(
            array('value' => self::FOR_SAME_PRODUCT, 'label' => $helper->__('Missing items of the same product')),
            array('value' => self::FOR_DIFFERENT_PRODUCT, 'label' => $helper->__('Different simple products')),
            array('value' => self::FOR_BUNDLE, 'label' => $helper->__('Simple products as parts of bundle products'))
        );
    }
}
