<?php

class Aitoc_MultiLocationInventory_Adminhtml_Multilocationinventory_WarehouseController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction() {
        $title = $this->__('Manage Warehouses');
        $this->_title($title);
        $this->loadLayout()
            ->_setActiveMenu('catalog/aitoc_multilocationinventory')
            ->_addBreadcrumb($title, $title)
            ->renderLayout();
    }

    public function newAction() {
        $this->_forward('edit');
    }

    public function editAction() {
        $id = (int) $this->getRequest()->getParam('id');
        $model = Mage::getModel('aitoc_multilocationinventory/warehouse')->load($id);
        if ($id==0 || $model->getId()) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data)) {
                $model->setData($data)->setId($id);
            }
            if ($id==0) {
                $model->setStatus(1)
                    ->setStoreId(0)
                    ->setSortOrder(0);
            }
            $model->getConditions()->setJsFormObject('rule_conditions_fieldset');
            Mage::register('multilocationinventory_warehouse', $model);

            if ($model->getId()) {
                $title = $this->__("Edit Warehouse '%s'", $model->getName());
            } else {
                $title = $this->__('New Warehouse');
            }

            $this->_title($title);
            $this->loadLayout()
                ->_setActiveMenu('catalog/warehouse')
                ->_addBreadcrumb($title, $title);
            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Warehouse does not exist'));
            $this->_redirect('*/*/');
        }
    }

    public function newConditionHtmlAction() {
        $id = $this->getRequest()->getParam('id');
        $typeArr = explode('|', str_replace('-', '/', $this->getRequest()->getParam('type')));
        $type = $typeArr[0];

        $model = Mage::getModel($type)
            ->setId($id)
            ->setType($type)
            ->setRule(Mage::getModel('salesrule/rule'))
            ->setPrefix('conditions');
        if (!empty($typeArr[1])) {
            $model->setAttribute($typeArr[1]);
        }

        if ($model instanceof Mage_Rule_Model_Condition_Abstract) {
            $model->setJsFormObject($this->getRequest()->getParam('form'));
            $html = $model->asHtmlRecursive();
        } else {
            $html = '';
        }
        $this->getResponse()->setBody($html);
    }

    private function _getStores($loadDefault=false) {
        return Mage::getModel('core/store')
            ->getResourceCollection()
            ->setLoadDefault($loadDefault)
            ->load();
    }

    private function _filterData($data, $isStripTags=true) {
        $result = array();
        $filter = new Zend_Filter();
        $filter->addFilter(new Zend_Filter_StringTrim());
        if ($isStripTags) $filter->addFilter(new Zend_Filter_StripTags());

        if ($data) {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $result[$key] = $this->_filterData($value, $isStripTags);
                } else {
                    $result[$key] = $filter->filter($value);
                }
            }
        }
        return $result;
    }

    public function saveAction() {
        $data = $this->getRequest()->getPost();
        $warehouseId = (int) $this->getRequest()->getParam('warehouse_id');
        $error = false;

        if ($data) {
            $data = $this->_filterData($data);
            $model = Mage::getModel('aitoc_multilocationinventory/warehouse');

            try {
                if (isset($data['rule']['conditions'])) {
                    $data['conditions'] = $data['rule']['conditions'];
                }

                if (isset($data['street1']) && isset($data['street2'])) {
                    $data['street'] = $data['street1'] . "\n" . $data['street2'];
                }

                $model->setData($data);
                $model->loadPost($data); // for save conditions

                if ($warehouseId) {
                    $model->setId($warehouseId);
                }
                $model->save();
                $warehouseId = $model->getId();

                if ($warehouseId) {
                    //* save stores
                    if ($data['stores']) {
                        $resource = Mage::getSingleton('core/resource');
                        $connection = $resource->getConnection('core_write');

                        $connection->delete(
                            $resource->getTableName('aitoc_multilocationinventory/warehouse_store'),
                            'warehouse_id = ' . intval($warehouseId)
                        );

                        $warehouseStores = array();
                        foreach ($data['stores'] as $storeId) {
                            $warehouseStores[] = array('store_id' => intval($storeId), 'warehouse_id' => $warehouseId);
                        }

                        $connection->insertMultiple(
                            $resource->getTableName('aitoc_multilocationinventory/warehouse_store'),
                            $warehouseStores
                        );
                    }
                } else {
                    Mage::getSingleton('adminhtml/session')->addError($this->__('Cannot add a record Warehouse. Please, try again.'));
                    $error = true;
                }
                if ($error) {
                    throw new Exception();
                }

                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Warehouse was successfully saved'));
                Mage::getSingleton('adminhtml/session')->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $warehouseId, 'tab'=>$this->getRequest()->getParam('tab')));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                if ($e->getMessage()) {
                    Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                }
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $warehouseId));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError($this->__('Unable to find Warehouse to save'));
        $this->_redirect('*/*/');
    }

    public function massDeleteAction() {
        $warehouseIds = $this->getRequest()->getParam('warehouse');
        if (!is_array($warehouseIds)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select Warehouse(s)'));
        } else {
            try {
                foreach ($warehouseIds as $warehouseId) {
                    Mage::getModel('aitoc_multilocationinventory/warehouse')->load($warehouseId)->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess($this->__('Total of %d record(s) were successfully deleted', count($warehouseIds)));
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    public function massStatusAction() {
        $warehouseIds = $this->getRequest()->getParam('warehouse');

        if (!is_array($warehouseIds)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select Warehouse(s)'));
        } else {
            try {
                foreach ($warehouseIds as $warehouseId) {
                    Mage::getSingleton('aitoc_multilocationinventory/warehouse')
                        ->load($warehouseId)
                        ->setStatus((int) $this->getRequest()->getParam('status'))
                        ->save();
                }
                $this->_getSession()->addSuccess($this->__('Total of %d record(s) were successfully updated', count($warehouseIds)));
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }
}
