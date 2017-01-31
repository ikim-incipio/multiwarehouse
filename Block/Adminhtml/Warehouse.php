<?php

class Aitoc_MultiLocationInventory_Block_Adminhtml_Warehouse extends Mage_Adminhtml_Block_Template
{
    protected function _prepareLayout() {
        $helper = Mage::helper('aitoc_multilocationinventory');
        $this->setChild('add_new_button', $this->getLayout()->createBlock('adminhtml/widget_button')
                ->setData(array('label' => $helper->__('Add Warehouse'),
                    'onclick' => "setLocation('" . $this->getUrl('*/*/new') . "')",
                    'class' => 'add')
                )
        );
        $this->setChild('grid', $this->getLayout()->createBlock('aitoc_multilocationinventory/adminhtml_warehouse_grid', 'warehouse.grid'));
        return parent::_prepareLayout();
    }

    public function getAddNewButtonHtml() {
        return $this->getChildHtml('add_new_button');
    }
    
    public function getGridHtml() {
        return $this->getChildHtml('grid');
    }
}
