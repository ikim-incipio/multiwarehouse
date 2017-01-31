<?php
class Aitoc_MultiLocationInventory_Block_Adminhtml_Catalog_Product_Edit_Action_Attribute_Tab_Inventory_Qty extends Mage_Adminhtml_Block_Template
{
    public function getWarehouses() {
    	$storeId = $this->getParentBlock()->getStoreId();
    	if ($storeId){
	        return Mage::getResourceModel('aitoc_multilocationinventory/warehouse_collection')
	            ->addStatusFilter()
	            ->addStoreFilter($storeId)
	            ->addSortOrder();
        } else {
        	return Mage::getResourceModel('aitoc_multilocationinventory/warehouse_collection')
	            ->addStatusFilter()
	            ->addSortOrder();
        }
    }
}
