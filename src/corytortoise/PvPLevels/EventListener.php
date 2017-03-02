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

    public function onDeath(PlayerDeathEvent $e) {
      $v = $e->getPlayer();
      $this->plugin->addDeath($v);
      $cause = $e->getPlayer()->getLastDamageCause();
      if($cause instanceof EntityDamageByEntityEvent) {
        if($cause->getDamager() instanceof Player) {
          $this->plugin->addKill($cause->getDamager());
          }
        }
      }

    }
