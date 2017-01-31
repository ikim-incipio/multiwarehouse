<?php

class Aitoc_MultiLocationInventory_Model_Mysql4_Warehouse_Collection extends Mage_Core_Model_Mysql4_Collection_Abstract
{
    public function _construct() {
        $this->_init('aitoc_multilocationinventory/warehouse');
    }

    public function addStoreFilter($storeId = null) {
        if (is_null($storeId)) {
            $storeId = Mage::app()->getStore()->getId();
        }

        if (!is_array($storeId)) {
            $storeId = array($storeId);
        }

        // all stores
        $storeId[] = 0;

        $this->getSelect()
            ->join(
                array('warehouse_store_tbl' => $this->getTable('aitoc_multilocationinventory/warehouse_store')),
                'main_table.warehouse_id = warehouse_store_tbl.warehouse_id',
                array()
            )
            ->where('warehouse_store_tbl.store_id IN (?)', ($storeId))
            ->group('main_table.warehouse_id');
        return $this;
    }

    public function addOrderItemFilter($orderItemsIds) {
        if (!is_array($orderItemsIds)) {
            $orderItemsIds = array($orderItemsIds);
        }
        $this->getSelect()
            ->join(
                array('warehouse_order_item_tbl' => $this->getTable('aitoc_multilocationinventory/warehouse_order_item')),
                'main_table.warehouse_id = warehouse_order_item_tbl.warehouse_id',
                array('ordered_qty' => 'SUM(qty)')
            )
            ->where('warehouse_order_item_tbl.order_item_id IN (?)', ($orderItemsIds))
            ->group('main_table.warehouse_id');
        return $this;
    }

    public function addOrderItemData($orderItemsId) {
        $this->getSelect()
            ->joinLeft(
                array('warehouse_order_item_tbl' => $this->getTable('aitoc_multilocationinventory/warehouse_order_item')),
                'main_table.warehouse_id = warehouse_order_item_tbl.warehouse_id ' .
                'AND warehouse_order_item_tbl.order_item_id = ' . intval($orderItemsId),
                array('ordered_qty' => 'SUM(warehouse_order_item_tbl.qty)')
            )
            ->group('main_table.warehouse_id');
        return $this;
    }

    public function addStoreData() {
        $this->getSelect()
            ->joinLeft(
                array('warehouse_store_tbl' => $this->getTable('aitoc_multilocationinventory/warehouse_store')),
                'main_table.warehouse_id = warehouse_store_tbl.warehouse_id',
                array(new Zend_Db_Expr('GROUP_CONCAT(warehouse_store_tbl.`store_id`) AS store_ids'))
            )
            ->group('main_table.warehouse_id');
        return $this;
    }
    
    public function addStatusFilter() {
        $this->getSelect()->where('main_table.status = 1');
        return $this;
    }

    public function addWarehouseFilter($warehouseIds) {
        if ($warehouseIds) {
            $this->getSelect()->where('main_table.warehouse_id IN (?)', $warehouseIds);
        }
        return $this;
    }

    public function addProductData($productId = 0) {
        $productId = intval($productId);
        $this->getSelect()
            ->columns( new Zend_Db_Expr('"' . $productId . '" AS product_id'))
            ->joinLeft(
                array('warehouse_product_tbl' => $this->getTable('aitoc_multilocationinventory/warehouse_product')),
                'main_table.warehouse_id = warehouse_product_tbl.warehouse_id AND warehouse_product_tbl.product_id = '. $productId,
                array('qty')
        );
        return $this;
    }

    public function sortByPriority() {
        $this->getSelect()->order('main_table.priority ASC');
        return $this;
    }

    public function sortByQuantity() {
        $this->getSelect()->order('warehouse_product_tbl.qty DESC');
        return $this;
    }
        
    public function addSortOrder() {
        $this->getSelect()->order('main_table.sort_order ASC');
        return $this;
    }
}
