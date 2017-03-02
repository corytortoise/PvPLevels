<?php

	namespace corytortoise\PvPLevels;
	
   use pocketmine\Player;
   use pocketmine\Server;
   use pocketmine\plugin\PluginBase;
   use pocketmine\scheduler\ServerScheduler;
   use pocketmine\scheduler\PluginTask;
   use pocketmine\utils\TextFormat as C;
   use pocketmine\utils\Config;
   use pocketmine\command\CommandSender;
   use pocketmine\command\Command;
   use pocketmine\item\Item;

   //PvPLevels files

   use corytortoise\PvPLevels\EventListener;
   use corytortoise\PvPLevels\PlayerData;
	
	  class Main extends PluginBase {

      private $cfg;
      private $playerData = array();
		
      public function onEnable() {
            $this->saveDefaultConfig();
            $this->reloadConfig();
            @mkdir($this->getDataFolder());
            @mkdir($this->getDataFolder() . "players/");
            $this->cfg = $this->getConfig();
            $listener = new EventListener($this);
            $this->getServer()->getPluginManager()->registerEvents($listener, $this);
            $this->getLogger()->notice(C::GOLD ."PvPLevels: " . count(array_keys($this->cfg->getAll())) . " levels loaded!");
		   }

        public function addKill(Player $player) {
          $data = $this->getData($player->getName());
          $data->addKill();
          //Thank you, StackOverflow. XD
          $maxlevel = max(array_keys($this->cfg->getAll()));
          $player->sendMessage("Starting debug...");
          if($data->getLevel() >= $maxlevel) {
            //Do nothing, because they have reached the highest level possible.
            $player->sendMessage("greater than max");
            return;
          }
          elseif($data->getLevel() < $maxLevel) {
            $player->sendMessage("less than max level");
            $level = $this->cfg->get($data->getLevel() + 1);
            if($data->getKills() == $level["kills"]) {
              $player->sendMessage("Level up");
              $data->levelUp();
              foreach($level["commands"] as $command) {
                $cmd = str_replace(["%p", "%k", "%d", "%kdr", "%l"], [$player->getName(), $data->getKills(), $data->getDeaths(), $data->getKdr(), $data->getLevel()], $command);
                $this->getServer()->dispatchCommand($cmd);
              }
            }
          }
        }

        public function addDeath(Player $player) {
          $this->getData($player->getName())->addDeath();
          return;
        }
  
        public function getData($name) {
          return new PlayerData($this, $name);
        }

        public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
          if(strtolower($command->getName()) == "pvpstats") {
            if($sender instanceof Player) {
              if(isset($args[0])) {
              $player = $this->getServer()->getPlayerExact($args[0]);
              if($player !== null) {
              $data = $this->getData($player->getName());
              $name = $player->getName();
              } else {
                $sender->sendMessage(C::RED . "Player is not online");
                return true;
              }
            } else {
              $data = $this->getData($sender->getName());
              $name = $sender->getName();
            }
            $sender->sendMessage(C::GRAY . "[" . C::GOLD . "PvP" . C::YELLOW . "Stats" . C::GRAY . "] \n" . C::GREEN . "*************" . C::YELLOW . "* Player: " . $name . "\n" .  C::YELLOW . "* Level: " . $data->getLevel() . "\n" . C::YELLOW . "* Kills: " . $data->getKills() . "\n" . C::YELLOW . "* Deaths: " . $data->getDeaths() . "\n" .  C::YELLOW . "* K/D: " . $data->getKdr() . "\n" .  C::GREEN . "*************");
            return true;
          } else {
            $sender->sendMessage(C::RED . "Please run this command in-game");
            return true;
          }
        }
      }

    }
