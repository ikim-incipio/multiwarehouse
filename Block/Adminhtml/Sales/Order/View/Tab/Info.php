<?php
class Aitoc_MultiLocationInventory_Block_Adminhtml_Sales_Order_View_Tab_Info extends Mage_Adminhtml_Block_Template
{
    protected $_warehouses = null;

    public function getOrder() {
        return $this->getParentBlock()->getOrder();
    }

    public function getWarehouses() {
        if (is_null($this->_warehouses)) {
            $order = $this->getOrder();
            if (!$order) {
                return false;
            }
            $items = $order->getItemsCollection();
            $itemsIds = array();
            foreach($items as $item) {
                $itemsIds[] = $item->getId();
            }

            $this->_warehouses = Mage::getResourceModel('aitoc_multilocationinventory/warehouse_collection')
                ->addOrderItemFilter($itemsIds);
        }
        return $this->_warehouses;
    }

    public function getWarehousesHtml() {
        $helper = Mage::helper('aitoc_multilocationinventory');
        $order = $this->getOrder();
        if (!$order) {
            return false;
        }
        $orderItems = $order->getItemsCollection();
        if (count($orderItems) == 0) {
            return '';
        }

        $html = '<table class="">';
        $html .= '<tr class="headings">';
        $html .= '<th>'. $this->__('Product') .'</th>';
        $html .= '<th>'. $this->__('SKU') .'</th>';
        $html .= '<th>'. $this->__('Warehouse') .'</th>';
        $html .= '<th>'. $this->__('Qty') .'</th>';
        $html .= '</tr>';

        foreach($orderItems as $orderItem) {

            if ($orderItem->getHasChildren()) {
                continue;
            }

            $itemWarehouses = Mage::getResourceModel('aitoc_multilocationinventory/warehouse_collection')
                ->addOrderItemFilter($orderItem->getId());
            if (count($itemWarehouses) == 0) {
                continue;
            }

            foreach ($itemWarehouses as $itemWarehouse) {

                if ($helper->canEditWarehouseInOrder()) {
                    $warehouses = Mage::getResourceModel('aitoc_multilocationinventory/warehouse_collection')
                        ->addStatusFilter()
                        ->addProductData($orderItem->getProductId())
                        ->addSortOrder();

                    $options = array();
                    foreach($warehouses as $warehouse) {
                        $option = array(
                            'value' => $warehouse->getId(),
                            'label' => $warehouse->getName() . ' (' . intval($warehouse->getQty()) . ')',
                        );

                        if (($warehouse->getQty() - $itemWarehouse->getOrderedQty()) < 0
                            && $itemWarehouse->getId() != $warehouse->getId()
                        ) {
                            $option['params'] = array('disabled' => 'disabled');
                        }

                        $options[] = $option;
                    }

                    $select = $this->getLayout()->createBlock('adminhtml/html_select')
                        ->setData(array(
                            'class' => 'select',
                            'extra_params' => 'onchange="changeWarehouse()"'
                        ))
                        ->setName('item_warehouse['. $orderItem->getId() .'][]')
                        ->setValue($itemWarehouse->getId())
                        ->setOptions($options);
                }


                if ($orderItem->getParentItem()) {
                    $parentOrderItem = $orderItem->getParentItem();
                } else {
                    $parentOrderItem = $orderItem;
                }

                $html .= '<tr>';
                $html .= '<td>' . $parentOrderItem->getName() . '</td>';
                $html .= '<td>' . $parentOrderItem->getSku() . '</td>';

                if ($helper->canEditWarehouseInOrder()) {
                    $html .= '<td>' . $select->getHtml() . '</td>';

                    $html .= '<td>';
                    $html .= '<input class="required-entry validate-zero-or-greater validate-warehouse-qty qty "' .
                        ' name="item_warehouse_qty[' . $orderItem->getId() . '][]"' .
                        ' onchange="changeWarehouse()" value="' . intval($itemWarehouse->getOrderedQty()) . '">';
                    $html .= '</td>';
                } else {
                    $html .= '<td>' . $itemWarehouse->getName() . '</td>';
                    $html .= '<td>' . intval($itemWarehouse->getOrderedQty()) . '</td>';
                }

                $html .= '</tr>';
            }
        }

        if ($helper->canEditWarehouseInOrder()) {
            $button = $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setType('button')
                ->setId('save_item_warehouse')
                ->setLabel($this->__('Save'))
                ->setOnClick('saveChangedWarehouse()');

            $html .= '<tr style="display: none;"><td colspan="4" align="right">' .  $button->toHtml() . '</tr></td>';
        }

        $html .= '</table>';
        return $html;
    }

    public function getTotalOrderedQty() {
        $totalQty = 0;
        $warehouses = $this->getWarehouses();
        foreach($warehouses as $warehouse) {
            $totalQty += $warehouse->getOrderedQty();
        }
        return $totalQty;
    }
}
