<?php

  namespace corytortoise\PvPLevels;

  use pocketmine\Player;
  use pocketmine\utils\Config;

  use corytortoise\PvPLevels\Main;

    class PlayerData {

      private $plugin;
      private $player = null;
      private $kills = 0;
      private $level = 0;
      private $deaths = 0;

      public function __construct(Main $plugin, $player){
      $this->plugin = $plugin;
      $this->player = $player;
      $path = $this->getPath();
      if(is_file($path)) {
      $data = yaml_parse_file($path);
      $this->kills = $data["kills"];
      $this->deaths = $data["deaths"];
      $this->level = $data["level"];
      } else {
       return;
        }
    }

    public function getStats() {
      return $this->data;
    }

    public function getName() {
      return $this->player;
    }

    public function getKills() {
      return $this->kills;
    }

    public function getDeaths() {
      return $this->deaths;
    }

    public function getKdr() {
      if ($this->deaths > 0){
        return $this->kills / $this->deaths;
      }
      else{
        return $this->kills;
      }
    }

    public function getLevel() {
      return $this->level;
    }

    public function addKill() {
      $this->kills++;
      $this->save();
    }

    public function addDeath() {
      $this->deaths++;
      $this->save();
      }

    public function levelUp() {
      $this->level++;
      $this->save();
    }

    public function getPath() {
      return $this->plugin->getDataFolder() . "players/" . strtolower($this->player) . ".yml";
    }

    public function save() {
      yaml_emit_file($this->getPath(), ["name" => $this->player, "kills" => $this->kills, "deaths" => $this->deaths, "level" => $this->level]);
    }

  }
