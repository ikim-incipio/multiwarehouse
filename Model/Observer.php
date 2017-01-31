<?php
class Aitoc_MultiLocationInventory_Model_Observer extends Mage_CatalogInventory_Model_Observer
{
    public function quoteLoadAfter($observer)
    {
        $quote = $observer->getEvent()->getQuote();
        if ($quote->getItemsCount() > 0 && Mage::app()->getRequest()->getActionName() == 'add') {
            Mage::getSingleton('checkout/session')->replaceQuote($quote);
            $quote->getItemsCollection()->load();
        }
    }

    public function quoteItemCollectionLoadAfter($observer)
    {
        // after load quoteItemCollection - check again warehouses
        if (Mage::app()->getStore()->isAdmin()) {
            $session = Mage::getSingleton('adminhtml/session_quote');
        } else {
            $session = Mage::getSingleton('checkout/session');
        }

        $quote = $session->getQuote();
        $helper = Mage::helper('aitoc_multilocationinventory');

        if (!$helper->canUseDifferentWarehouses() && $quote->getItemsCount() > 0) {
            $items = $quote->getItemsCollection();

            // collect qty for each product in quote
            $productsQtyInQuote = array();
            foreach($items as $item) {
                if ($item->getHasChildren()) {
                    continue;
                }
                if (isset($productsQtyInQuote[$item->getProductId()])) {
                    if ($item->getParentItem()) {
                        $productsQtyInQuote[$item->getProductId()] += $item->getParentItem()->getQty();
                    } else {
                        $productsQtyInQuote[$item->getProductId()] += $item->getQty();
                    }
                } else {
                    if ($item->getParentItem()) {
                        $productsQtyInQuote[$item->getProductId()] = $item->getParentItem()->getQty();
                    } else {
                        $productsQtyInQuote[$item->getProductId()] = $item->getQty();
                    }
                }
            }

            // select optimal warehouse for quote
            $warehouseIds = array();
            foreach($items as $item) {
                if ($item->getHasChildren()) {
                    continue;
                }
                $warehouses = $item->getProduct()->getStockItem()->getWarehouses();
                if (!$warehouses) {
                    continue;
                }
                foreach($warehouses as $warehouse) {
                    if (!isset($warehouseIds[$warehouse->getId()])) {
                        $warehouseIds[$warehouse->getId()] = array(
                            'out_of_stock' => 0,
                            'in_alternative_stock' => 0,
                            'in_right_stock' => 0,
                            'all_in_stock' => 0,
                            'warehouse_id' => $warehouse->getId(),
                        );
                    }

                    if (!isset($productsQtyInQuote[$item->getProductId()])
                        || $warehouse->getQty() < $productsQtyInQuote[$item->getProductId()]) {
                        $warehouseIds[$warehouse->getId()]['out_of_stock'] += 1;
                    } elseif ($warehouse->getIsAlternative()) {
                        $warehouseIds[$warehouse->getId()]['in_alternative_stock'] += 1;
                        $warehouseIds[$warehouse->getId()]['all_in_stock'] += 1;
                    } else {
                        $warehouseIds[$warehouse->getId()]['in_right_stock'] += 1;
                        $warehouseIds[$warehouse->getId()]['all_in_stock'] += 1;
                    }
                }
            }

            usort($warehouseIds, array($this, '_sortWarehouses'));

            $warehouse = array_shift($warehouseIds);
            $quote->setWarehouseId($warehouse['warehouse_id']);

            $productCollection = $observer->getEvent()->getProductCollection();
            foreach ($productCollection as $product) {
                $product->getStockItem()->assignProduct($product);
            }

            //var_dump($quote->getWarehouseId()); exit;
        }
    }

    protected function _sortWarehouses($a, $b)
    {
        if ($a['all_in_stock'] == $b['all_in_stock']) {
            if ($a['in_right_stock'] == $b['in_right_stock']) {
                if ($a['in_alternative_stock'] == $b['in_alternative_stock']) {
                    if ($a['out_of_stock'] == $b['out_of_stock']) {
                        return 0;
                    }
                    return ($a['out_of_stock'] > $b['out_of_stock']) ? -1 : 1;
                }
                return ($a['in_alternative_stock'] > $b['in_alternative_stock']) ? -1 : 1;
            }
            return ($a['in_right_stock'] > $b['in_right_stock']) ? -1 : 1;
        }
        return ($a['all_in_stock'] > $b['all_in_stock']) ? -1 : 1;
    }


    public function convertQuoteItemToOrderItem($observer)
    {
        $orderItem = $observer->getEvent()->getOrderItem();
        $quoteItem = $observer->getEvent()->getItem();
        $orderItem->setQuoteItemId($quoteItem->getId());
    }

    public function checkoutSubmitAllAfter($observer)
    {
        $orders = $observer->getEvent()->getOrder();
        $quote = $observer->getEvent()->getQuote();
        if (!is_array($orders)) {
            $orders = array($orders);
        }

        foreach($orders as $order) {
            $orderItems = $order->getItemsCollection();

            foreach ($orderItems as $orderItem) {
                if ($orderItem->getQuoteItemId()) {
                    $quoteItem = $quote->getItemById($orderItem->getQuoteItemId());

                    if ($quoteItem) {
                        // fix for same products in quote - clone objects
                        if ($quoteItem->getProduct()->getStockItem()->getOrderItemId()) {
                            $quoteItem->setProduct(clone $quoteItem->getProduct());
                            $quoteItem->getProduct()->setStockItem(clone $quoteItem->getProduct()->getStockItem());
                        }

                        $quoteItem->getProduct()->getStockItem()->setOrderItemId($orderItem->getId());
                    }
                }
            }
        }

        if (!$quote->getInventoryProcessed()) {
            $this->subtractQuoteInventory($observer);
            $this->reindexQuoteInventory($observer);
        }
    }

    public function subtractQuoteInventory(Varien_Event_Observer $observer)
    {
        $quote = $observer->getEvent()->getQuote();

        // Maybe we've already processed this quote in some event during order placement
        // e.g. call in event 'sales_model_service_quote_submit_before' and later in 'checkout_submit_all_after'
        if ($quote->getInventoryProcessed()) {
            return;
        }
        $items = $this->_getProductsQty($quote->getAllItems());

        /**
         * Remember items
         */
        $this->_itemsForReindex = Mage::getSingleton('cataloginventory/stock')->registerProductsSale($items);

        $quote->setInventoryProcessed(true);
        return $this;
    }

    protected function _addItemToQtyArray($quoteItem, &$items)
    {
        $productId = $quoteItem->getProductId();
        if (!$productId) return;

        $stockItem = null;
        if ($quoteItem->getProduct()) {
            $stockItem = $quoteItem->getProduct()->getStockItem();
        }
        $items[] = array(
            'item' => $stockItem,
            'qty'  => $quoteItem->getTotalQty()
        );
    }

    public function cancelOrderItem($observer)
    {
        $item = $observer->getEvent()->getItem();
        $warehouses = Mage::getResourceModel('aitoc_multilocationinventory/warehouse_collection')
            ->addOrderItemFilter($item->getId());
        foreach($warehouses as $warehouse) {
            $warehouseIds[$warehouse->getId()] = $warehouse->getOrderedQty() * -1;
        }

        $children = $item->getChildrenItems();
        $qty = $item->getQtyOrdered() - max($item->getQtyShipped(), $item->getQtyInvoiced()) - $item->getQtyCanceled();

        $productId = $item->getProductId();
        if ($item->getId() && $productId && empty($children) && $qty) {
            Mage::getSingleton('cataloginventory/stock')->backItemQty($productId, $qty, $warehouseIds, $item->getId());
        }
        return $this;
    }

    public function refundOrderInventory($observer)
    {
        /* @var $creditmemo Mage_Sales_Model_Order_Creditmemo */
        $creditmemo = $observer->getEvent()->getCreditmemo();
        $items = array();
        foreach ($creditmemo->getAllItems() as $item) {
            /* @var $item Mage_Sales_Model_Order_Creditmemo_Item */
            $return = false;
            if ($item->hasBackToStock()) {
                if ($item->getBackToStock() && $item->getQty()) {
                    $return = true;
                }
            } elseif (Mage::helper('cataloginventory')->isAutoReturnEnabled()) {
                $return = true;
            }
            if ($return) {
                $parentOrderId = $item->getOrderItem()->getParentItemId();
                /* @var $parentItem Mage_Sales_Model_Order_Creditmemo_Item */
                $parentItem = $parentOrderId ? $creditmemo->getItemByOrderId($parentOrderId) : false;
                $qty = $parentItem ? ($parentItem->getQty() * $item->getQty()) : $item->getQty();

                // aitoc fix
                $warehouses = Mage::getResourceModel('aitoc_multilocationinventory/warehouse_collection')
                    ->addOrderItemFilter($item->getOrderItemId());
                foreach($warehouses as $warehouse) {
                    $warehouseIds[$warehouse->getId()] = $warehouse->getOrderedQty() * -1;
                }

                Mage::getSingleton('cataloginventory/stock')
                    ->backItemQty($item->getProductId(), $qty, $warehouseIds, $item->getOrderItemId());
            }
        }
    }

    // add warehouse column to order grid start
    protected $_activeWarehousesArray = null;

    public function adminBlockPrepareLayoutAfter($observer)
    {
        $block = $observer->getEvent()->getBlock();

        // add warehouse column to order grid
        if ($block instanceof Mage_Adminhtml_Block_Sales_Order_Grid &&
            Mage::helper('aitoc_multilocationinventory')->getShowWarehouseInOrderGrid()
        ) {
            $block->addColumnAfter('warehouse',
                array(
                    'header' => Mage::helper('aitoc_multilocationinventory')->__('Warehouse'),
                    'type'  => 'options',
                    'index' => 'warehouse_ids',
                    'options' => $this->getActiveWarehousesArray(),
                    'frame_callback' => array($this, 'showWarehouse'),
                    'filter_condition_callback' => array($this, 'filterWarehouseCondition')
                ),
                'shipping_name'
            );
        }
    }

    public function getActiveWarehousesArray()
    {
        if (is_null($this->_activeWarehousesArray)) {
            $warehouses = Mage::getResourceModel('aitoc_multilocationinventory/warehouse_collection')
                ->addStatusFilter();
            $values = '';
            foreach($warehouses as $warehouse) {
                $values[$warehouse->getId()] = $warehouse->getName();
            }

            $this->_activeWarehousesArray = $values;
        }
        return $this->_activeWarehousesArray;
    }

    public function showWarehouse($value, $row, $column, $isExport)
    {
        if ($row->getWarehouseIds()) {
            $warehousesArray = $this->getActiveWarehousesArray();

            $values = explode("\n", $row->getWarehouseIds());
            $qtys = explode("\n", $row->getWarehouseQtys());

            $warehouses = array();
            foreach($values as  $key => $warehouseId) {
                if (isset($warehouses[$warehouseId])) {
                    $warehouses[$warehouseId] += intval($qtys[$key]);
                } else {
                    $warehouses[$warehouseId] = intval($qtys[$key]);
                }
            }

            $cell = array();
            foreach($warehouses as $warehouseId => $qty) {
                if (isset($warehousesArray[$warehouseId])) {
                    $cell[$warehouseId] = $warehousesArray[$warehouseId] . ' (' . $qty . ')';
                }
            }

            if ($isExport) {
                $cell = implode(':', $cell);
            } else {
                $cell = implode('<br>', $cell);
            }
        } else {
            $cell = $value;
        }
        return $cell;
    }

    public function filterWarehouseCondition($collection, $column)
    {
        if (!$value = $column->getFilter()->getValue()) {
            return;
        }
        $collection->addFilter('warehouse_order_item_tbl.warehouse_id', array('in' => $value));
    }

    public function orderGridCollectionLoadBefore($observer)
    {
        if (Mage::helper('aitoc_multilocationinventory')->getShowWarehouseInOrderGrid()) {
            $collection = $observer->getEvent()->getOrderGridCollection();
            $collection->getSelect()
                ->joinLeft(
                    array('order_item_tbl' => $collection->getTable('sales/order_item')),
                    'order_item_tbl.order_id = main_table.entity_id',
                    array()
                )
                ->joinLeft(array('warehouse_order_item_tbl' => $collection->getTable('aitoc_multilocationinventory/warehouse_order_item')),
                    "warehouse_order_item_tbl.order_item_id = order_item_tbl.item_id",
                    array (
                        'warehouse_ids' => new Zend_Db_Expr('GROUP_CONCAT(warehouse_order_item_tbl.`warehouse_id` SEPARATOR \'\n\')'),
                        'warehouse_qtys' => new Zend_Db_Expr('GROUP_CONCAT(warehouse_order_item_tbl.`qty` SEPARATOR \'\n\')')
                    )
                )
                ->group('main_table.entity_id');
        }
    }
    // add warehouse column to order grid end

    // ------------------ old \/

    /*
    * used to prevent database locks conflicts during repeated saving stock items from some of the modules classes
    * due to this second saving process takes place only when first saving was committed
    */
    public function onCataloginventoryStockItemSaveCommitAfter($observer)
    {
        $stockItem = $observer->getItem();
        $callingClass = $stockItem->getCallingClass();

        if($callingClass == 'Aitoc_Aitquantitymanager_Model_Rewrite_FrontCatalogInventoryStock')
            $this->_onCataloginventoryStockItemSaveCommitAfter1($stockItem);
        elseif($callingClass == 'Aitoc_Aitquantitymanager_Model_Rewrite_FrontCatalogInventoryObserver' ||
               $callingClass == 'Aitoc_Aitquantitymanager_AttributeController')
            $this->_onCataloginventoryStockItemSaveCommitAfter2($stockItem);

        $stockItem->setCallingClass('');
        return;
    }

    public function onRssCatalogNotifyStockCollectionSelect($observer)
    {
        $collection = $observer->getCollection();
        //$stockItemTable = $collection->getTable('cataloginventory/stock_item');
        $collection->getSelect()->columns(array('website_id' => 'invtr.website_id'));
    }
}
