<?php
/**
 * @copyright  Copyright (c) 2009 AITOC, Inc. 
 */


class Aitoc_Aitquantitymanager_Block_Rewrite_AdminReviewProductGrid extends Mage_Adminhtml_Block_Review_Product_Grid
{
    // override parent
    public function _prepareCollection()
    {
        $store = $this->_getStore(); // aitoc code
        $iWebsiteId = $store->getWebsiteId(); // aitoc code
      
        if (!$iWebsiteId)
        {
            $iWebsiteId = Mage::helper('aitquantitymanager')->getHiddenWebsiteId();
        }
        
        $collection = Mage::getModel('catalog/product')->getCollection()
            ->addAttributeToSelect('sku')
            ->addAttributeToSelect('name')
            ->addAttributeToSelect('attribute_set_id')
            ->addAttributeToSelect('type_id')
            ->joinField('qty',
//                'cataloginventory/stock_staus',
                'aitquantitymanager/stock_item', // aitoc code
#                'aitquantitymanager/stock_status', // aitoc code
                'qty',
                'product_id=entity_id',
//                '{{table}}.stock_id=1',
                '{{table}}.stock_id=1 AND {{table}}.website_id = ' . $iWebsiteId, // aitoc code
                'left');
#d($iWebsiteId);
        if ($store->getId()) {
            //$collection->setStoreId($store->getId());
            $collection->addStoreFilter($store);
            $collection->joinAttribute('custom_name', 'catalog_product/name', 'entity_id', null, 'inner', $store->getId());
            $collection->joinAttribute('status', 'catalog_product/status', 'entity_id', null, 'inner', $store->getId());
            $collection->joinAttribute('visibility', 'catalog_product/visibility', 'entity_id', null, 'inner', $store->getId());
            $collection->joinAttribute('price', 'catalog_product/price', 'entity_id', null, 'left', $store->getId());
        }
        else {
            $collection->addAttributeToSelect('price');
            $collection->addAttributeToSelect('status');
            $collection->addAttributeToSelect('visibility');
        }

        $this->setCollection($collection);

        Mage_Adminhtml_Block_Widget_Grid::_prepareCollection(); // aitoc code
        $this->getCollection()->addWebsiteNamesToResult();
        return $this;
    }
}
