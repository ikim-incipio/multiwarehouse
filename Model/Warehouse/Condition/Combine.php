<?php
class Aitoc_MultiLocationInventory_Model_Warehouse_Condition_Combine extends Mage_SalesRule_Model_Rule_Condition_Combine
{
    public function __construct()
    {
        parent::__construct();
        $this->setType('aitoc_multilocationinventory/warehouse_condition_combine');
    }

    public function getNewChildSelectOptions()
    {
        $addressCondition = Mage::getModel('aitoc_multilocationinventory/warehouse_condition_address');
        $addressAttributes = $addressCondition->loadAttributeOptions()->getAttributeOption();
        $attributes = array();
        foreach ($addressAttributes as $code => $label) {
            $attributes[] = array('value'=>'aitoc_multilocationinventory/warehouse_condition_address|'.$code, 'label'=>$label);
        }

        $conditions = array(
            array('value' => '', 'label' => Mage::helper('rule')->__('Please choose a condition to add...')),
            array('value' => 'salesrule/rule_condition_product_found', 'label' => Mage::helper('salesrule')->__('Product attribute combination')),
            array('value' => 'salesrule/rule_condition_product_subselect', 'label' => Mage::helper('salesrule')->__('Products subselection')),
            array('value' => 'aitoc_multilocationinventory/warehouse_condition_combine', 'label' => Mage::helper('salesrule')->__('Conditions combination')),
            array('value' => $attributes, 'label' => Mage::helper('salesrule')->__('Cart Attribute'))
        );

        $additional = new Varien_Object();
        Mage::dispatchEvent('aitoc_multilocationinventory_warehouse_condition_combine', array('additional' => $additional));
        if ($additionalConditions = $additional->getConditions()) {
            $conditions = array_merge_recursive($conditions, $additionalConditions);
        }

        return $conditions;
    }
}
