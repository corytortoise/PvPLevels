<?php

namespace corytortoise\PvPLevels;

use pocketmine\scheduler\Task;


class JoinTask extends Task {

    private $name = "";
    private $plugin;

    public function __construct(Main $plugin, $name) {
        $this->name = $name;
        $this->plugin = $plugin;
    }

    public function onRun($currentTick) {
        $this->plugin->joinText($this->name);
    }
}

