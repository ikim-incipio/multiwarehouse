<?php
/**
 * @copyright  Copyright (c) 2009 AITOC, Inc. 
 */

class Aitoc_Aitquantitymanager_Model_Rewrite_FrontCatalogInventoryStockStatus extends Mage_CatalogInventory_Model_Stock_Status
{
    // override parent
    protected function _construct()
    {
        Mage::getModel('aitquantitymanager/moduleObserver')->onAitocModuleLoad();
        
#        $this->_init('cataloginventory/stock_status');
        $this->_init('aitquantitymanager/stock_status');
    }

    // override parent
    public function updateStatus($productId, $productType = null, $websiteId = null)
    {
        
        if (is_null($productType)) {
            $productType = $this->getProductType($productId);
        }
        
        $product = mage::getModel('catalog/product')->load($productId);
        
        $aProductWebsiteHash = $product->getWebsiteIds();
        
        if ($aProductWebsiteHash)
        {
            $aProductWebsiteHash[] = Mage::helper('aitquantitymanager')->getHiddenWebsiteId();
            
            foreach ($aProductWebsiteHash as $iWebsiteId)
            {
                $item = $this->getStockItemModel()->loadByProductWebsite($productId, $iWebsiteId);
                
                if ($item)
                {
                    $status  = self::STATUS_IN_STOCK;
                    $qty     = 0;
                    if ($item->getId()) {
                        $status = $item->getIsInStock();
                        $qty    = $item->getQty();
                    }
            
// start ait
                    if (!$item->getManageStock()) // fix for product list
                    {
                        $qty = 0;
                    }
// finish ait
            
                    $this->_processChildren($productId, $productType, $qty, $status, $item->getStockId(), $item->getWebsiteId());
                    $this->_processParents($productId, $item->getStockId(), $item->getWebsiteId());
                
                }
            }
        }
        
        /*
        if (is_null($productType)) {
            $productType = $this->getProductType($productId);
        }

        $item = $this->getStockItemModel()->loadByProduct($productId);

        $status  = self::STATUS_IN_STOCK;
        $qty     = 0;
        if ($item->getId()) {
            $status = $item->getIsInStock();
            $qty    = $item->getQty();
        }

// start ait
        if (!$item->getManageStock()) // fix for product list
        {
            $qty = 0;
        }
// finish ait

///ait/        $this->_processChildren($productId, $productType, $qty, $status, $item->getStockId(), $websiteId);
        $this->_processChildren($productId, $productType, $qty, $status, $item->getStockId(), $item->getWebsiteId());
///ait/        $this->_processParents($productId, $item->getStockId(), $websiteId);
        $this->_processParents($productId, $item->getStockId(), $item->getWebsiteId());

        return $this;
        */
    }

    
    // override parent
    public function changeItemStatus(Mage_CatalogInventory_Model_Stock_Item $item)
    {
        $productId  = $item->getProductId();
        if (!$productType = $item->getProductTypeId()) {
            $productType    = $this->getProductType($productId);
        }
        $status     = (int)$item->getIsInStock();
        $qty        = (int)$item->getQty();
        
// start ait
        if (!$item->getManageStock()) // fix for product list
        {
            $qty = 0;
        }
// finish ait

///ait/        $this->_processChildren($productId, $productType, $qty, $status, $item->getStockId());
        $this->_processChildren($productId, $productType, $qty, $status, $item->getStockId(), $item->getWebsiteId());
///ait/        $this->_processParents($productId, $item->getStockId());
        $this->_processParents($productId, $item->getStockId(), $item->getWebsiteId());

        return $this;
    }

    // override parent
    protected function _processChildren($productId, $productType, $qty = 0, $status = self::STATUS_IN_STOCK, $stockId = 1, $websiteId = null)
    {
        if ($status == self::STATUS_OUT_OF_STOCK) {
            $this->saveProductStatus($productId, $status, $qty, $stockId, $websiteId);
            return $this;
        }

        $statuses   = array();
        $websites   = $this->getWebsites($websiteId);

        foreach (array_keys($websites) as $websiteId) {
            /* @var $website Mage_Core_Model_Website */
            $statuses[$websiteId] = $status;
        }
        
        if (!$typeInstance = $this->getProductTypeInstance($productType)) {
            return $this;
        }

        $requiredChildrenIds = $typeInstance->getChildrenIds($productId, true);
        if ($requiredChildrenIds) {
            $childrenIds = array();
            foreach ($requiredChildrenIds as $groupedChildrenIds) {
                $childrenIds = array_merge($childrenIds, $groupedChildrenIds);
            }
            $childrenWebsites = Mage::getSingleton('catalog/product_website')
                ->getWebsites($childrenIds);
                
// start ait

if ($childrenWebsites)
{
    foreach ($childrenWebsites as $iId => $aData)
    {
        $childrenWebsites[$iId][] = Mage::helper('aitquantitymanager')->getHiddenWebsiteId(); 
    }
}
// fin ait
                
                
            foreach ($websites as $websiteId => $storeId) {
                $childrenStatus = $this->getProductStatusModel()
                    ->getProductStatus($childrenIds, $storeId);
                $childrenStock  = $this->getProductStatus($childrenIds, $websiteId, $stockId);

                $websiteStatus = $statuses[$websiteId];
                foreach ($requiredChildrenIds as $groupedChildrenIds) {
                    $optionStatus = false;
                    foreach ($groupedChildrenIds as $childId) {
                        if (isset($childrenStatus[$childId])
                            and isset($childrenWebsites[$childId])
                            and in_array($websiteId, $childrenWebsites[$childId])
                            and $childrenStatus[$childId] == $this->getProductStatusEnabled()
                            and isset($childrenStock[$childId])
                            and $childrenStock[$childId] == self::STATUS_IN_STOCK
                        ) {
                            $optionStatus = true;
                        }
                    }
                    
                    $websiteStatus = $websiteStatus && $optionStatus;
                }
                $statuses[$websiteId] = (int)$websiteStatus;
            }
        }
        
        foreach ($statuses as $websiteId => $websiteStatus) {
            
            $this->saveProductStatus($productId, $websiteStatus, $qty, $stockId, $websiteId);
        }
        
        return $this;
    }

}
