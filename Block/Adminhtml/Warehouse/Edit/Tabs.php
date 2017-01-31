<?php

class Aitoc_MultiLocationInventory_Block_Adminhtml_Warehouse_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct() {
        parent::__construct();
        $this->setId('warehouse_edit_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle($this->__('Manage Warehouse'));
    }
}
