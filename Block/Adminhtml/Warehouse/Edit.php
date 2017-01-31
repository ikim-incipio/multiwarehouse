<?php

class Aitoc_MultiLocationInventory_Block_Adminhtml_Warehouse_Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct() {
        $this->_objectId = 'warehouse_id';
        $this->_blockGroup = 'aitoc_multilocationinventory';
        $this->_controller = 'adminhtml_warehouse';
        parent::__construct();        
        $this->removeButton('reset');
        
        $this->_updateButton('delete', 'label', $this->__('Delete Warehouse'));
        $this->_updateButton('save', 'label', $this->__('Save Warehouse'));
        
        $this->_addButton('save_and_continue', array(
            'label'     => Mage::helper('salesrule')->__('Save and Continue Edit'),
            'onclick'   => 'saveAndContinueEdit()',
            'class' => 'save'
        ), 10);
        
        
        // set last tab
        $tab = Mage::app()->getRequest()->getParam('tab');
        // $('applied_totals').size = 3;
        $this->_formScripts[] = "function saveAndContinueEdit(){
                editForm.submit($('edit_form').action + 'back/edit/tab/'+warehouse_edit_tabsJsTabs.activeTab.id+'/')
            }".
            ($tab ?
                "warehouse_edit_tabsJsTabs.setSkipDisplayFirstTab();
                warehouse_edit_tabsJsTabs.showTabContent($('" . $tab . "'));"
                : ""
            );
    }

    public function getHeaderText() {
        $model = Mage::registry('multilocationinventory_warehouse');
        if ($model && $model->getId()) {            
            return $this->__("Edit Warehouse '%s'", $this->htmlEscape($model->getName()));
        } else {
            return $this->__('New Warehouse');
        }        
    }

    public function getFormActionUrl()
    {
        return $this->getUrl('*/*/save');
    }
}
