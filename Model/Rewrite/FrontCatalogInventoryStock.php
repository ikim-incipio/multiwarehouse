<?php

class Aitoc_MultiLocationInventory_Model_Rewrite_FrontCatalogInventoryStock extends Mage_CatalogInventory_Model_Stock
{
    /**
     * Subtract product qtys from stock.
     * Return array of items that require full save
     *
     * @param array $items
     * @return array
     */
    public function registerProductsSale($items)
    {
        /** 
         * custom code by Isaac on 1-24-2017
         * avoid partial shipment if one warehouse can fulfill
         * the entire order even though its not the closest
         */

        // true if all the products have the same priority warehouse
        // assume the most optimal situation
        $matching = 1;

        // used to check and compare the initial priority warehouse for all products
        $priority = 0;

        foreach($items as $productId => $item){
            $stockItem = $item['item'];
            $warehouseIds = $stockItem->getWarehouseIds();
            // check for key to skip configurable products
            if (key($warehouseIds)){
                reset($warehouseIds);
                if ($priority == 0){
                    $priority = key($warehouseIds);
                } else if ($priority != key($warehouseIds)){
                    $matching = 0;
                }
            }
        }

        /** 
         * runs ONLY if we have partial shipment situation
         * 1. check to see if there is a warehouse that can fulfill entire order
         * 2. if there is, rearrange the priority
         */
        if (!$matching){
            // variable used to keep track of warehouse availability
            $available = [];
            // get list of all warehouses to compare against
            $warehouses = Mage::getResourceModel('aitoc_multilocationinventory/warehouse_collection');
            foreach($warehouses as $warehouse){
                // true if this warehouse is available for all products 
                // assuming true - most optimal situation
                $exists = 1;
                $warehouseId = $warehouse->getId();
                foreach($items as $productId => $item){
                    $stockItem = $item['item'];
                    $warehouseIds = $stockItem->getWarehouseIds();
                    // check for key to skip configurable products
                    if (key($warehouseIds)){
                        // if a product is not available in this warehouse...
                        if (!isset($warehouseIds[$warehouseId])){
                           $exists = 0;
                        }
                    }
                }
                $available[$warehouse->getId()] = $exists;
            }
            // id of warehouse that can fulfill entire order
            // if there is no such warehouse value is 0
            $optimalWarehouse = 0;
            for ($i=0;$i<=count($available);$i++){
                if ($available[$i]){
                    $optimalWarehouse = $i;
                }
            }
            // runs ONLY if we have an optimal warehouse to avoid partial shipment
            if ($optimalWarehouse > 0){
                foreach($items as $productId => $item){
                    $stockItem = $item['item'];
                    $warehouseIds = $stockItem->getWarehouseIds();
                    // check for key to skip configurable products
                    if (key($warehouseIds)){
                        // rearrange by inserting the priority warehouse first then the rest
                        $rearranged = [];
                        $rearranged[$optimalWarehouse] = $warehouseIds[$optimalWarehouse];
                        foreach($warehouseIds as $warehouseId => $qty){
                            if ($warehouseId != $optimalWarehouse){
                                $rearranged[$warehouseId] = $qty;
                            }
                        }
                        $stockItem->setWarehouseIds($rearranged);
                        $stockItem->save();
                    }
                }
            }
        }

        // original aitoc code starts here
        $fullSaveItems = array();

        foreach($items as $productId => $item) {
            if (empty($item['item'])) {
                $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
            } else {
                $stockItem = $item['item'];
            }
            $canSubtractQty = $stockItem->getId() && $stockItem->canSubtractQty();
            if ($canSubtractQty && Mage::helper('catalogInventory')->isQty($stockItem->getTypeId())) {
                if (!$stockItem->checkQty($item['qty'])) {
                    Mage::throwException(Mage::helper('cataloginventory')->__('Not all products are available in the requested quantity'));
                }

                $stockItem->subtractQty($item['qty']);

                if (!$stockItem->verifyStock() || $stockItem->verifyNotification()) {
                    $fullSaveItems[] = $stockItem;
                }

                $stockItem->save();
            }
        }

        return $fullSaveItems;
    }

    /**
     * Get back to stock (when order is canceled or whatever else)
     *
     * @param int $productId
     * @param numeric $qty
     * @return Mage_CatalogInventory_Model_Stock
     */
    public function backItemQty($productId, $qty, $warehouseIds = array(), $orderItemId = null)
    {
        $stockItem = Mage::getModel('cataloginventory/stock_item')->loadByProduct($productId);
        if ($stockItem->getId() && Mage::helper('catalogInventory')->isQty($stockItem->getTypeId())) {

            // aitoc fix
            $stockItem->setWarehouseIds($warehouseIds);
            $stockItem->setOrderItemId($orderItemId);

            $stockItem->addQty($qty);
            if ($stockItem->getCanBackInStock() && $stockItem->getQty() > $stockItem->getMinQty()) {
                $stockItem->setIsInStock(true)
                    ->setStockStatusChangedAutomaticallyFlag(true);
            }
            $stockItem->save();
        }
        return $this;
    }
}
