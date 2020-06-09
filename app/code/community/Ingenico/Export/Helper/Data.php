<?php

define('ING_ENCRYPT_METHOD', 'AES-256-CBC');
define('ING_SECRET_IV', 'cOwsHp7HPnoZa29Qz0oecw==');

class Ingenico_Export_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function encrypt($data, $password)
    {

        $output = openssl_encrypt($data, ING_ENCRYPT_METHOD, $password, 0, base64_decode(ING_SECRET_IV));
        return base64_encode($output);
    }

    public function decrypt($data, $password) {
        $output = base64_decode($data);
        return openssl_decrypt($output, ING_ENCRYPT_METHOD, $password, 0, base64_decode(ING_SECRET_IV));
    }

    public function export()
    {
        set_time_limit(0);
        ini_set('memory_limit', '8192M');

        // Get Stores Configuration
        $storesConfig = array();
        if (!Mage::app()->isSingleStoreMode()) {
            $stores = Mage::app()->getStores(true);
            foreach ($stores as $store) {
                /** @var Mage_Core_Model_Store $store */
                $storesConfig[] = [
                    'id' => $store->getId(),
                    'code' => $store->getCode(),
                    'is_active' => $store->getIsActive(),
                ];
            }
        } else {
            $storesConfig[] = [
                'id' => 1,
                'code' => 'default',
                'is_active' => 1,
            ];
        }

        // Get module configuration
        $config = array();
        if (Mage::getStoreConfig('payment_services/inexport/export_conf')) {
            $encrypted = array(
                'payment_services/ops/secret_key_in',
                'payment_services/ops/secret_key_out',
                'payment_services/ops/api_pswd'
            );

            // Get general settings like payment_services/ops/%
            $configDataCollection = Mage::getModel('core/config_data')
                ->getCollection()
                ->addPathFilter('payment_services/ops');

            foreach ($configDataCollection as $data) {
                $config[] = array(
                    'scope'     => $data->getScope(),
                    'scope_id'  => $data->getScopeId(),
                    'path'      => $data->getPath(),
                    'value'     => in_array($data->getPath(), $encrypted) ? Mage::helper('core')->decrypt($data->getValue()) : $data->getValue(),
                );
            }

            // Get methods settings like payment/ops_%
            $configDataCollection = Mage::getModel('core/config_data')
                ->getCollection()
                ->addFieldToFilter('path', array('like' => 'payment/ops_%'));

            foreach ($configDataCollection as $data) {
                $config[$data->getPath()] = array(
                    'scope'      => $data->getScope(),
                    'scope_id' => $data->getScopeId(),
                    'path'      => $data->getPath(),
                    'value'     => in_array($data->getPath(), $encrypted) ? Mage::helper('core')->decrypt($data->getValue()) : $data->getValue(),
                );
            }
        }

        // Get aliases
        $aliases = array();
        if (Mage::getStoreConfig('payment_services/inexport/export_aliases')) {
            $resource = Mage::getSingleton('core/resource');
            $readConnection = $resource->getConnection('core_read');
            $query = "SELECT * FROM `" . $resource->getTableName('ops_alias') . "` WHERE state = 'active';";
            $results = $readConnection->fetchAll($query);
            foreach ($results as $result) {
                $customerId = $result['customer_id'];
                $storeId = $result['store_id'];

                $email = null;
                /** @var Mage_Customer_Model_Customer $customer */
                $customer = Mage::getModel('customer/customer')->load($customerId);
                if ($customer->getId()) {
                    $email = $customer->getEmail();
                }

                $storeCode = null;
                if ($storeId > 0) {
                    $storeCode = Mage::app()->getStore($storeId)->getCode();
                }

                $aliases[] = array(
                    'customer_email' => $email,
                    'alias' => $result['alias'],
                    'card_holder' => $result['card_holder'],
                    'brand' => $result['brand'],
                    'masked' => $result['pseudo_account_or_cc_no'],
                    'expiration_date' => $result['expiration_date'],
                    'payment_method' => $result['payment_method'],
                    'store_id' => $result['store_id'],
                    'store_code' => $storeCode,
                    'created_at' => $result['created_at']
                );
            }
        }

        return array(
            'stores' => $storesConfig,
            'config' => $config,
            'aliases' => $aliases,
        );
    }
}
