<?php

class Aitoc_MultiLocationInventory_Model_Warehouse extends Mage_SalesRule_Model_Rule //Mage_Rule_Model_Rule //Mage_Core_Model_Abstract
{
    
    protected $_eventPrefix = 'warehouse_rule';
    
    public function _construct() {
        parent::_construct();
	    $this->_init('aitoc_multilocationinventory/warehouse');
        $this->setIdFieldName('warehouse_id');
    }
    
    public function getConditionsInstance() {
        return Mage::getModel('aitoc_multilocationinventory/warehouse_condition_combine');
    }

    public function getActionsInstance() {
        return Mage::getModel('aitoc_multilocationinventory/warehouse_condition_product_combine');
    }
    
    protected function _beforeSave() {
        parent::_beforeSave();
        if (is_array($this->getCustomerGroupIds())) {
            $this->setCustomerGroupIds(join(',', $this->getCustomerGroupIds()));
        }        
    }
    
    // standart magento _afterSave
    protected function _afterSave() {
        $this->cleanModelCache();
        Mage::dispatchEvent('model_save_after', array('object'=>$this));
        Mage::dispatchEvent($this->_eventPrefix.'_save_after', $this->_getEventData());
        return $this;
    }
    
    public function getCustomerGroupIds() {
        $customerGroupIds = $this->getData('customer_group_ids');
        if (is_null($customerGroupIds) || $customerGroupIds==='') return array();
        if (is_string($customerGroupIds)) $this->setData('customer_group_ids', explode(',', $customerGroupIds));        
        return $this->getData('customer_group_ids');
    }

    public function saveQty($qty = null) {
        $this->getResource()->saveQty($this, $qty);
        return $this;
    }

    public function changeQty($qty = null) {
        $this->getResource()->changeQty($this, $qty);
        return $this;
    }

    public function saveUsedInOrderItemQty($orderItemId, $qty) {
        $this->getResource()->saveUsedInOrderItemQty($this, $orderItemId, $qty);
        return $this;
    }

    public function getWarehouseId($orderItemId){
        return $this->getResource()->getWarehouseId($orderItemId);
    }
}
