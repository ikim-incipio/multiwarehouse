<?php
class Aitoc_MultiLocationInventory_Block_Adminhtml_Catalog_Product_Edit_Tab_Inventory_Qty extends Mage_Adminhtml_Block_Template
{
    public function getWarehouses() {
        $product = $this->getParentBlock()->getProduct();
        return Mage::getResourceModel('aitoc_multilocationinventory/warehouse_collection')
            ->addStatusFilter()
            ->addStoreFilter($product->getStoreIds())
            ->addProductData($product->getId())
            ->addSortOrder();
    }
}
