<?php
class Aitoc_MultiLocationInventory_Block_Adminhtml_Catalog_Product_Renderer extends Mage_Adminhtml_Block_Widget_Grid_Column_Renderer_Abstract
{
	public function render(Varien_Object $row){
		$value='';
		if ($warehouses = Mage::getResourceModel('aitoc_multilocationinventory/warehouse_collection')){
			foreach ($warehouses as $wh){
				$value .= $wh->getName() . ' (' . (int) $row['warehouse_'.$wh->getId()] . ')<br>';
			}
		}
		$value = substr($value, 0, (count($value)-5));
		//$value = $row->getData($this->getColumn()->getIndex());
		return $value;
	}
}