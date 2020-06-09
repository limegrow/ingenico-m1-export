<?php

class Ingenico_Export_Block_Adminhtml_System_Config_Form_Field_Button extends Mage_Adminhtml_Block_System_Config_Form_Field
{
    protected function _getElementHtml(Varien_Data_Form_Element_Abstract $element)
    {
        $this->setElement($element);
        $url = Mage::helper('adminhtml')->getUrl('inexport/adminhtml_export/index');

        return $this->getLayout()->createBlock('adminhtml/widget_button')
            ->setType('button')
            ->setClass('scalable')
            ->setLabel('Export Data')
            ->setOnClick("setLocation('$url')")
            ->toHtml();
    }
}