<?php
namespace weareferal\assetversioner\events;

use yii\base\Event;

class FilesVersionedEvent extends Event
{
    public $versioned_files = [];
}