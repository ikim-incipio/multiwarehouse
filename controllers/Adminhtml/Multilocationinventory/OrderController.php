<?php

class Aitoc_MultiLocationInventory_Adminhtml_Multilocationinventory_OrderController extends Mage_Adminhtml_Controller_Action
{
    public function saveAction() {
        $response = new Varien_Object();

        $ordeId = (int) $this->getRequest()->getParam('order_id');

        if ($ordeId) {
            $itemWarehouses = $this->getRequest()->getParam('item_warehouse', array());
            $itemWarehouseQtys = $this->getRequest()->getParam('item_warehouse_qty', array());

            $newItemWarehouses = array();
            foreach($itemWarehouses as $orderItemId => $warehouses) {
                foreach($warehouses as $key => $warehouseId) {
                    if (isset($newItemWarehouses[$orderItemId][$warehouseId])) {
                        $newItemWarehouses[$orderItemId][$warehouseId] += intval($itemWarehouseQtys[$orderItemId][$key]);
                    } else {
                        $newItemWarehouses[$orderItemId][$warehouseId] = intval($itemWarehouseQtys[$orderItemId][$key]);
                    }
                }
            }

            $order = Mage::getModel('sales/order')->load($ordeId);
            if (!$order->getId()) {
                $response->setSuccess(false);
                $response->setMessage($this->__('Error load order by ID: %d.', $ordeId));
                return $this->getResponse()->setBody($response->toJson());
            }
            $orderItems = $order->getItemsCollection();

            try {
                foreach($orderItems as $orderItem) {

                    if ($orderItem->getHasChildren()) {
                        continue;
                    }

                    $warehouses = Mage::getResourceModel('aitoc_multilocationinventory/warehouse_collection')
                        ->addProductData($orderItem->getProductId())
                        ->addOrderItemData($orderItem->getId());
                    foreach($warehouses as $warehouse) {
                        $warehouse->setProductId($orderItem->getProductId());

                        if (isset($newItemWarehouses[$orderItem->getId()][$warehouse->getId()])) {
                            // change qty
                            $newQty = $newItemWarehouses[$orderItem->getId()][$warehouse->getId()];
                            if ($warehouse->getOrderedQty() != $newQty) {
                                $warehouse
                                    ->saveUsedInOrderItemQty($orderItem->getId(), ($newQty - $warehouse->getOrderedQty()) * 1)
                                    ->changeQty(($newQty - $warehouse->getOrderedQty()) * -1);
                            }
                        } else {
                            // remove qty
                            $warehouse->saveUsedInOrderItemQty($orderItem->getId(), $warehouse->getOrderedQty() * -1)
                                ->changeQty($warehouse->getOrderedQty() * 1);
                        }
                    }
                }
                $response->setSuccess(true);

            } catch (Mage_Core_Exception $e) {
                $response->setSuccess(false);
                $response->setMessage($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
                $this->_initLayoutMessages('adminhtml/session');
                $response->setSuccess(false);
                $response->setMessage($this->getLayout()->getMessagesBlock()->getGroupedHtml());
            }

        } else {

            $orderItemId = (int) $this->getRequest()->getParam('order_item_id');
            $warehouseId = (int) $this->getRequest()->getParam('warehouse_id');
            $origWarehouseId = (int) $this->getRequest()->getParam('orig_warehouse_id');
            $productId = (int) $this->getRequest()->getParam('product_id');
            $qty = (int) $this->getRequest()->getParam('qty');

            if (!$orderItemId || !$warehouseId || !$origWarehouseId || !$productId || !$qty) {
                $response->setSuccess(false);
                $response->setMessage($this->__('Error in the data sent.'));
                return $this->getResponse()->setBody($response->toJson());
            }

            try {
                Mage::getModel('aitoc_multilocationinventory/warehouse')
                    ->load($warehouseId)
                    ->setProductId($productId)
                    ->saveUsedInOrderItemQty($orderItemId, $qty)
                    ->changeQty($qty * -1);

                Mage::getModel('aitoc_multilocationinventory/warehouse')
                    ->load($origWarehouseId)
                    ->setProductId($productId)
                    ->saveUsedInOrderItemQty($orderItemId, $qty * -1)
                    ->changeQty($qty);

                $response->setSuccess(true);

            } catch (Mage_Core_Exception $e) {
                $response->setSuccess(false);
                $response->setMessage($e->getMessage());
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
                $this->_initLayoutMessages('adminhtml/session');
                $response->setSuccess(false);
                $response->setMessage($this->getLayout()->getMessagesBlock()->getGroupedHtml());
            }

        }

        return $this->getResponse()->setBody($response->toJson());
    }
}
