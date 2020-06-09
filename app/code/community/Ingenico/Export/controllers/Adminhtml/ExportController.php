<?php

class Ingenico_Export_Adminhtml_ExportController extends Mage_Adminhtml_Controller_Action
{
    public function indexAction()
    {
        $result = Mage::helper('inexport')->export();

        // Set headers
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="export.json.data";');

        echo Mage::helper('inexport')->encrypt(json_encode($result), Mage::getStoreConfig('payment_services/inexport/encryptionkey'));
        exit();
    }
}
