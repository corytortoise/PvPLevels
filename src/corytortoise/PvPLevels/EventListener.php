<?php

namespace corytortoise\PvPLevels;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\{EntityDamageEvent, EntityDamageByEntityEvent};
use pocketmine\{Server, Player};

class EventListener implements Listener {

    private $plugin;


    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }
    
    /**
     * 
     * @param PlayerDeathEvent $e
     * 
     */
    public function onDeath(PlayerDeathEvent $e) {
        $v = $e->getPlayer();
        $cause = $e->getPlayer()->getLastDamageCause();
        if($cause instanceof EntityDamageByEntityEvent) {
            if($cause->getDamager() instanceof Player) {
                $this->plugin->addKill($cause->getDamager());
                $this->plugin->handleStreak($cause->getDamager(), $v);
            }
        }
        $this->plugin->addDeath($v);
    }

}
