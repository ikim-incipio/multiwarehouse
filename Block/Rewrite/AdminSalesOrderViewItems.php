<?php
class Aitoc_MultiLocationInventory_Block_Rewrite_AdminSalesOrderViewItems extends Mage_Adminhtml_Block_Sales_Order_View_Items
{
    protected function _afterToHtml($html)
    {
        if (Mage::helper('aitoc_multilocationinventory')->getShowWarehouseInOrder() ==
             Aitoc_MultiLocationInventory_Model_System_Config_Source_Order_Show::ON_ORDER_ITEM
        ) {
            // add warehouse th
            $pos = strpos($html, '<th class="a-center">');
            if ($pos === false) {
                return $html;
            }

            $nextPos = strpos($html, '<th>', $pos + 21);
            if ($nextPos) {
                $pos = $nextPos;
            }

            $warehouseThHtml = '<th>' . $this->__('Warehouse') . '</th>';

            $html = substr_replace(
                $html,
                $warehouseThHtml,
                $pos,
                0
            );

            // add new '<col width="1" />'
            $pos = strpos($html, '<col width="1" />');
            if ($pos === false) {
                return $html;
            }
            $html = substr_replace(
                $html,
                '<col width="1" />',
                $pos,
                0
            );

            // add js functions
            $html .= '<script type="text/javascript">
                    function changeWarehouse(el) {
                        if (el.value != el.readAttribute("orig_warehouse_id")) {
                            $("save_" + el.id).up("div").show();
                        } else {
                            $("save_" + el.id).up("div").hide();
                        }
                    }
                    function saveChangedWarehouse(itemId, productId, qty) {
                        var selectEl = $("item_warehouse_" + itemId)
                        var warehouseId = selectEl.value;
                        var origWarehouseId = selectEl.readAttribute("orig_warehouse_id");
                        new Ajax.Request(
                            "'. Mage::helper('adminhtml')->getUrl('adminhtml/multilocationinventory_order/save') .'",
                            {
                                parameters: {
                                    "order_item_id": itemId,
                                    "orig_warehouse_id": origWarehouseId,
                                    "warehouse_id": warehouseId,
                                    "product_id": productId,
                                    "qty": qty
                                },
                                method: "post",
                                onSuccess:function(transport) {
                                    if (transport.responseText.isJSON()) {
                                        var response = transport.responseText.evalJSON();
                                        if (response.success) {
                                            selectEl.writeAttribute("orig_warehouse_id", selectEl.value);
                                            changeWarehouse(selectEl);
                                        } else {
                                            alert(response.message);
                                        }
                                    } else {
                                        alert(transport.responseText);
                                    }
                                }
                            }
                        );
                    }
                </script>';
        }

        return $html;
    }

    /**
     * Retrieve rendered item html content
     *
     * @param Varien_Object $item
     * @return string
     */
    public function getItemHtml(Varien_Object $item)
    {
        $html = parent::getItemHtml($item);

        if (Mage::helper('aitoc_multilocationinventory')->getShowWarehouseInOrder() ==
            Aitoc_MultiLocationInventory_Model_System_Config_Source_Order_Show::ON_ORDER_ITEM
        ) {
            // add td warehouse
            $pos = strpos($html, 'class="qty-table"');
            if ($pos === false) {
                return $html;
            }

            $pos = strpos($html, '</table>', $pos + 17);
            if ($pos === false) {
                return $html;
            }

            $pos = strpos($html, '<td', $pos + 8);
            if ($pos === false) {
                return $html;
            }

            $html = substr_replace(
                $html,
                $this->_getWarehouseTdHtml($item),
                $pos,
                0
            );
        }

        return $html;
    }

    protected function _getWarehouseTdHtml($item)
    {

        if ($item->getHasChildren()) {
            $items = $item->getChildrenItems();
        } else {
            $items = array($item);
        }

        $html = array();
        foreach ($items as $item) {
            $itemWarehouses = Mage::getResourceModel('aitoc_multilocationinventory/warehouse_collection')
                ->addOrderItemFilter($item->getId());

            if (count($itemWarehouses) == 0) {
                continue;
            }

            foreach ($itemWarehouses as $itemWarehouse) {
                if (Mage::helper('aitoc_multilocationinventory')->canEditWarehouseInOrder()) {
                    $warehouses = Mage::getResourceModel('aitoc_multilocationinventory/warehouse_collection')
                        ->addStatusFilter()
                        ->addProductData($item->getProductId())
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
                            'id' => 'item_warehouse_' . $item->getId(),
                            'class' => 'select',
                            'extra_params' => 'onchange="changeWarehouse(this)" orig_warehouse_id='. $itemWarehouse->getId()
                        ))
                        ->setName('item_warehouse['. $item->getId() .']')
                        ->setValue($itemWarehouse->getId())
                        ->setOptions($options);

                    $button = $this->getLayout()->createBlock('adminhtml/widget_button')
                        ->setType('button')
                        ->setId('save_item_warehouse_' . $item->getId())
                        ->setLabel($this->__('Save'))
                        ->setOnClick(
                            'saveChangedWarehouse(' . $item->getId() . ', ' .
                            $item->getProductId() . ', ' .
                            intval($itemWarehouse->getOrderedQty()) . ')'
                        );

                    $html[] = $select->getHtml() .
                        '<div align="right" style="margin:4px; display:none;">' . $button->toHtml() . '</div>';

                } else {
                    $html[] = $itemWarehouse->getName() . ' ('. intval($itemWarehouse->getOrderedQty()) .')';
                }
            }
        }

        $html = '<td class="nobr">' . implode('<br>', $html) . '</td>';
        return $html;
    }
}
