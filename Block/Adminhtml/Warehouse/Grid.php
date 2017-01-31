<?php

class Aitoc_MultiLocationInventory_Block_Adminhtml_Warehouse_Grid extends Mage_Adminhtml_Block_Widget_Grid {

    public function __construct() {
        parent::__construct();
        $this->setId('warehouse_id');
        $this->setDefaultSort('sort_order');
        $this->setDefaultDir(Varien_Data_Collection::SORT_ORDER_ASC);
	    $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection() {
        $collection = Mage::getResourceModel('aitoc_multilocationinventory/warehouse_collection');
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns() {
        $helper = Mage::helper('aitoc_multilocationinventory');
        
        $this->addColumn('id', array(
            'header' => $helper->__('ID'),
            'type' => 'number',
            'width' => '80px',
            'index' => 'warehouse_id',
        ));
        
        $this->addColumn('name', array(
            'header' => $helper->__('Warehouse'),
            'index' => 'name'
        ));

        $this->addColumn('code', array(
            'header' => $helper->__('Code'),
            'index' => 'code',
            'width' => '180px'
        ));

        if (!Mage::app()->isSingleStoreMode()) {
            $this->addColumn('store_id', array(
                'header' => $helper->__('Store View'),
                'width' => '200px',
                'index' => 'store_id',
                'type' => 'store',
                'store_all' => true,
                'store_view' => true,
                'sortable' => false,
                'filter_condition_callback'
                => array($this, '_filterStoreCondition'),
                'align' => 'center'
            ));
        }

        $this->addColumn('priority', array(
            'header' => $helper->__('Priority'),
            'type' => 'number',
            'width' => '80px',
            'index' => 'priority',
        ));

        $this->addColumn('sort_order', array(
            'header' => $helper->__('Sort Order'),
            'type' => 'number',
            'width' => '80px',
            'index' => 'sort_order',
        ));

        $this->addColumn('status', array(
            'header'    => $helper->__('Status'),
            'align'     => 'left',
            'width'     => '80px',
            'index'     => 'status',
            'type'      => 'options',
            'options'   => Mage::getModel('aitoc_multilocationinventory/warehouse_status')->toArray()
        ));

        $this->addColumn('action', 
            array(
                'header'    => $helper->__('Action'),
                'width'     => '50px',
                'type'      => 'action',
                'getter'     => 'getId',
                'actions'   => array(
                    array(
                        'caption' => $helper->__('Edit'),
                        'url'     => array('base'=>'*/*/edit'),
                        'field'   => 'id'
                    )
                ),
                'filter'    => false,
                'sortable'  => false,
                'index'     => 'stores',
                'is_system' => true,
                'align' => 'center'            
        ));

        return parent::_prepareColumns();
    }
    
    public function getRowUrl($row) {
        return $this->getUrl('*/*/edit', array('id' => $row->getId()));
    }

    
    protected function _filterStoreCondition($collection, $column) {
        if (!$value = $column->getFilter()->getValue()) return;        
        $this->getCollection()->addStoreFilter($value);
    }    

    protected function _prepareMassaction() {
        $helper = Mage::helper('aitoc_multilocationinventory');
        
        $this->setMassactionIdField('warehouse_id');
        $this->getMassactionBlock()->setFormFieldName('warehouse');

        $this->getMassactionBlock()->addItem('delete', array(
            'label' => $helper->__('Delete'),
            'url' => $this->getUrl('*/*/massDelete'),
            'confirm' => $helper->__('Are you sure you want to do this?')
        ));
                
        $statuses = array();
        $statuses[''] = '';
        $array = Mage::getModel('aitoc_multilocationinventory/warehouse_status')->toArray();
        foreach($array as $key=>$value) {
             $statuses[$key] = $value;
        }

        $this->getMassactionBlock()->addItem('status', array(
            'label' => $helper->__('Change status'),
            'url' => $this->getUrl('*/*/massStatus', array('_current' => true)),
            'additional' => array(
                'visibility' => array(
                    'name' => 'status',
                    'type' => 'select',
                    'class' => 'required-entry',
                    'label' => $helper->__('Status'),
                    'values' => $statuses
                )
            )
        ));

        return $this;
    }
}
