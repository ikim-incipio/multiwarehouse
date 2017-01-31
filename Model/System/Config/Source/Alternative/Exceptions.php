<?php

class Aitoc_MultiLocationInventory_Model_System_Config_Source_Alternative_Exceptions
{
    const STORE_VIEW = 1;
    const CUSTOMER_GROUPS = 2;
    const CONDITIONS = 3;

    public function toOptionArray()
    {
        $helper = Mage::helper('aitoc_multilocationinventory');
        return array(
            array('value' => 0, 'label' => $helper->__('None')),
            array('value' => self::STORE_VIEW, 'label' => $helper->__('Store View')),
            array('value' => self::CUSTOMER_GROUPS, 'label' => $helper->__('Customer Groups')),
            array('value' => self::CONDITIONS, 'label' => $helper->__('Conditions'))
        );
    }
}
