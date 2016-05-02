<?php

class Hibrido_Trustvox_Model_System_Config_Source_Posicoes {
    /**
     * Options getter
     *
     * @return array
     */
    public function toOptionArray()
    {
        return array(
            array('value' => 'padrao', 'label' => Mage::helper('adminhtml')->__('Padrão')),
            array('value' => 'personalizado', 'label' => Mage::helper('adminhtml')->__('Personalizado'))
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
            'padrao' => Mage::helper('adminhtml')->__('Padrão'),
            'personalizado' => Mage::helper('adminhtml')->__('Personalizado')
        );
    }
}
