<?php

namespace corytortoise\PvPLevels;

use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as C;
use pocketmine\utils\Config;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\math\Vector3;
use pocketmine\level\particle\FloatingTextParticle;

//PvPLevels files

use corytortoise\PvPLevels\EventListener;
use corytortoise\PvPLevels\PlayerData;

class Main extends PluginBase {

    private $cfg;
    private $texts;
    private $playerData = [];
    private $particles = [];

    public function onEnable() {
        $this->saveDefaultConfig();
        $this->reloadConfig();
        @mkdir($this->getDataFolder());
        @mkdir($this->getDataFolder() . "players/");
        $this->cfg = $this->getConfig();
        $this->texts = new Config($this->getDataFolder() . "texts.yml", Config::YAML);
        $listener = new EventListener($this);
        $this->getServer()->getPluginManager()->registerEvents($listener, $this);
        if(!$this->cfg->get("texts")["timer"] === false) {
            $interval = $this->cfg->get("texts")["timer"] ?? 60;
            $this->getScheduler()->scheduleDelayedRepeatingTask(new UpdateTask($this), $interval * 20, $interval * 20);
        }
        $this->getLogger()->notice(C::GOLD . count(array_keys($this->cfg->getAll())) . " levels loaded!");
        $this->getLogger()->notice(C::GOLD . count(array_keys($this->texts->getAll())) . " floating texts loaded!");
    }

    public function joinText(string $name) {
        foreach($this->texts->getAll() as $loc => $type) {
        $pos = explode("_", $loc);
            if(isset($pos[1])) {
                $v3 = new Vector3(round($pos[0], 2),round($pos[1], 2),round($pos[2], 2));
                $this->createText($v3, $type, [$this->getServer()->getPlayerExact($name)]);
            }
        }
    }

    /**
     * Initializes Floating Texts.
     * @param Vector3 $location
     * @param string $type
     * @param array $players
     */
    public function createText(Vector3 $location, string $type = "levels", $players = null) {
        $typetitle = $this->colorize($this->getConfig()->get("texts")[$type]);
        $id = implode("_", [$location->getX(), $location->getY(), $location->getZ()]);
        $this->getServer()->getLevelByName($this->cfg->get("texts")["world"])->addParticle($particle = new FloatingTextParticle($location, C::GOLD . "<<<<<>>>>>", $typetitle . "\n" . $this->getRankings($type)), $players);
        $this->particles[$id] = $particle;
    }

    public function updateTexts() {
        foreach($this->particles as $id => $text) {
            $type = $this->texts->get($id);
            $typetitle = $this->colorize($this->getConfig()->get("texts")[$type]);
            $text->setTitle(C::GOLD . $typetitle . "\n" . $this->getRankings($type));
            $this->getServer()->getLevelByName($this->cfg->get("texts")["world"])->addParticle($text);
        }
    }

    /**
     * Adds a kill to stats, and checks for a levelup.
     * @param Player $player
     */
    public function addKill(Player $player) {
        $data = $this->getData($player->getName());
        $data->addKill();
        $maxLevel = max(array_keys($this->cfg->getAll()));
        if($data->getLevel() >= $maxLevel) {
            return;
        }
        elseif($data->getLevel() < $maxLevel) {
            $level = $this->cfg->getAll()[$data->getLevel() + 1];
            if($data->getKills() == $level["kills"]) {
                $player->sendPopup(C::GREEN . "Level up");
                $data->levelUp();
                foreach($level["commands"] as $command) {
                    $cmd = str_replace(["%p", "%k", "%s", "%d", "%kdr", "%l"], ["\"" . $player->getName() . "\"", $data->getKills(), $data->getStreak(), $data->getDeaths(), $data->getKdr(), $data->getLevel()], $command);
                    $this->getServer()->dispatchCommand(new ConsoleCommandSender(), $cmd);
                }
            }
        }
    }

    //TODO: Add Custom KillStreak messages and commands.
    /**
     * Handles killstreaks and streak breaks.
     * @param Player $player
     * @param Player $v
     */
    public function handleStreak(Player $player, Player $v) {
        $killer = $this->getData($player->getName());
        $loser = $this->getData($v->getName());
        $oldStreak = $loser->getStreak();
        if($oldStreak >= 5) {
            $v->sendMessage(C::GRAY . "[" . C::GOLD . "PvP" . C::YELLOW . "Stats" . C::GRAY . "] " . C::YELLOW . "Your " . $oldStreak . " killstreak was ended by " . $player->getName() . "!");
            $player->sendMessage(C::GRAY . "[" . C::GOLD . "PvP" . C::YELLOW . "Stats" . C::GRAY . "] " . C::YELLOW . "You have ended " . $v->getName() . "'s " . $oldStreak . " killstreak!");
        }
        $newStreak = $killer->getStreak();
        if(is_int($newStreak / 5)) {
            $this->getServer()->broadcastMessage(C::GRAY . "[" . C::GOLD . "PvP" . C::YELLOW . "Stats" . C::GRAY . "] " . C::YELLOW . $player->getName() . " is on a " . $newStreak . " killstreak!");
        }
    }

    /**
     * Adds a death to stats.
     * @param Player $player
     */
    public function addDeath(Player $player) {
        $this->getData($player->getName())->addDeath();
        return;
    }

    /**
     * Returns the PlayerData object for a player.
     * @param type $name
     * @return PlayerData
     */
    public function getData($name) {
        return new PlayerData($this, $name);
    }

    /**
     *
     * @param CommandSender $sender
     * @param Command $command
     * @param string $label
     * @param array $args
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool {
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
                $sender->sendMessage(C::GRAY . "[" . C::GOLD . "PvP" . C::YELLOW . "Stats" . C::GRAY . "] \n" . C::GREEN . "*************\n" . C::YELLOW . "* Player: " . $name . "\n" .  C::YELLOW . "* Level: " . $data->getLevel() . "\n" . C::YELLOW . "* Kills: " . $data->getKills() . "\n" . C::YELLOW . "* Killstreak: " . $data->getStreak() . "\n" . C::YELLOW . "* Deaths: " . $data->getDeaths() . "\n" .  C::YELLOW . "* K/D: " . $data->getKdr() . "\n" .  C::GREEN . "*************");
                return true;
            } else {
                $sender->sendMessage(C::RED . "Please run this command in-game");
                return true;
            }
        }
        if(strtolower($command->getName()) == "pvptext") {
            if($sender instanceof Player) {
                if(isset($args[0])) {
                    if(in_array($args[0], ["levels", "kills", "kdr", "streaks"])) {
                        $v3 = implode("_", [round($sender->getX(), 2), round($sender->getY(), 2) + 1.7, round($sender->getZ(), 2)]);
                        $this->texts->set($v3, $args[0]);
                        $this->texts->save();
                        $this->createText(new Vector3(round($sender->getX(), 2), round($sender->getY(), 2) + 1.7, round($sender->getZ(), 2)), $args[0], null);
                        $sender->sendMessage(C::GRAY . "[" . C::GOLD . "PvP" . C::YELLOW . "Stats" . C::GRAY . "] \n" . C::GREEN . $args[0] . " leaderboard created!");
                        return true;
                    } elseif(in_array($args[0], ["del", "remove", "delete"])) {
                        $text = $this->isNearText($sender);
                        if(isset($this->particles[$text])) {
                            if($this->particles[$text] instanceof FloatingTextParticle) {
                                $this->particles[$text]->setInvisible();
                                $this->getServer()->getLevelByName($this->cfg->get("texts")["world"])->addParticle($this->particles[$text], [$sender]);
                                $this->texts->remove($text);
                                $this->texts->save();
                                if(isset($this->particles[$text])) {
                                    unset($this->particles[$text]);
                                }
                                $sender->sendMessage(C::GOLD . "Floating Text removed.");
                                return true;
                            } else {
                                $sender->sendMessage(C::RED . "Floating Text not found.");
                                return true;
                            }
                        } else {
                            $sender->sendMessage(C::RED . "Floating Text not found.");
                            return true;
                        }
                    } else {
                        $sender->sendMessage(C::RED . "Please define what type of text you want, e.g. \"kills\", \"levels\", \"kdr\", \"streaks\", or \"delete\"");
                        return true;
                    }
                } else {
                    $sender->sendMessage(C::RED . "Please define what type of text you want, e.g. \"kills\", \"levels\", \"kdr\", \"streaks\", or \"delete\"");
                    return true;
                }
            } else {
                $sender->sendMessage(C::RED . "Please run this command in-game");
                return true;
            }
        }
        return true;
    }

    public function isNearText($player) {
        foreach($this->texts->getAll() as $loc => $type) {
            $v3 = explode("_", $loc);
            if(isset($v3[1])) {
                $text = new Vector3($v3[0], $v3[1], $v3[2]);
                if($player->distance($text) <= 5 && $player->distance($text) > 0) {
                    return $loc;
                }
            }
        }
        return false;
    }

    public function getRankings(string $type) {
        $files = scandir($this->getDataFolder() . "players/");
        $stats = [];
        //Maybe just use str_replace instead?
        switch($type) {
            case "levels":
                $string = "level";
                break;
            case "kills":
                $string = "kills";
                break;
            case "kdr":
                $string = "kdr";
                break;
            case "streaks":
                $string = "killstreak";
                break;
            default:
                break;
        }
        foreach($files as $file) {
            if(pathinfo($file, PATHINFO_EXTENSION) == "yml") {
                $yaml = file_get_contents($this->getDataFolder() . "players/" . $file);
                $rawData = yaml_parse($yaml);
                if(isset($rawData[$string])) {
                    $stats[$rawData["name"]] = $rawData[$string];
                }
            }
        }
        arsort($stats, SORT_NUMERIC);
        $finalRankings = "";
        $i = 1;
        foreach($stats as $name => $number) {
            $finalRankings .= C::YELLOW . $i . ".) " . $name . ": " . $number . "\n";
            if($i > $this->getConfig()->get("texts")["top"]) {
                return $finalRankings;
            }
            if(count($stats) <= $i) {
                return $finalRankings;
            }
            $i++;
        }
        return "";
    }

    //TODO: Use Color class instead of str_replace.
    /**
     *
     * @param string $text
     * @return type
     */
    public function colorize(string $text) {
        $newText = str_replace("&", "§", $text);
        return $newText;
    }

}