<?php
if (Mage::getConfig()->getModuleConfig('Aitoc_Aitpreorder')->is('active', 'true')){
    class Aitoc_MultiLocationInventory_Model_Rewrite_FrontCatalogInventoryStockItem_Aittmp extends Aitoc_Aitpreorder_Model_Rewrite_StockItem {}
 } else {
    /* default extends start */
    class Aitoc_MultiLocationInventory_Model_Rewrite_FrontCatalogInventoryStockItem_Aittmp extends Mage_CatalogInventory_Model_Stock_Item {}
    /* default extends end */
}

class Aitoc_MultiLocationInventory_Model_Rewrite_FrontCatalogInventoryStockItem extends Aitoc_MultiLocationInventory_Model_Rewrite_FrontCatalogInventoryStockItem_Aittmp
{
    protected function _beforeSave()
    {
        // catalog product save (from admin)
        if ($this->getProduct()) {
            $stockData = $this->getProduct()->getStockData();
            if ($stockData && isset($stockData['warehouse_qty']) && $stockData['warehouse_qty']) {

                $warehouses = Mage::getResourceModel('aitoc_multilocationinventory/warehouse_collection')
                    ->addProductData($this->getProductId());
                $totalQty = 0;
                foreach ($warehouses as $warehouse) {
                    if (isset($stockData['warehouse_qty'][$warehouse->getId()])) {
                        $qty = floatval($stockData['warehouse_qty'][$warehouse->getId()]);
                        $totalQty += $qty;
                        $warehouse->saveQty($qty);
                    } else {
                        $totalQty += $warehouse->getQty();
                    }
                }
                $this->setQty($totalQty);
            }
        }

        // catalog action attribute save (from admin)
        if (is_array($this->getWarehouseQty()) && $this->getWarehouseQty()
            && is_array($this->getOriginalWarehouseQty())
        ) {
            $warehouseQtyData = $this->getWarehouseQty();
            $warehouses = Mage::getResourceModel('aitoc_multilocationinventory/warehouse_collection')
                ->addProductData($this->getProductId());
            $totalQty = 0;
            foreach ($warehouses as $warehouse) {
                if (isset($warehouseQtyData[$warehouse->getId()])) {
                    $qty = floatval($warehouseQtyData[$warehouse->getId()]);
                    $totalQty += $qty;
                    $warehouse->saveQty($qty);
                } else {
                    $totalQty += $warehouse->getQty();
                }
            }
            $this->setQty($totalQty);
        }

        // subtract Qty
        if ($this->getSubtractWarehouseQty() && $this->getWarehouseIds()) {
            $subtractQty = $this->getSubtractWarehouseQty();

            foreach($this->getWarehouseIds() as $warehouseId => $qty) {
                $warehouse = Mage::getResourceModel('aitoc_multilocationinventory/warehouse_collection')
                    ->addWarehouseFilter($warehouseId)
                    ->addProductData($this->getProductId())
                    ->getFirstItem();

                if (!$warehouse->getId()) {
                    continue;
                }

                if ($qty) {
                    if ($this->getOrderItemId()) {
                        $warehouse->saveUsedInOrderItemQty($this->getOrderItemId(), $qty);
                    }
                    $warehouse->changeQty($qty * -1);
                    $subtractQty -= $qty;

                } elseif ($warehouse->getQty() - $this->getMinQty() >= $subtractQty) {
                    if ($this->getOrderItemId()) {
                        $warehouse->saveUsedInOrderItemQty($this->getOrderItemId(), $subtractQty);
                    }
                    $warehouse->changeQty($subtractQty * -1);
                    $subtractQty = 0;
                } else {
                    if ($this->getOrderItemId()) {
                        $warehouse->saveUsedInOrderItemQty(
                            $this->getOrderItemId(),
                            $warehouse->getQty() - $this->getMinQty()
                        );
                    }
                    $warehouse->changeQty(($warehouse->getQty() - $this->getMinQty()) * -1);
                    $subtractQty -= $warehouse->getQty() - $this->getMinQty();
                }

                if ($subtractQty==0) {
                    break;
                }
            }

            if ($subtractQty != 0 && $this->getBackorders()) {
                if ($this->getOrderItemId()) {
                    $warehouse->saveUsedInOrderItemQty($this->getOrderItemId(), $subtractQty);
                }
                $warehouse->changeQty($subtractQty * -1);
                $subtractQty = 0;
            }

            $this->setSubtractWarehouseQty($subtractQty);
        }

        return parent::_beforeSave();
    }

    /**
     * Add product data to stock item
     *
     * @param Mage_Catalog_Model_Product $product
     * @return Mage_CatalogInventory_Model_Stock_Item
     */
    public function setProduct($product)
    {
        parent::setProduct($product);

        if (Mage::app()->getStore()->isAdmin() &&
            Mage::app()->getRequest()->getControllerName() != 'sales_order_create'
        ) {
            return $this;
        }

        if ($product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_CONFIGURABLE ||
            $product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_BUNDLE ||
            $product->getTypeId() == Mage_Catalog_Model_Product_Type::TYPE_GROUPED
        ) {
            return $this;
        }

        $helper = Mage::helper('aitoc_multilocationinventory');

        if (Mage::app()->getStore()->isAdmin()) {
            $session = Mage::getSingleton('adminhtml/session_quote');
        } else {
            $session = Mage::getSingleton('checkout/session');
        }

        $quote = $session->getQuote();
        $warehouseId = $quote->getWarehouseId();
        $warehouseIds = array();
        if ($warehouseId) {
            $warehouseIds[] = $warehouseId;
        }

        // get maximum-possible quantity
        if ($helper->canUseAlternativeWarehouse()) {
            $warehouses = $this->getAlternativeWarehouses($warehouseIds);
        } else {
            $warehouses = $this->getAllowedWarehouses($warehouseIds);
        }

        $this->setWarehouses($warehouses);

        // set one warehouse with max qty
        if (count($warehouses) > 1 && !$helper->canUseDifferentWarehouses()) {
            $maxQty = 0;
            $warehouseByMaxQty = null;
            foreach($warehouses as $warehouse) {
                if ($warehouse->getQty() > $maxQty) {
                    $maxQty = $warehouse->getQty();
                    $warehouseByMaxQty = $warehouse;
                }
            }
            $warehouses = array($warehouseByMaxQty);
        }

        if ($warehouses) {
            $qty = 0;
            $warehouseIds = array();
            foreach($warehouses as $warehouse) {
                $qty += $warehouse->getQty();
                $warehouseIds[$warehouse->getId()] = false;
            }

            //$this->setQty($qty);
            $this->setWarehouseQty($qty);
            $this->setWarehouseIds($warehouseIds);

            return $this;
        }

        $this->setWarehouseQty($this->getMinQty());
        $this->setWarehouseIds(array());
        $this->setQty($this->getMinQty());
        $this->setStockStatus(false);

        return $this;
    }

    public function getAllowedWarehouses($warehouseIds = array()) {
        // get customerGroupId
        if (Mage::app()->getStore()->isAdmin()) {
            if (Mage::getSingleton('adminhtml/session_quote')) {
                $customerGroupId = Mage::getSingleton('adminhtml/session_quote')->getCustomer()->getGroupId();
            } else {
                $customerGroupId = Mage_Customer_Model_Group::NOT_LOGGED_IN_ID;;
            }
        } else {
            $customerGroupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
        }

        $warehouses = Mage::getResourceModel('aitoc_multilocationinventory/warehouse_collection')
            ->addWarehouseFilter($warehouseIds)
            ->addStatusFilter()
            ->addStoreFilter()
            ->addProductData($this->getProductId())
            ->sortByPriority()
            ->sortByQuantity();

        $allowedWarehouses = array();

        if (Mage::app()->getStore()->isAdmin()) {
            $session = Mage::getSingleton('adminhtml/session_quote');
        } else {
            $session = Mage::getSingleton('checkout/session');
        }
        $address = $this->getSalesAddress($session->getQuote());

        foreach($warehouses as $warehouse) {
            if ($warehouse->getQty() <= $this->getMinQty()) {
                continue;
            }

            $customerGroupIds = $warehouse->getCustomerGroupIds();
            if ($customerGroupIds && in_array($customerGroupId, $customerGroupIds)) {
                continue;
            }

            // check conditions
            $conditions = unserialize($warehouse->getConditionsSerialized());
            if ($conditions) {
                $conditionModel = Mage::getModel($conditions['type'])->setPrefix('conditions')->loadArray($conditions);
                if (!$conditionModel->validate($address)) {
                    continue;
                }
            }

            $allowedWarehouses[] = $warehouse;
        }

        return $allowedWarehouses;
    }

    public function getAlternativeWarehouses($warehouseIds = array()) {
        // get customerGroupId
        if (Mage::app()->getStore()->isAdmin()) {
            if (Mage::getSingleton('adminhtml/session_quote')) {
                $customerGroupId = Mage::getSingleton('adminhtml/session_quote')->getCustomer()->getGroupId();
            } else {
                $customerGroupId = Mage_Customer_Model_Group::NOT_LOGGED_IN_ID;;
            }
        } else {
            $customerGroupId = Mage::getSingleton('customer/session')->getCustomerGroupId();
        }

        $warehouses = Mage::getResourceModel('aitoc_multilocationinventory/warehouse_collection')
            ->addWarehouseFilter($warehouseIds)
            ->addStatusFilter()
            ->addProductData($this->getProductId())
            ->addStoreData()
            ->sortByPriority()
            ->sortByQuantity();

        $exceptions = explode(',', Mage::helper('aitoc_multilocationinventory')->getAlternativeWarehouseExceptions());

        $allowedWarehouses = array();
        $allowedAlternativeWarehouses = array();

        if (Mage::app()->getStore()->isAdmin()) {
            $session = Mage::getSingleton('adminhtml/session_quote');
        } else {
            $session = Mage::getSingleton('checkout/session');
        }
        $address = $this->getSalesAddress($session->getQuote());

        foreach($warehouses as $warehouse) {
            if ($warehouse->getQty() <= $this->getMinQty()) {
                continue;
            }

            $storeIds = explode(',', $warehouse->getStoreIds());
            /* fixed by Aitoc on 1-19-2017 - $this ==> $session
             * resolves issue where admin could not place manual order from backend
             */
            /* additional fix by Isaac on 1-19-2017 - apply Aitoc fix for only admin
             * $session->getStoreId() does not work for frontend
             * it makes the system into thinking that all products are out of stock
             */
            /* updated by Isaac on 1-31-2017
             * if it does not meet store view exception setNotIncluded
             */
            if (Mage::app()->getStore()->isAdmin()) {
                if (!in_array(0, $storeIds) && !in_array($session->getStoreId(), $storeIds)) {
                    if (!in_array(Aitoc_MultiLocationInventory_Model_System_Config_Source_Alternative_Exceptions::STORE_VIEW, $exceptions)) {
                        continue;
                    }
                    //$warehouse->setIsAlternative(1);
                    $warehouse->setNotIncluded(1);
                }
            } else {
                if (!in_array(0, $storeIds) && !in_array($this->getStoreId(), $storeIds)) {
                    if (!in_array(Aitoc_MultiLocationInventory_Model_System_Config_Source_Alternative_Exceptions::STORE_VIEW, $exceptions)) {
                        continue;
                    }
                    //$warehouse->setIsAlternative(1);
                    $warehouse->setNotIncluded(1);
                }
            }

            $customerGroupIds = $warehouse->getCustomerGroupIds();
            if ($customerGroupIds && !in_array($customerGroupId, $customerGroupIds)) {
                if (!in_array(Aitoc_MultiLocationInventory_Model_System_Config_Source_Alternative_Exceptions::CUSTOMER_GROUPS, $exceptions)) {
                    continue;
                }
                $warehouse->setIsAlternative(1);
            }

            // check conditions
            $conditions = unserialize($warehouse->getConditionsSerialized());
            if ($conditions) {
                $conditionModel = Mage::getModel($conditions['type'])->setPrefix('conditions')->loadArray($conditions);
                if (!$conditionModel->validate($address)) {
                    if (!in_array(Aitoc_MultiLocationInventory_Model_System_Config_Source_Alternative_Exceptions::CONDITIONS, $exceptions)) {
                        continue;
                    }
                    $warehouse->setIsAlternative(1);
                }
            }

            // custom code by Isaac on 1-31-2017 - do not include if it doesn't meet store view exception
            if (!$warehouse->getNotIncluded()){
                if ($warehouse->getIsAlternative()) {
                    $allowedAlternativeWarehouses[] = $warehouse;
                } else {
                    $allowedWarehouses[] = $warehouse;
                }
            }

        }

        return array_merge($allowedWarehouses, $allowedAlternativeWarehouses);
    }

    public function getSalesAddress($sales) {
        $address = $sales->getShippingAddress();
        if ($address->getSubtotal()==0) {
            $address = $sales->getBillingAddress();
        }
        return $address;
    }

    /**
     * Subtract quote item quantity
     *
     * @param   decimal $qty
     * @return  Mage_CatalogInventory_Model_Stock_Item
     */
    public function subtractQty($qty)
    {
        if ($this->canSubtractQty()) {
            $this->setQty(parent::getQty() - $qty);
            $this->setSubtractWarehouseQty(floatval($this->getSubtractWarehouseQty()) + $qty);
        }
        return $this;
    }

    public function getQty()
    {
        if (!is_null($this->getWarehouseQty())) {
            return $this->getWarehouseQty();
        }
        return parent::getQty();
    }

    /**
     * Add quantity process
     *
     * @param float $qty
     * @return Mage_CatalogInventory_Model_Stock_Item
     */
    public function addQty($qty)
    {
        if (!$this->getManageStock()) {
            return $this;
        }
        $config = Mage::getStoreConfigFlag(self::XML_PATH_CAN_SUBTRACT);
        if (!$config) {
            return $this;
        }

        $this->setQty(parent::getQty() + $qty);
        $this->setSubtractWarehouseQty(floatval($this->getSubtractWarehouseQty()) - $qty);
        return $this;
    }


    // ---------------- reserv \/

    public function XafterCommitCallback()
    {
        parent::afterCommitCallback();

        Mage::getSingleton('index/indexer')->processEntityAction(
            $this, self::ENTITY, Mage_Index_Model_Event::TYPE_SAVE
        );
        return $this;
    }
}
