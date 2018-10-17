<?php

namespace corytortoise\PvPLevels;

use pocketmine\scheduler\Task;
use corytortoise\PvPLevels\Main;

class UpdateTask extends Task {

private $plugin;

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function onRun($currentTick) : void {
        $this->plugin->updateTexts();
    }
}


