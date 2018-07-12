<?php

namespace corytortoise\PvPLevels;

use pocketmine\scheduler\Task;

use corytortoise\PvPLevels\Main;

class TimerTask implements Task {

    private $player;
    private $plugin;

    public function __construct(Main $plugin, string $player) {
        $this->plugin = $plugin;
        $this->player = $player;
    }

    public function onRun($currentTick) : void {
        $this->plugin->joinText($this->player);
    }
}