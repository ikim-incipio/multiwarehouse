<?php

class Aitoc_MultiLocationInventory_Model_Mysql4_Warehouse extends Mage_Core_Model_Mysql4_Abstract
{
    public function _construct() {
        $this->_init('aitoc_multilocationinventory/warehouse', 'warehouse_id');
    }
    
    protected function _afterLoad(Mage_Core_Model_Abstract $object) {
        $read = $this->_getReadAdapter();
        
        // load stores
        $select = $read->select()
                ->from($this->getTable('aitoc_multilocationinventory/warehouse_store'))
                ->where('warehouse_id = ?', $object->getId());
        $data = $read->fetchAll($select);
        if ($data) {
            $storesArray = array();
            foreach ($data as $row) {
                $storesArray[] = $row['store_id'];
            }
            $object->setData('store_id', $storesArray);
        }
        
        return parent::_afterLoad($object);
    }

    public function saveQty(Mage_Core_Model_Abstract $object, $qty = null) {
        $write = $this->_getWriteAdapter();
        $write->insertOnDuplicate(
            $this->getTable('aitoc_multilocationinventory/warehouse_product'),
            array('warehouse_id' => $object->getId(), 'product_id' => $object->getProductId(), 'qty' => $qty),
            array('qty')
        );
    }

    public function changeQty(Mage_Core_Model_Abstract $object, $qty = null) {
        $write = $this->_getWriteAdapter();
        $write->insertOnDuplicate(
            $this->getTable('aitoc_multilocationinventory/warehouse_product'),
            array('warehouse_id' => $object->getId(), 'product_id' => $object->getProductId(), 'qty' => $qty),
            array('qty' => new Zend_Db_Expr('`qty` + ' . $qty))
        );
    }

    public function saveUsedInOrderItemQty(Mage_Core_Model_Abstract $object, $orderItemId, $qty) {
        $write = $this->_getWriteAdapter();
        $table = $this->getTable('aitoc_multilocationinventory/warehouse_order_item');
        $write->insertOnDuplicate(
            $table,
            array('order_item_id' => $orderItemId, 'warehouse_id' => $object->getId(), 'qty' => $qty),
            array('qty' => new Zend_Db_Expr('`qty` + ' . $qty))
        );

        if ($qty < 0) {
            $select = $write->select()
                ->from($table, array('qty'))
                ->where('warehouse_id = ?', $object->getId())
                ->where('order_item_id = ?', $orderItemId);
            $currentQty = $write->fetchOne($select);
            if ($currentQty <= 0) {
                $write->delete($table, 'warehouse_id = ' . $object->getId() . ' AND order_item_id = ' . $orderItemId);
            }
        }
    }

    public function getWarehouseId($orderItemId){
        $read = $this->_getReadAdapter();

        $select = $read->select()
                ->from($this->getTable('aitoc_multilocationinventory/warehouse_order_item'))
                ->where('order_item_id = ?', $orderItemId);
        $data = $read->fetchAll($select);
        if ($data) {
            foreach ($data as $row) {
                return $row['warehouse_id'];
            }
        }
        
        return 0;
    }
}
