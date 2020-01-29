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
        if (!AssetVersioner::getInstance()->getSettings()->staticVersioningEnabled) {
            $this->stdout('Static file versioning is not enabled. Please enable via either a config setting or via the Control Panel settings' . PHP_EOL, Console::FG_RED, Console::UNDERLINE);
            return 0;
        }

        $result = AssetVersioner::getInstance()->scan->scan();

        $this->stdout('Paths:' . PHP_EOL, Console::UNDERLINE);
        foreach($result["paths"] as $path) {
            $this->stdout($path . PHP_EOL);
        }
        $this->stdout(PHP_EOL);

        if (array_key_exists("deleted_paths", $result)) {
            $this->stdout('Deleted Paths:' . PHP_EOL, Console::FG_RED, Console::UNDERLINE);
            foreach($result["deleted_paths"] as $path) {
                $this->stdout($path . PHP_EOL);
            }
            $this->stdout(PHP_EOL);
        }

        $this->stdout('Generated Paths:' . PHP_EOL, Console::FG_GREEN, Console::UNDERLINE);
        foreach($result["versioned_paths"] as $path) {
            $this->stdout($path . PHP_EOL);
        }
    }
}
