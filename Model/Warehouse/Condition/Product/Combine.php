<?php

class Aitoc_MultiLocationInventory_Model_Warehouse_Condition_Product_Combine extends Mage_SalesRule_Model_Rule_Condition_Product_Combine
{
    /**
     * Initialize a rule condition
     */
    public function __construct()
    {
        parent::__construct();
        $this->setType('aitoc_multilocationinventory/warehouse_condition_product_combine');
    }

    /**
     * Generate a conditions data
     * @return array
     */
    public function getNewChildSelectOptions()
    {
        $conditions = parent::getNewChildSelectOptions();
        $conditions = array_merge_recursive(
            $conditions,
            array(
                array(
                    'label' => Mage::helper('catalog')->__('Conditions Combination'),
                    'value' => 'aitoc_multilocationinventory/warehouse_condition_product_combine'
                ),
                array(
                    'label' => Mage::helper('catalog')->__('Cart Item Attribute'),
                    'value' => $this->_getAttributeConditions(self::PRODUCT_ATTRIBUTES_TYPE_QUOTE_ITEM)
                ),
                array(
                    'label' => Mage::helper('catalog')->__('Product Attribute'),
                    'value' => $this->_getAttributeConditions(self::PRODUCT_ATTRIBUTES_TYPE_PRODUCT),
                ),
                array(
                    'label' => $this->_getHelper()->__('Product Attribute Assigned'),
                    'value' => $this->_getAttributeConditions(self::PRODUCT_ATTRIBUTES_TYPE_ISSET)
                )
            )
        );
        return $conditions;
    }
}
