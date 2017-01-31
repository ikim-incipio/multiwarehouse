<?php

class Aitoc_MultiLocationInventory_Block_Adminhtml_Warehouse_Edit_Tab_Main extends Mage_Adminhtml_Block_Widget_Form implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    public function getTabLabel() {
        return $this->__('General');
    }
   
    public function getTabTitle() {
        return $this->__('General');
    }

    public function canShowTab() {
        return true;
    }

    public function isHidden() {
        return false;
    }

    protected function _prepareForm() {
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('warehouse_');

        $fieldset = $form->addFieldset('base_fieldset', array('legend'=>$this->__('General')));

        $model = Mage::registry('multilocationinventory_warehouse');
        if ($model && $model->getId()) {
            $fieldset->addField('warehouse_id', 'hidden', array('name' => 'warehouse_id'));
        }        

        $fieldset->addField('name', 'text', array(
            'name' => 'name',
            'label' => $this->__('Warehouse'),
            'title' => $this->__('Warehouse'),
            'required' => true,
        ));

        $fieldset->addField('code', 'text', array(
            'name' => 'code',
            'label' => $this->__('Warehouse Code'),
            'title' => $this->__('Warehouse Code'),
            'required' => true,
            'class' => 'validate-code'
        ));

        $fieldset->addField('description', 'textarea', array(
            'name' => 'description',
            'label' => $this->__('Description'),
            'title' => $this->__('Description')
        ));
        
        $fieldset->addField('status', 'select', array(
            'label'     => $this->__('Status'),
            'title'     => $this->__('Status'),
            'name'      => 'status',
            'options'   => Mage::getModel('aitoc_multilocationinventory/warehouse_status')->toArray()
        ));
        
        
        if (!Mage::app()->isSingleStoreMode()) {
            $fieldset->addField('store_id', 'multiselect', array(
                'name'      => 'stores[]',
                'label'     => $this->__('Store View'),
                'title'     => $this->__('Store View'),
                'required'  => true,
                'values'    => Mage::getSingleton('adminhtml/system_store')->getStoreValuesForForm(false, true)
            ));
        } else {
            $fieldset->addField('store_id', 'hidden', array(
                'name'      => 'stores[]',
                'value'     => Mage::app()->getStore(true)->getId()
            ));
            $model->setStoreId(Mage::app()->getStore(true)->getId());
        }
        
        $customerGroups = Mage::getResourceModel('customer/group_collection')->load()->toOptionArray();
        $found = false;
        foreach ($customerGroups as $group) {
            if ($group['value']==0) {
                $found = true;
            }
        }
        if (!$found) array_unshift($customerGroups, array('value'=>0, 'label'=>Mage::helper('salesrule')->__('NOT LOGGED IN')));

        $fieldset->addField('customer_group_ids', 'multiselect', array(
            'name'      => 'customer_group_ids[]',
            'label'     => $this->__('Customer Groups'),
            'title'     => $this->__('Customer Groups'),
            'values'    => $customerGroups
        ));

        $fieldset->addField('priority', 'text', array(
            'name' => 'priority',
            'label' => $this->__('Priority'),
            'class' => 'validate-greater-than-zero'
        ));

        $fieldset->addField('sort_order', 'text', array(
            'name' => 'sort_order',
            'label' => $this->__('Sort Order'),
            'class' => 'validate-zero-or-greater'
        ));

        $form->setValues($model->getData());

        $this->setForm($form);

        // field dependencies
//        $this->setChild('form_after', $this->getLayout()->createBlock('adminhtml/widget_form_element_dependence')
//            ->addFieldMap($couponTypeFiled->getHtmlId(), $couponTypeFiled->getName())
//            ->addFieldMap($couponCodeFiled->getHtmlId(), $couponCodeFiled->getName())
//            ->addFieldMap($usesPerCouponFiled->getHtmlId(), $usesPerCouponFiled->getName())
//            ->addFieldDependence(
//                $couponCodeFiled->getName(),
//                $couponTypeFiled->getName(),
//                Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC)
//            ->addFieldDependence(
//                $usesPerCouponFiled->getName(),
//                $couponTypeFiled->getName(),
//                Mage_SalesRule_Model_Rule::COUPON_TYPE_SPECIFIC)
//        );

        return parent::_prepareForm();
    }
}
