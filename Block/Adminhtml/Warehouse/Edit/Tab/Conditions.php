<?php

class Aitoc_MultiLocationInventory_Block_Adminhtml_Warehouse_Edit_Tab_Conditions extends Mage_Adminhtml_Block_Widget_Form implements Mage_Adminhtml_Block_Widget_Tab_Interface
{

    public function getTabLabel() {
        return $this->__('Conditions');
    }
   
    public function getTabTitle() {
        return $this->__('Conditions');
    }
    
    public function canShowTab() {
        return true;
    }

    public function isHidden() {
        return false;
    }

    protected function _prepareForm() {
        $model = Mage::registry('multilocationinventory_warehouse');
        $form = new Varien_Data_Form();

        $form->setHtmlIdPrefix('conditions_');

        // conditions
        $renderer = Mage::getBlockSingleton('adminhtml/widget_form_renderer_fieldset')
            ->setTemplate('promo/fieldset.phtml')
            ->setNewChildUrl($this->getUrl('*/multilocationinventory_warehouse/newConditionHtml/form/rule_conditions_fieldset'));
        
        $fieldset = $form->addFieldset('conditions_fieldset', array(
            'legend'=>Mage::helper('salesrule')->__('This warehouse will be used only if the following conditions are met (leave blank if you do not need it)')
        ))->setRenderer($renderer);

        $fieldset->addField('conditions', 'text', array(
            'name' => 'conditions',
            'label' => Mage::helper('salesrule')->__('Apply to'),
            'title' => Mage::helper('salesrule')->__('Apply to'),
            'required' => true,
        ))->setRule($model)->setRenderer(Mage::getBlockSingleton('rule/conditions'));
        
        
        $form->setValues($model->getData());

        $this->setForm($form);

        return parent::_prepareForm();
    }
    
    private function getShippingMethods() {
//        $carriers = Mage::getSingleton('shipping/config')->getActiveCarriers();        
//        $methods = array();
//        foreach ($carriers as $code=>$carriersModel) {
//            $title = Mage::getStoreConfig('carriers/'.$code.'/title');
//            if ($title) $methods[] = array('value'=>$code, 'label'=>$title);
//        }
//        return $methods;
        
        $carriers = Mage::getSingleton('shipping/config')->getActiveCarriers();
        $methods = array();
        foreach ($carriers as $_code => $carrier) {
            $title = Mage::getStoreConfig("carriers/$_code/title");    
            if ($_methods = $carrier->getAllowedMethods()) {
                foreach($_methods as $_mcode => $_method) {
                    if ($_method) $methods[] = array('value' => $_code . '_' . $_mcode, 'label' => $_method);
                }
            }
        }
        return $methods;
    }

    private function getPaymentMethods() {
        $methods = Mage::getSingleton('adminhtml/system_config_source_payment_allowedmethods')->toOptionArray();
        if (isset($methods[0])) {
            unset($methods[0]);
        }
        return $methods;
        
        //$payments = Mage::getSingleton('payment/config')->getActiveMethods();
        //$methods = array();
        //foreach ($payments as $paymentCode=>$paymentModel) {
            //$methods[] = array('value'=>$paymentCode, 'label'=>Mage::getStoreConfig('payment/'.$paymentCode.'/title'));
        //}
        //return $methods;
    }

}
