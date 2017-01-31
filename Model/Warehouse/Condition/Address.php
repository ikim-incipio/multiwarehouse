<?php
class Aitoc_MultiLocationInventory_Model_Warehouse_Condition_Address extends Mage_SalesRule_Model_Rule_Condition_Address
{
    /**
     * Validate product attribute value for condition
     *
     * @param   mixed $validatedValue product attribute value
     * @return  bool
     */
    public function validateAttribute($validatedValue)
    {
        if (is_null($validatedValue)) {
            return true;
        }
        return parent::validateAttribute($validatedValue);
    }
}
