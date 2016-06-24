<?php
/**
 * A shell script to run the Pimgento imports.  Largely lifted from code in Pimgento/Core/Model/Cron.php
 * Martin Hopkins - 23/6/2016
 *
 */
//
// The following finds /shell/abstract.php.  The depth depends on where the script is.  As I am using modman
// I need to go up four levels.  Without modman, the script can be put in the /shell folder in which case
// require_once("abstract.php") should work.
require_once (
    dirname(dirname(dirname(dirname(realpath(__FILE__))))) . DIRECTORY_SEPARATOR . 'shell' . DIRECTORY_SEPARATOR . 'abstract.php'
);

class Mage_Shell_DataflowExport extends Mage_Shell_Abstract
{

    public function run() {
        $type = $this->getArg('type');
        if (!$this->getArg('type')) {
            echo "Usage: php -f pimgentoImport.php -type [option]\r\n";
            echo "Where: option is 'product', 'image', 'variant', 'option', 'family', 'category'\r\n";
            return;
        }
        pimgentoImport($type);
    }
}

$shell = new Mage_Shell_DataflowExport();
$shell->run();

/**
 * Function to perform Pimgento import tasks
 *
 * @param $type - file type to load, one of product, variant, option, image etc
 */
function pimgentoImport($type)
{
        $command = 'pimgento_' . $type;
        $file = Mage::getStoreConfig('pimdata/' . $type . '/cron_file');

        if (!$file) {
            echo 'No file configured in Pimgento cron option for ' . $type . ' - exiting\r\n';
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
                echo 'File ' . $task->getFile() . ' does not exist - exiting\r\n';
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