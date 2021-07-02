<?php

namespace ElementalMinecraftGaming\JericoSystemisation;

use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use ElementalMinecraftGaming\JericoSystemisation\SXPInterval;
use ElementalMinecraftGaming\JericoSystemisation\libs\jojoe77777\FormAPI\SimpleForm;
use ElementalMinecraftGaming\JericoSystemisation\libs\jojoe77777\FormAPI\customForm;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\level\{Level,Position};
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\command\Command;
use pocketmine\event\Listener;

class Main extends PluginBase implements Listener {

    public $db;
    public $Interval;
    public $plugin;
    public $config;

    public function onEnable() {
        @mkdir($this->getDataFolder());
        $this->db = new \SQLite3($this->getDataFolder() . "JericoSystemisation.db");
        $this->db->exec("CREATE TABLE IF NOT EXISTS USystem(user TEXT PRIMARY KEY, system TEXT, lvl INT, sxp INT, skill TEXT);");
        $this->saveDefaultConfig();
        $this->Interval = new Config($this->getDataFolder() . "SXPInterval.yml", Config::YAML, array("SXPInterval" => 60));
        $this->config = new Config($this->getDataFolder() . "config.yml", Config::YAML);
        $this->playerskills = new Config($this->getDataFolder() . "PlayerSkills.yml", Config::YAML);
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
        $del = $this->db->prepare("INSERT OR REPLACE INTO USystem (user, system, lvl, sxp, skill) VALUES (:user, :system, :lvl, :sxp, :skill);");
        $del->bindValue(":user", $user);
        $del->bindValue(":system", $system);
        $del->bindValue(":lvl", 1);
        $del->bindValue(":sxp", 0);
        //$del->bindValue(":skills", "system");
        $del->bindValue(":skill", "system");
        $start = $del->execute();
    }

    public function setSkill($user, $skill) {
        $lvl = $this->getLvl($user);
        $sxp = $this->getSxp($user);
        $system = $this->getSystem($user);
        $del = $this->db->prepare("INSERT OR REPLACE INTO USystem (user, system, lvl, sxp, skill) VALUES (:user, :system, :lvl, :sxp, :skill);");
        $del->bindValue(":user", $user);
        $del->bindValue(":system", $system);
        $del->bindValue(":lvl", $lvl);
        $del->bindValue(":sxp", $sxp);
        $del->bindValue(":skill", $skill);
        $start = $del->execute();
    }

    public function getSystem($user) {
        $search = $this->db->prepare("SELECT system FROM USystem WHERE user = :user;");
        $search->bindValue(":user", $user);
        $start = $search->execute();
        $system = $start->fetchArray(SQLITE3_ASSOC);
        return $system["system"];
    }

    public function getLvl($user) {
        $search = $this->db->prepare("SELECT lvl FROM USystem WHERE user = :user;");
        $search->bindValue(":user", $user);
        $start = $search->execute();
        $lvl = $start->fetchArray(SQLITE3_ASSOC);
        return $lvl["lvl"];
    }

    public function getSxp($user) {
        $search = $this->db->prepare("SELECT sxp FROM USystem WHERE user = :user;");
        $search->bindValue(":user", $user);
        $start = $search->execute();
        $sxp = $start->fetchArray(SQLITE3_ASSOC);
        return $sxp["sxp"];
    }

    public function autoSXP() {
        $amount = 1;
        $players = $this->getServer()->getOnlinePlayers();
        foreach ($players as $player) {
            $user = $player->getName();
            $lvl = $this->getLvl($user);
            $sxp = $this->getSxp($user);
            $skill = $this->getSkill($user);
            $addsxp = $sxp + $amount;
            $system = $this->getSystem($user);
            $del = $this->db->prepare("INSERT OR REPLACE INTO USystem (user, system, lvl, sxp, skill) VALUES (:user, :system, :lvl, :sxp, :skill);");
            $del->bindValue(":user", $user);
            $del->bindValue(":system", $system);
            $del->bindValue(":lvl", $lvl);
            $del->bindValue(":sxp", $addsxp);
            $del->bindValue(":skill", $skill);
            $start = $del->execute();
            if (!$sxp >= 10) {
                while ($sxp >= 10) {
                    $addsxp = $sxp - 10;
                    $system = $this->getSystem($user);
                    $del = $this->db->prepare("INSERT OR REPLACE INTO USystem (user, system, lvl, sxp, skill) VALUES (:user, :system, :lvl, :sxp, :skill);");
                    $del->bindValue(":user", $user);
                    $del->bindValue(":system", $system);
                    $del->bindValue(":lvl", $lvl);
                    $del->bindValue(":sxp", $addsxp);
                    $del->bindValue(":skill", $skill);
                    $start = $del->execute();
                    $this->lvlUp($user, 1);
                }
                return true;
            } else {
                return false;
            }
        }
    }

    public function addSXP($user, $amount) {
        $lvl = $this->getLvl($user);
        $sxp = $this->getSxp($user);
        $addsxp = $sxp + $amount;
        $system = $this->getSystem($user);
        $del = $this->db->prepare("INSERT OR REPLACE INTO USystem (user, system, lvl, sxp, skill) VALUES (:user, :system, :lvl, :sxp, :skill);");
        $del->bindValue(":user", $user);
        $del->bindValue(":system", $system);
        $del->bindValue(":lvl", $lvl);
        $del->bindValue(":sxp", $addsxp);
        $del->bindValue(":skill", $skill);
        $start = $del->execute();
        if (!$sxp >= 10) {
            while ($sxp >= 10) {
                $addsxp = $sxp - 10;
                $system = $this->getSystem($user);
                $del = $this->db->prepare("INSERT OR REPLACE INTO USystem (user, system, lvl, sxp, skill) VALUES (:user, :system, :lvl, :sxp, :skill);");
                $del->bindValue(":user", $user);
                $del->bindValue(":system", $system);
                $del->bindValue(":lvl", $lvl);
                $del->bindValue(":sxp", $addsxp);
                $del->bindValue(":skill", $skill);
                $start = $del->execute();
                $this->lvlUp($user, 1);
            }
            return true;
        } else {
            return false;
        }
    }

    public function getSkill($user) {
        $search = $this->db->prepare("SELECT skill FROM USystem WHERE user = :user;");
        $search->bindValue(":user", $user);
        $start = $search->execute();
        $skill = $start->fetchArray(SQLITE3_ASSOC);
        return $skill["skill"];
    }

    public function Msg($string) {
        return TextFormat::RED . "[" . TextFormat::DARK_PURPLE . "System" . TextFormat::RED . "] " . TextFormat::BLUE . "$string";
    }

    public function onPlayerCrouch(PlayerToggleSneakEvent $ev) {
        $p = $ev->getPlayer();
        $user = $p->getName();
        $system = $this->getSystem($user);
        if ($system == "MageSystem") {
            $activeskill = $this->getSkill($user);
            if ($activeskill == "FlameStar") {
                $px = $p->getX();
                $py = $p->getY();
                $pz = $p->getZ();
                $x = round($px);
                $y = round($py);
                $z = round($pz);
                $xplus = $x + 1;
                $xminus = $x - 1;
                $zplus = $z + 1;
                $zminus = $z - 1;
                $world = $ev->getPlayer()->getLevel();
                $lvl = $this->getLvl($user);
                while ($xplus < $x + $lvl) {
                    $block = Block::get(Block::FIRE);
                    $pos = $ev->getPlayer()->getLevel()->getBlock(new Vector3($x, $y, $z))->getId();
                    if ($pos == "Air") {
                        $world->setBlock(new Vector3($x, $y, $z), $block, true, true);
                    }
                    $pos = $ev->getPlayer()->getLevel()->getBlock(new Vector3($xplus, $y, $z))->getId();
                    if ($pos == "Air") {
                        $world->setBlock(new Vector3($xplus, $y, $z), $block, true, true);
                    }
                    $pos = $ev->getPlayer()->getLevel()->getBlock(new Vector3($xminus, $y, $z))->getId();
                    if ($pos == "Air") {
                        $world->setBlock(new Vector3($xminus, $y, $z), $block, true, true);
                    }
                    $pos = $ev->getPlayer()->getLevel()->getBlock(new Vector3($x, $y, $zplus))->getId();
                    if ($pos == "Air") {
                        $world->setBlock(new Vector3($x, $y, $zplus), $block, true, true);
                    }
                    $pos = $ev->getPlayer()->getLevel()->getBlock(new Vector3($x, $y, $zminus))->getId();
                    if ($pos == "Air") {
                        $world->setBlock(new Vector3($x, $y, $zminus), $block, true, true);
                    }
                    $pos = $ev->getPlayer()->getLevel()->getBlock(new Vector3($xplus, $y, $zplus))->getId();
                    if ($pos == "Air") {
                        $world->setBlock(new Vector3($xplus, $y, $zplus), $block, true, true);
                    }
                    $pos = $ev->getPlayer()->getLevel()->getBlock(new Vector3($xminus, $y, $zminus))->getId();
                    if ($pos == "Air") {
                        $world->setBlock(new Vector3($xminus, $y, $zminus), $block, true, true);
                    }
                    $pos = $ev->getPlayer()->getLevel()->getBlock(new Vector3($xplus, $y, $zminus))->getId();
                    if ($pos == "Air") {
                        $world->setBlock(new Vector3($xplus, $y, $zminus), $block, true, true);
                    }
                    $pos = $ev->getPlayer()->getLevel()->getBlock(new Vector3($xminus, $y, $zplus))->getId();
                    if ($pos == "Air") {
                        $world->setBlock(new Vector3($xminus, $y, $zplus), $block, true, true);
                    }
                    $xplus = $xplus + 1;
                    $xminus = $xminus - 1;
                    $zplus = $zplus + 1;
                    $zminus = $zminus - 1;
                }
            }
        }
    }

    public function onPlayerJoin(PlayerJoinEvent $ev) {
        $p = $ev->getPlayer();
        $user = $p->getName();
        $min = $this->config->get("MinSR");
        $max = $this->config->get("MaxSR");
        $checkSystem = $this->checkPSExists($user);
        if ($checkSystem == false) {
            $systemNumber = mt_rand($min, $max);
            $system = $this->config->get($systemNumber);
            $this->setSystem($user, $system);
            $arr = ["system"];
            $this->playerskills->set($user, $arr);
            $this->playerskills->save();
            $p->sendMessage($this->Msg("\nSystem synchronising to soul...\nSystem loading...\nSYSTEM LOADED!\nWelcome to the $system!\n\n==== You can look at your stats and select skills in /system ===="));
        } else {
            $system = $this->getSystem($user);
            $p->sendMessage($this->Msg("$system Loaded!"));
        }
    }

    public function setSkillForm($p, $user) {
        $form = new customForm(function (Player $player, $data) {
                    if (!$data[0]) {
                        return;
                    } else {
                        $user = $player->getName();
                        $skills = $this->playerskills->get($user);
                        foreach ($skills as $skill) {
                            if ($data[0] === $skill) {
                                $this->setSkill($user, $data[0]);
                                return true;
                            } else {
                                $player->sendMessage(TextFormat::RED . "You don't have this wisdom!");
                                return false;
                            }
                        }
                        return;
                    }
                });
        $system = $this->getSystem($user);
        $MenuInfoImage = $this->config->get("MenuInfo");
        $form->setTitle(TextFormat::DARK_PURPLE . $system);
        $skillList = $this->playerskills->get($user);
        $form->addInput(TextFormat::AQUA . "Skill:", "System");
        $form->sendToPlayer($p);
    }

    //=========COMMANDS=========\\
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool {
        if (strtolower($command->getName()) == "system") {
            if ($sender->hasPermission("csystem.use")) {
                if ($sender instanceof Player) {
                    $user = $sender->getName();
                    $lvl = $this->getLvl($user);
                    $sxp = $this->getSxp($user);
                    $system = $this->getSystem($user);
                    $MenuInfoImage = $this->config->get("MenuInfo");
                    $MenuSkillsImage = $this->config->get("MenuSkills");
                    $form = new SimpleForm(function (Player $player, $data) {
                                switch ($data) {
                                    case 0:
                                        $form = new customForm(function (Player $player, $data) {
                                                    switch ($data) {
                                                        case 0:
                                                            return;
                                                    }
                                                });
                                        $user = $player->getName();
                                        $system = $this->getSystem($user);
                                        $lvl = $this->getLvl($user);
                                        $activeskill = $this->getSkill($user);
                                        $sxp = $this->getSxp($user);
                                        $MenuInfoImage = $this->config->get("MenuInfo");
                                        $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                        $form->addLabel(TextFormat::GREEN . "System User: $user");
                                        $form->addLabel(TextFormat::GREEN . "Level: $lvl");
                                        $form->addLabel(TextFormat::GREEN . "XP: $sxp");
                                        $form->addLabel(TextFormat::GREEN . "Active Skill: $activeskill");
                                        $skillList = $this->playerskills->get($user);
                                        $form->sendToPlayer($player);
                                        return;
                                    case 1:
                                        $form = new simpleForm(function (Player $player, $data) {
                                                    switch ($data) {
                                                        case 1:
                                                            return;
                                                    }
                                                });
                                        $user = $player->getName();
                                        $system = $this->getSystem($user);
                                        $MenuSkillsImage = $this->config->get("MenuSkills");
                                        $MenuInfoImage = $this->config->get("MenuInfo");
                                        $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                        $skillList = $this->playerskills->get($user);
                                        $form->addButton(TextFormat::AQUA . "Done", 1, "$MenuInfoImage");
                                        foreach ($skillList as $skills) {
                                            $form->addButton(TextFormat::GOLD . "$skills", 1, "$MenuSkillsImage");
                                        }
                                        $form->sendToPlayer($player);
                                        return;
                                    case 2:
                                        $user = $player->getName();
                                        $this->setSkillForm($player, $user);
                                        return;
                                }
                            });
                    $form->setTitle($system);
                    $form->setContent(TextFormat::DARK_PURPLE . "Welcome, user of the $system, this is your menu!");
                    $form->addButton("Info", 1, "$MenuInfoImage");
                    $form->addButton("Skills", 1, "$MenuSkillsImage");
                    $form->addButton("Select Skill", 1, "$MenuSkillsImage");
                    $form->sendToPlayer($sender);
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
