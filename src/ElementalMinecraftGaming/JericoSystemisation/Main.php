<?php

namespace ElementalMinecraftGaming\JericoSystemisation;

use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use ElementalMinecraftGaming\JericoSystemisation\SXPInterval;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\command\Command;
use pocketmine\event\Listener;

class Main extends PluginBase implements Listener {

    public $db;
    public $Interval;
    public $plugin;

    public function onEnable() {
        @mkdir($this->getDataFolder());
        $this->db = new \SQLite3($this->getDataFolder() . "JericoSystemisation.db");
        $this->db->exec("CREATE TABLE IF NOT EXISTS USystem(user TEXT PRIMARY KEY, system TEXT, lvl INT, sxp INT, skills TEXT);");
        $this->saveDefaultConfig();
        $this->Interval = new Config($this->getDataFolder() . "SXPInterval.yml", Config::YAML, array("SXPInterval" => 60));
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->getScheduler()->scheduleRepeatingTask(new SXPInterval($this), $this->Interval->get("SXPInterval") * 20);
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }
    
    //=========EVENTS=========\\
    public function checkUSystem($user) {
        $search = $this->db->prepare("SELECT system FROM USystem WHERE user = :user;");
        $search->bindValue(":user", $user);
        $start = $search->execute();
        $got = $start->fetchArray(SQLITE3_ASSOC);
        return $got["system"];
    }
    
    public function checkPSExists($user) {
        $username = \SQLite3::escapeString($user);
        $search = $this->db->prepare("SELECT * FROM USystem WHERE user = :user;");
        $search->bindValue(":user", $username);
        $start = $search->execute();
        $checker = $start->fetchArray(SQLITE3_ASSOC);
        return empty($checker) == false;
    }
    
    public function setSystem($user, $system) {
        $del = $this->db->prepare("INSERT OR REPLACE INTO USystem (user, system, lvl, sxp, skills) VALUES (:user, :system, :lvl, :sxp, :skills);");
        $del->bindValue(":user", $user);
        $del->bindValue(":system", $system);
        $del->bindValue(":lvl", 1);
        $del->bindValue(":sxp", 0);
        $del->bindValue(":skills", "System");
        $start = $del->execute();
    }
    
    public function getSystem($p, $user) {
        $search = $this->db->prepare("SELECT system FROM USystem WHERE user = :user;");
        $search->bindValue(":user", $user);
        $start = $search->execute();
        $system = $start->fetchArray(SQLITE3_ASSOC);
        return $system["system"];
    }
    
    public function getLvl($p, $user) {
        $search = $this->db->prepare("SELECT lvl FROM USystem WHERE user = :user;");
        $search->bindValue(":user", $user);
        $start = $search->execute();
        $lvl = $start->fetchArray(SQLITE3_ASSOC);
        return $lvl["lvl"];
    }
    
    public function getSxp($p, $user) {
        $search = $this->db->prepare("SELECT sxp FROM USystem WHERE user = :user;");
        $search->bindValue(":user", $user);
        $start = $search->execute();
        $sxp = $start->fetchArray(SQLITE3_ASSOC);
        return $sxp["sxp"];
    }
    
    public function getSkills($p, $user) {
        $search = $this->db->prepare("SELECT skills FROM USystem WHERE user = :user;");
        $search->bindValue(":user", $user);
        $start = $search->execute();
        $skills = $start->fetchArray(SQLITE3_ASSOC);
        return $skills["skills"];
    }
    
    public function Msg($string) {
        return TextFormat::RED . "[" . TextFormat::DARK_PURPLE . "System" . TextFormat::RED . "] " . TextFormat::BLUE . "$string";
    }
    
    public function onPlayerJoin(PlayerJoinEvent $ev) {
        $p = $ev->getPlayer();
        $user = $p->getName();
        $min = $this->config->get("Min");
        $max = $this->config->get("Max");
        $checkSystem = $this->checkPSExists($user);
        if ($checkSystem == false) {
            $systemNumber = mt_rand($min, $max);
            $system = $this->config->get($systemNumber);
            $this->setSystem($user, $system);
            $lvl = $this->getLvl($p, $user);
            $skills = $this->getSkills($p, $user);
            $sxp = $this->getSxp($p, $user);
            $p->sendMessage($this->Msg("\nSystem synchronising to soul...\nSystem loading...\nSYSTEM LOADED!\nWelcome to the $system!\n\n====$system Stats====\nName: $user \nLVL: $lvl \nXP: $sxp \nSKILLS: $skills"));
        } else {
            $lvl = $this->getLvl($p, $user);
            $skills = $this->getSkills($p, $user);
            $sxp = $this->getSxp($p, $user);
            $system = $this->getSystem($p, $user);
            $p->sendMessage($this->Msg("\n====$system Stats====\nName: $user \nLVL: $lvl \nXP: $sxp \nSKILLS: $skills"));
        }
    }
    
    //=========COMMANDS=========\\
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if (strtolower($command->getName()) == "system") {
            if ($sender->hasPermission("csystem.use")) {
                if ($sender instanceof Player) {
                    $user = $sender->getName();
                    $lvl = $this->getLvl($sender, $user);
                    $skills = $this->getSkills($sender, $user);
                    $sxp = $this->getSxp($sender, $user);
                    $system = $this->getSystem($sender, $user);
                    $sender->sendMessage($this->Msg("\n====$system Stats====\nName: $user \nLVL: $lvl \nXP: $sxp \nSKILLS: $skills"));
                    return true;
                } else {
                    
                }
            } else {
                
            }
        } else {
            return false;
        }
        return false;
    }
}