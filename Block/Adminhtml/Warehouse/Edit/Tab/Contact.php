<?php

class Aitoc_MultiLocationInventory_Block_Adminhtml_Warehouse_Edit_Tab_Contact extends Mage_Adminhtml_Block_Widget_Form implements Mage_Adminhtml_Block_Widget_Tab_Interface
{
    public function getTabLabel() {
        return $this->__('Contact');
    }
   
    public function getTabTitle() {
        return $this->__('Contact');
    }

    public function canShowTab() {
        return true;
    }

    public function isHidden() {
        return false;
    }

    protected function _prepareForm() {
        $form = new Varien_Data_Form();
        $form->setHtmlIdPrefix('contact_');

        $fieldset = $form->addFieldset('base_fieldset', array('legend'=>$this->__('Contact')));

        $model = Mage::registry('multilocationinventory_warehouse');

        $fieldset->addField('contact_name', 'text', array(
            'name' => 'contact_name',
            'label' => $this->__('Contact Name'),
            'title' => $this->__('Contact Name'),
        ));

        $fieldset->addField('contact_email', 'text', array(
            'name' => 'contact_email',
            'label' => $this->__('Contact Email'),
            'title' => $this->__('Contact Email'),
            'class' => 'validate-email'
        ));


        $countries = $this->helper('directory')->getCountryCollection()->loadByStore(0)->toOptionArray();
        $fieldset->addField('country_id', 'select', array(
            'name' => 'country_id',
            'label' => $this->__('Country'),
            'title' => $this->__('Country'),
            'values' => $countries //Mage::getModel('adminhtml/system_config_source_country')->toOptionArray()
        ));

        $regions = Mage::getModel('directory/region')
            ->getCollection()
            ->addCountryFilter($model->getCountryId())
            ->toOptionArray();
        $regions[0]['label'] = '';

        $fieldset->addField('region_id', 'select', array(
            'label'     => $this->__('State'),
            'title'     => $this->__('State'),
            'name'      => 'region_id',
            'values'   => $regions
        ));

        $fieldset->addField('postcode', 'text', array(
            'name' => 'postcode',
            'label'     => $this->__('Zip/Post Code'),
            'title'     => $this->__('Zip/Post Code'),
            'class' => 'validate-zip'
        ));

        $fieldset->addField('city', 'text', array(
            'name' => 'city',
            'label'     => $this->__('City'),
            'title'     => $this->__('City'),
        ));

        $fieldset->addField('street1', 'text', array(
            'name' => 'street1',
            'label'     => $this->__('Street Address'),
            'title'     => $this->__('Street Address'),
        ));

        $fieldset->addField('street2', 'text', array(
            'name' => 'street2',
            'label'     => $this->__('Street Address 2'),
            'title'     => $this->__('Street Address 2'),
        ));

        $data = $model->getData();
        list($data['street1'], $data['street2']) = explode("\n", $data['street'], 2);

        $form->setValues($data);
        $this->setForm($form);
        return parent::_prepareForm();
    }

    protected function _afterToHtml($html)
    {
        return $html .
            '<script type="text/javascript">
                var updater = new RegionUpdater(
                    "contact_country_id",
                    "contact_region",
                    "contact_region_id",
                    ' . $this->helper('directory')->getRegionJson() .',
                    "disable"
                );
                updater.update();
            </script>';
    }
}
