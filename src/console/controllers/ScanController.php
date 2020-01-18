<?php
namespace weareferal\assetversioner\console\controllers;

use weareferal\assetversioner\AssetVersioner;

use Craft;
use yii\console\Controller;
use yii\helpers\Console;


class ScanController extends Controller
{
    public function actionIndex()
    {
        $results = AssetVersioner::getInstance()->scan->scan();

        $this->stdout('Folders:' . PHP_EOL, Console::UNDERLINE);
        foreach($results["files"] as $file) {
            $this->stdout($file . PHP_EOL);
        }
        $this->stdout(PHP_EOL);

        $this->stdout('Deleted Files:' . PHP_EOL, Console::FG_RED, Console::UNDERLINE);
        foreach($results["deleted_files"] as $file) {
            $this->stdout($file . PHP_EOL);
        }
        $this->stdout(PHP_EOL);

        $this->stdout('Generated Versions:' . PHP_EOL, Console::FG_GREEN, Console::UNDERLINE);
        foreach($results["versioned_files"] as $file) {
            $this->stdout($file . PHP_EOL);
        }
    }
}
