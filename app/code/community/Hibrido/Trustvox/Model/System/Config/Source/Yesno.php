<?php

class Hibrido_Trustvox_Model_System_Config_Source_Yesno {
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'sim', 'label' => Mage::helper('adminhtml')->__('Sim')),
            array('value' => 'nao', 'label' => Mage::helper('adminhtml')->__('Não'))
        );
    }

    /**
     * Get options in "key-value" format
     *
     * @return array
     */
    public function toArray()
    {
        return array(
            'sim' => Mage::helper('adminhtml')->__('Sim'),
            'nao' => Mage::helper('adminhtml')->__('Não')
        );
    }
}
