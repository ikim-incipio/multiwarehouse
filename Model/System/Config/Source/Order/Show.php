<?php
class Aitoc_MultiLocationInventory_Model_System_Config_Source_Order_Show
{
    const ON_PAGE = 1;
    const ON_ORDER_ITEM = 2;

    public function toOptionArray()
    {
        $helper = Mage::helper('aitoc_multilocationinventory');
        return array(
            array('value' => 0, 'label' => $helper->__('None')),
            array('value' => self::ON_PAGE, 'label' => $helper->__('On Order Page')),
            array('value' => self::ON_ORDER_ITEM, 'label' => $helper->__('On each Order Item'))
        );
    }
}
