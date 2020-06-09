<?php

require_once 'abstract.php';

class Ingenico_Shell_Export extends Mage_Shell_Abstract
{
    public function run()
    {
        $file = $this->getArg('file');
        $password = $this->getArg('password');
        if (empty($file) || empty($password)) {
            echo $this->usageHelp();
        } else {
            echo "Exporting...\n";
            $result = Mage::helper('inexport')->export();
            echo "Encryption...\n";
            $output = Mage::helper('inexport')->encrypt(json_encode($result), $password);
            echo "Writing...\n";
            file_put_contents($file, $output);
        }
    }

    /**
     * Retrieve Usage Help Message
     *
     */
    public function usageHelp()
    {
        return <<<USAGE
Usage:  php -f ingenico_export.php --password <password> --file <filename>

  --file <filename>     Export data into file
  --password <password> Password

USAGE;
    }
}

$shell = new Ingenico_Shell_Export();
$shell->run();
