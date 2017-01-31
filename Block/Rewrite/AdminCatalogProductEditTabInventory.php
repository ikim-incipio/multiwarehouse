<?php
if (Mage::getConfig()->getModuleConfig('Aitoc_Aitpermissions')->is('active', 'true')){
    class Aitoc_MultiLocationInventory_Block_Rewrite_AdminCatalogProductEditTabInventory_Aittmp extends Aitoc_Aitpermissions_Block_Rewrite_AdminhtmlCatalogProductEditTabInventory {}
 } else {
    /* default extends start */
    class Aitoc_MultiLocationInventory_Block_Rewrite_AdminCatalogProductEditTabInventory_Aittmp extends Mage_Adminhtml_Block_Catalog_Product_Edit_Tab_Inventory {}
    /* default extends end */
}

class Aitoc_MultiLocationInventory_Block_Rewrite_AdminCatalogProductEditTabInventory extends Aitoc_MultiLocationInventory_Block_Rewrite_AdminCatalogProductEditTabInventory_Aittmp
{
    protected function _prepareLayout()
    {
        $this->setChild('multilocationinventory_warehouse_qty',
            $this->getLayout()->createBlock(
                'aitoc_multilocationinventory/adminhtml_catalog_product_edit_tab_inventory_qty'
            )->setTemplate('aitoc_multilocationinventory/catalog/product/edit/inventory/qty.phtml')
        );

        return parent::_prepareLayout();
    }

    protected function _afterToHtml($html)
    {
        $pos = strpos($html, '"inventory_qty"');

        if ($pos===false) {
            return $html;
        }

        $startPos  = strrpos($html, '<tr>', (strlen($html) - $pos) * -1);
        $endPos  = strpos($html, '</tr>', $pos);

        if ($startPos===false || $endPos===false) {
            return $html;
        }

        $warehouseQtyHtml = $this->getChildHtml('multilocationinventory_warehouse_qty');
        if (!$warehouseQtyHtml) {
            return $html;
        }

        $html = substr_replace(
            $html,
            $warehouseQtyHtml,
            $startPos,
            $endPos - $startPos
        );

        return $html;
    }
}
