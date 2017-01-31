<?php
class Aitoc_MultiLocationInventory_Block_Rewrite_AdminSalesOrderViewTabInfo extends Mage_Adminhtml_Block_Sales_Order_View_Tab_Info
{
    protected function _prepareLayout()
    {
        $this->setChild('multilocationinventory_order_view_warehouse_info',
            $this->getLayout()->createBlock(
                'aitoc_multilocationinventory/adminhtml_sales_order_view_tab_info'
            )->setTemplate('aitoc_multilocationinventory/sales/order/view/tab/info.phtml')
        );
        return parent::_prepareLayout();
    }

    protected function _afterToHtml($html)
    {
        if (Mage::helper('aitoc_multilocationinventory')->getShowWarehouseInOrder() ==
             Aitoc_MultiLocationInventory_Model_System_Config_Source_Order_Show::ON_PAGE
        ) {
            $pos = strpos($html, '<div class="clear"></div>');

            if ($pos === false) {
                return $html;
            }

            $nextPos = strpos($html, '<div class="clear"></div>', $pos + 25);
            if ($nextPos) {
                $pos = $nextPos;
                $nextPos = strpos($html, '<div class="clear"></div>', $pos + 25);
                if ($nextPos) {
                    $pos = $nextPos;
                }
            }

            $warehouseInfoHtml = $this->getChildHtml('multilocationinventory_order_view_warehouse_info');
            if (!$warehouseInfoHtml) {
                return $html;
            }

            $html = substr_replace(
                $html,
                $warehouseInfoHtml,
                $pos + 26,  // changed by Isaac on 1-19-2017 to inject warehouse html after <div class="clear"></div> (mainly for Braven site)
                0
            );
        }

        return $html;
    }
}
