<?php
require_once (
    dirname(dirname(dirname(dirname(realpath(__FILE__))))) . DIRECTORY_SEPARATOR . 'shell' . DIRECTORY_SEPARATOR . 'abstract.php'
);

class Mage_Shell_DataflowExport extends Mage_Shell_Abstract
{

    public function run() {
        $type = $this->getArg('type');
        if (!$this->getArg('type')) {
            echo "Usage: php -f pimgentoImport.php -type [option]\r\n";
            echo "Where: option is 'product', 'image', 'variant', or 'option'\r\n";
            return;
        }
        pimgentoImport($type);
    }
}

$shell = new Mage_Shell_DataflowExport();
$shell->run();

/**
 * Function to perform PIMGento import tasks
 * User: Martin Hopkins
 * Date: 23/06/2016
 * Time: 08:30
 * @param $type - file type to load, one of product, variant, option, image
 * @param $command- import task to perform
 */
function pimgentoImport($type)
{
        $command = 'pimgento_' . $type;
        $file = Mage::getStoreConfig('pimdata/' . $type . '/cron_file');

        if (!$file) {
            echo 'No file configured in PIMGento cron option for ' . $type . ' - exiting';
            return;
        }

        error_reporting(E_ALL);
        ini_set('display_errors', 1);

        set_time_limit(0);

        umask(0);

        $helper = Mage::helper('pimgento_core');

        try {
            // Start the task
            $model = Mage::getSingleton('pimgento_core/task');
            $task = $model->load($command);
            $task->setFile(null);
            $task->setFile($helper->getCronDir() . $file);

            $data = $task->getTask();
            if ($data['type'] == 'file' && !is_file($task->getFile())) {
                return;
            }

            $task->setNoReindex(false);
            echo 'Executing task to import ' . $command . "\r\n";
            echo "\r\n";
            while (!$task->taskIsOver()) {
                $task->execute();
                echo $task->getStepComment() . "\r\n";
                echo $task->getMessage() . "\r\n";
                $task->nextStep();
            }
            echo "\r\n";

            echo "Task " . $command . " complete\r\n";
        } catch (Exception $e) {
            echo $e->getMessage() . (PHP_SAPI === 'cli' ? "\n" : '<br/>');
        }

}