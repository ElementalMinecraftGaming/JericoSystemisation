<?php

namespace ElementalMinecraftGaming\JericoSystemisation;

use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use ElementalMinecraftGaming\JericoSystemisation\SXPInterval;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use jojoe77777\FormAPI\Form;
use revivalpmmp\pureentities\entity\monster\Monster;
use revivalpmmp\pureentities\PureEntities;
//use jasonwynn10\VanillaEntityAI\entity\InventoryHolder;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\level\particle\HeartParticle;
use pocketmine\level\particle\SporeParticle;
use pocketmine\level\{Level,Position};
use pocketmine\block\Block;
use pocketmine\math\Vector3;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\item;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Effect;
use pocketmine\command\Command;
use pocketmine\event\Listener;

class Main extends PluginBase implements Listener {

    public $db;
    public $Interval;
    public $plugin;
    public $config;
    public $playerskills;
    public $MonsterConfig;

    public function onEnable() {
        @mkdir($this->getDataFolder());
        $this->db = new \SQLite3($this->getDataFolder() . "JericoSystemisation.db");
        $this->db->exec("CREATE TABLE IF NOT EXISTS USystem(user TEXT PRIMARY KEY, system TEXT, lvl INT, sxp INT, skill TEXT, skillpoints INT);");
        $this->saveDefaultConfig();
        $this->saveResource("MonsterConfig.yml");
        if (!$this->getConfig()->get("Config-Version") == 1) {
            rename($this->getDataFolder() . "config.yml", $this->getDataFolder() . "config_old.yml");
            $this->saveResource("config.yml");
        }
        $this->MonsterConfig = new Config($this->getDataFolder() . "MonsterConfig.yml", Config::YAML);
        if (!$this->MonsterConfig->get("Config-Version") == 1) {
            rename($this->getDataFolder() . "MonsterConfig.yml", $this->getDataFolder() . "MonsterConfig_old.yml");
            $this->saveResource("MonsterConfig.yml");
        }
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
        $del = $this->db->prepare("INSERT OR REPLACE INTO USystem (user, system, lvl, sxp, skill, skillpoints) VALUES (:user, :system, :lvl, :sxp, :skill, :skillpoints);");
        $del->bindValue(":user", $user);
        $del->bindValue(":system", $system);
        $del->bindValue(":lvl", 1);
        $del->bindValue(":sxp", 0);
        $del->bindValue(":skill", "system");
        $del->bindValue(":skillpoints", 1);
        $start = $del->execute();
    }

    public function setSkill($user, $skill) {
        $lvl = $this->getLvl($user);
        $sxp = $this->getSxp($user);
        $skillpoints = $this->getSkillPoints($user);
        $system = $this->getSystem($user);
        $del = $this->db->prepare("INSERT OR REPLACE INTO USystem (user, system, lvl, sxp, skill, skillpoints) VALUES (:user, :system, :lvl, :sxp, :skill, :skillpoints);");
        $del->bindValue(":user", $user);
        $del->bindValue(":system", $system);
        $del->bindValue(":lvl", $lvl);
        $del->bindValue(":sxp", $sxp);
        $del->bindValue(":skill", $skill);
        $del->bindValue(":skillpoints", $skillpoints);
        $start = $del->execute();
    }

    public function setSkillPoints($user, $skillpoints) {
        $lvl = $this->getLvl($user);
        $sxp = $this->getSxp($user);
        $skill = $this->getSkill($user);
        $system = $this->getSystem($user);
        $del = $this->db->prepare("INSERT OR REPLACE INTO USystem (user, system, lvl, sxp, skill, skillpoints) VALUES (:user, :system, :lvl, :sxp, :skill, :skillpoints);");
        $del->bindValue(":user", $user);
        $del->bindValue(":system", $system);
        $del->bindValue(":lvl", $lvl);
        $del->bindValue(":sxp", $sxp);
        $del->bindValue(":skill", $skill);
        $del->bindValue(":skillpoints", $skillpoints);
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
            $playerWorld = $player->getLevel()->getFolderName();
            if ($playerWorld == $this->config->get("World")) {
                $user = $player->getName();
                $lvl = $this->getLvl($user);
                $sxp = $this->getSxp($user);
                $skill = $this->getSkill($user);
                $skillpoints = $this->getSkillPoints($user);
                $addsxp = $sxp + $amount;
                $system = $this->getSystem($user);
                $del = $this->db->prepare("INSERT OR REPLACE INTO USystem (user, system, lvl, sxp, skill, skillpoints) VALUES (:user, :system, :lvl, :sxp, :skill, :skillpoints);");
                $del->bindValue(":user", $user);
                $del->bindValue(":system", $system);
                $del->bindValue(":lvl", $lvl);
                $del->bindValue(":sxp", $addsxp);
                $del->bindValue(":skill", $skill);
                $del->bindValue(":skillpoints", $skillpoints);
                $start = $del->execute();
                $max = $this->config->get("MaxSXP");
                if ($sxp >= $max) {
                    while ($sxp >= $max) {
                        $this->lvlUp($user, 1);
                        $takesxp = $sxp - $max;
                        $system = $this->getSystem($user);
                        $del = $this->db->prepare("INSERT OR REPLACE INTO USystem (user, system, lvl, sxp, skill, skillpoints) VALUES (:user, :system, :lvl, :sxp, :skill, :skillpoints);");
                        $del->bindValue(":user", $user);
                        $del->bindValue(":system", $system);
                        $del->bindValue(":lvl", $lvl);
                        $del->bindValue(":sxp", $takesxp);
                        $del->bindValue(":skill", $skill);
                        $del->bindValue(":skillpoints", $skillpoints);
                        $start = $del->execute();
                    }
                }
            }
        }
    }

    public function addSXP($user, $amount) {
        $lvl = $this->getLvl($user);
        $sxp = $this->getSxp($user);
        $skillpoints = $this->getSkillPoints($user);
        $addsxp = $sxp + $amount;
        $skill = $this->getSkill($user);
        $system = $this->getSystem($user);
        $del = $this->db->prepare("INSERT OR REPLACE INTO USystem (user, system, lvl, sxp, skill, skillpoints) VALUES (:user, :system, :lvl, :sxp, :skill, :skillpoints);");
        $del->bindValue(":user", $user);
        $del->bindValue(":system", $system);
        $del->bindValue(":lvl", $lvl);
        $del->bindValue(":sxp", $addsxp);
        $del->bindValue(":skill", $skill);
        $del->bindValue(":skillpoints", $skillpoints);
        $start = $del->execute();
        $max = $this->config->get("MaxSXP");
        if ($sxp >= $max) {
            while ($sxp >= $max) {
                $this->lvlUp($user, 1);
                $takesxp = $sxp - $max;
                $system = $this->getSystem($user);
                $del = $this->db->prepare("INSERT OR REPLACE INTO USystem (user, system, lvl, sxp, skill, skillpoints) VALUES (:user, :system, :lvl, :sxp, :skill, :skillpoints);");
                $del->bindValue(":user", $user);
                $del->bindValue(":system", $system);
                $del->bindValue(":lvl", $lvl);
                $del->bindValue(":sxp", $takesxp);
                $del->bindValue(":skill", $skill);
                $del->bindValue(":skillpoints", $skillpoints);
                $start = $del->execute();
            }
        }
    }

    public function addSkillPoints($user, $amount) {
        $lvl = $this->getLvl($user);
        $sxp = $this->getSxp($user);
        $skill = $this->getSkill($user);
        $skillpoints = $this->getSkillPoints($user);
        $system = $this->getSystem($user);
        $del = $this->db->prepare("INSERT OR REPLACE INTO USystem (user, system, lvl, sxp, skill, skillpoints) VALUES (:user, :system, :lvl, :sxp, :skill, :skillpoints);");
        $del->bindValue(":user", $user);
        $del->bindValue(":system", $system);
        $del->bindValue(":lvl", $lvl);
        $del->bindValue(":sxp", $sxp);
        $del->bindValue(":skill", $skill);
        $del->bindValue(":skillpoints", $skillpoints + $amount);
        $start = $del->execute();
    }

    public function lvlUp($user, $amount) {
        $lvl = $this->getLvl($user);
        $sxp = $this->getSxp($user);
        $skill = $this->getSkill($user);
        $skillpoints = $this->getSkillPoints($user);
        $system = $this->getSystem($user);
        $del = $this->db->prepare("INSERT OR REPLACE INTO USystem (user, system, lvl, sxp, skill, skillpoints) VALUES (:user, :system, :lvl, :sxp, :skill, :skillpoints);");
        $del->bindValue(":user", $user);
        $del->bindValue(":system", $system);
        $del->bindValue(":lvl", $lvl + $amount);
        $del->bindValue(":sxp", $sxp);
        $del->bindValue(":skill", $skill);
        $del->bindValue(":skillpoints", $skillpoints);
        $start = $del->execute();
    }

    public function minusSkillPoints($user, $amount) {
        $lvl = $this->getLvl($user);
        $sxp = $this->getSxp($user);
        $skill = $this->getSkill($user);
        $skillpoints = $this->getSkillPoints($user);
        $system = $this->getSystem($user);
        $del = $this->db->prepare("INSERT OR REPLACE INTO USystem (user, system, lvl, sxp, skill, skillpoints) VALUES (:user, :system, :lvl, :sxp, :skill, :skillpoints);");
        $del->bindValue(":user", $user);
        $del->bindValue(":system", $system);
        $del->bindValue(":lvl", $lvl);
        $del->bindValue(":sxp", $sxp);
        $del->bindValue(":skill", $skill);
        $del->bindValue(":skillpoints", $skillpoints - $amount);
        $start = $del->execute();
    }

    public function getSkillPoints($user) {
        $search = $this->db->prepare("SELECT skillpoints FROM USystem WHERE user = :user;");
        $search->bindValue(":user", $user);
        $start = $search->execute();
        $skillpoints = $start->fetchArray(SQLITE3_ASSOC);
        return (INT) $skillpoints["skillpoints"];
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

    public function playerKilled(PlayerDeathEvent $ev) {
        $player = $ev->getPlayer();
        if ($player instanceof Player) {
            $getCause = $player->getLastDamageCause();
            if ($getCause instanceof EntityDamageByEntityEvent) {
                $killer = $getCause->getDamager();
                if ($killer instanceof Player) {
                    $user = $killer->getName();
                    $this->addSkillPoints($user, 2);
                }
            }
        }
    }

    public function entityKilled(EntityDeathEvent $ev) {
        $entity = $ev->getEntity();
        $getCause = $entity->getLastDamageCause();
        if ($getCause instanceof EntityDamageByEntityEvent) {
            $killer = $getCause->getDamager();
            if ($killer instanceof Player) {
                $monsterName = $entity->getNameTag();
                $nameList = $this->MonsterConfig->get("MonsterNames");
                $user = $killer->getName();
                if ($this->getServer()->getPluginManager()->getPlugin("PureEntitiesX")) {
                    $this->addSkillPoints($user, 1);
                    foreach ($nameList as $monster) {
                        if ($monsterName == $monster) {
                            $this->addSkillPoints($user, 2);
                        }
                    }
                } elseif ($entity instanceof Entity) {
                    $this->addSkillPoints($user, 1);
                }
            }
        }
    }

    public function damageEvent(EntityDamageEvent $event) {
        $attacked = $event->getEntity();
        if ($event instanceof EntityDamageByEntityEvent) {
            $attacker = $event->getDamager();
            if ($attacker instanceof Player) {
                $world = $attacker->getLevel()->getFolderName();
                if ($world == $this->config->get("World")) {
                    $Damage = $event->getBaseDamage();
                    $attackerSystem = $this->getSystem($attacker->getName());
                    $attackerSkill = $this->getSkill($attacker->getName());
                    if ($attackerSystem == "BerserkerSystem") {
                        if ($attackerSkill == "DoubleDamage") {
                            $item = $attacker->getInventory()->getItemInHand();
                            $event->setModifier($item->getAttackPoints() * 2, 3);
                        } elseif ($attackerSkill == "TripleDamage") {
                            $item = $attacker->getInventory()->getItemInHand();
                            $event->setModifier($item->getAttackPoints() * 3, 3);
                        } elseif ($attackerSkill == "Bloodlust") {
                            $item = $attacker->getInventory()->getItemInHand();
                            $event->setModifier($item->getAttackPoints() * 2, 3);
                            $health = $attacker->getHealth();
                            $attacker->setHealth($health - 1);
                            $attacker->addEffect(new EffectInstance(Effect::getEffect(Effect::ABSORPTION), 20 * 5, 2, false));
                        }
                    }
                }
            } elseif ($attacked instanceof Player) {
                $world = $attacked->getLevel()->getFolderName();
                if ($world == $this->config->get("World")) {
                    $entityTag = $attacker->getNameTag();
                    $monsterTags = $this->MonsterConfig->get("MonsterNames");
                    foreach ($monsterTags as $monsterName) {
                        if (!$entityTag == $monsterName) {
                            $demonizeTest = mt_rand(1, 2);
                            if ($demonizeTest == 1) {
                                $monsterName = $this->MonsterConfig->get(mt_rand(1, 10));
                                $attacker->setNameTag(TextFormat::RED . TextFormat::BOLD . $monsterName);
                                $attacker->setNameTagVisible(true);
                                $attacker->setNameTagAlwaysVisible(true);
                                $attacker->setScale(2);
                                $attacker->setMaxHealth(60);
                                $attacker->setHealth(60);
                                $attacked->sendMessage("Boss Health: 60");
                            }
                        } else {
                            $monsterSkill = $this->MonsterConfig->get(mt_rand(11, 12));
                            if ($monsterSkill == "FlameAttack") {
                                $attacked->setOnFire(5);
                            } elseif ($monsterSkill == "DoubleDamage") {
                                if ($this->getServer()->getPluginManager()->getPlugin("PureEntitiesX")) {
                                    if ($attacker instanceof Monster) {
                                        $item = $attacker->getDamage();
                                        $event->setModifier($item * 2, 3);
                                        /* } elseif ($this->getServer()->getPluginManager()->getPlugin("VanillaEntitiesAI")) {
                                          if ($attacker instanceof InventoryHolder) {
                                          $item = $attacker->getMainHand();
                                          $event->setModifier($item->getAttackPoints() * 2, 3);
                                          } */
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
    }

    public function onPlayerCrouch(PlayerToggleSneakEvent $ev) {
        $p = $ev->getPlayer();
        $user = $p->getName();
        $system = $this->getSystem($user);
        $world = $p->getLevel()->getFolderName();
        if ($world == $this->config->get("World")) {
            if ($system == "MageSystem" || $system == "DemonSystem") {
                $activeskill = $this->getSkill($user);
                if ($activeskill == "FlameStar" || $activeskill == "Mage") {
                    $x = $p->getX();
                    $y = $p->getY();
                    $z = $p->getZ();
                    $xplus = $x + 1;
                    $xminus = $x - 1;
                    $zplus = $z + 1;
                    $zminus = $z - 1;
                    $world = $ev->getPlayer()->getLevel();
                    $lvl = $this->getLvl($user);
                    if ($lvl <= 10) {
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
                    } else {
                        while ($xplus < $x + 10) {
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
                } elseif ($activeskill == "FireCross" || $activeskill == "Mage") {
                    $x = $p->getX();
                    $y = $p->getY();
                    $z = $p->getZ();
                    $xplus = $x + 1;
                    $xminus = $x - 1;
                    $zplus = $z + 1;
                    $zminus = $z - 1;
                    $world = $ev->getPlayer()->getLevel();
                    $lvl = $this->getLvl($user);
                    if ($lvl <= 10) {
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
                            if ($pos == "Air") {
                                $world->setBlock(new Vector3($xminus, $y, $z), $block, true, true);
                            }
                            if ($pos == "Air") {
                                $world->setBlock(new Vector3($x, $y, $zplus), $block, true, true);
                            }
                            if ($pos == "Air") {
                                $world->setBlock(new Vector3($x, $y, $zminus), $block, true, true);
                            }
                            $xplus = $xplus + 1;
                            $xminus = $xminus - 1;
                            $zplus = $zplus + 1;
                            $zminus = $zminus - 1;
                        }
                    } elseif ($lvl > 10) {
                        while ($xplus < $x + 10) {
                            $block = Block::get(Block::FIRE);
                            $pos = $ev->getPlayer()->getLevel()->getBlock(new Vector3($x, $y, $z))->getId();
                            if ($pos == "Air") {
                                $world->setBlock(new Vector3($x, $y, $z), $block, true, true);
                            }
                            $pos = $ev->getPlayer()->getLevel()->getBlock(new Vector3($xplus, $y, $z))->getId();
                            if ($pos == "Air") {
                                $world->setBlock(new Vector3($xplus, $y, $z), $block, true, true);
                            }
                            if ($pos == "Air") {
                                $world->setBlock(new Vector3($xminus, $y, $z), $block, true, true);
                            }
                            if ($pos == "Air") {
                                $world->setBlock(new Vector3($x, $y, $zplus), $block, true, true);
                            }
                            if ($pos == "Air") {
                                $world->setBlock(new Vector3($x, $y, $zminus), $block, true, true);
                            }
                            $xplus = $xplus + 1;
                            $xminus = $xminus - 1;
                            $zplus = $zplus + 1;
                            $zminus = $zminus - 1;
                        }
                    }
                } elseif ($activeskill == "MoltenStar" || $activeskill == "Mage") {
                    $x = $p->getX();
                    $y = $p->getY();
                    $z = $p->getZ();
                    $xplus = $x + 1;
                    $xminus = $x - 1;
                    $zplus = $z + 1;
                    $zminus = $z - 1;
                    $world = $ev->getPlayer()->getLevel();
                    $lvl = $this->getLvl($user);
                    if ($lvl <= 10) {
                        while ($xplus < $x + $lvl) {
                            $block = Block::get(Block::LAVA);
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
                    } else {
                        $maxlvl = 10;
                        while ($xplus < $x + $maxlvl) {
                            $block = Block::get(Block::LAVA);
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
                } elseif ($activeskill == "MoltenCross" || $activeskill == "Mage") {
                    $x = $p->getX();
                    $y = $p->getY();
                    $z = $p->getZ();
                    $xplus = $x + 1;
                    $xminus = $x - 1;
                    $zplus = $z + 1;
                    $zminus = $z - 1;
                    $world = $ev->getPlayer()->getLevel();
                    $lvl = $this->getLvl($user);
                    if ($lvl <= 10) {
                        while ($xplus < $x + $lvl) {
                            $block = Block::get(Block::LAVA);
                            $pos = $ev->getPlayer()->getLevel()->getBlock(new Vector3($x, $y, $z))->getId();
                            if ($pos == "Air") {
                                $world->setBlock(new Vector3($x, $y, $z), $block, true, true);
                            }
                            $pos = $ev->getPlayer()->getLevel()->getBlock(new Vector3($xplus, $y, $z))->getId();
                            if ($pos == "Air") {
                                $world->setBlock(new Vector3($xplus, $y, $z), $block, true, true);
                            }
                            if ($pos == "Air") {
                                $world->setBlock(new Vector3($xminus, $y, $z), $block, true, true);
                            }
                            if ($pos == "Air") {
                                $world->setBlock(new Vector3($x, $y, $zplus), $block, true, true);
                            }
                            if ($pos == "Air") {
                                $world->setBlock(new Vector3($x, $y, $zminus), $block, true, true);
                            }
                            $xplus = $xplus + 1;
                            $xminus = $xminus - 1;
                            $zplus = $zplus + 1;
                            $zminus = $zminus - 1;
                        }
                    } else {
                        $maxlvl = 10;
                        while ($xplus < $x + $maxlvl) {
                            $block = Block::get(Block::LAVA);
                            $pos = $ev->getPlayer()->getLevel()->getBlock(new Vector3($x, $y, $z))->getId();
                            if ($pos == "Air") {
                                $world->setBlock(new Vector3($x, $y, $z), $block, true, true);
                            }
                            $pos = $ev->getPlayer()->getLevel()->getBlock(new Vector3($xplus, $y, $z))->getId();
                            if ($pos == "Air") {
                                $world->setBlock(new Vector3($xplus, $y, $z), $block, true, true);
                            }
                            if ($pos == "Air") {
                                $world->setBlock(new Vector3($xminus, $y, $z), $block, true, true);
                            }
                            if ($pos == "Air") {
                                $world->setBlock(new Vector3($x, $y, $zplus), $block, true, true);
                            }
                            if ($pos == "Air") {
                                $world->setBlock(new Vector3($x, $y, $zminus), $block, true, true);
                            }
                            $xplus = $xplus + 1;
                            $xminus = $xminus - 1;
                            $zplus = $zplus + 1;
                            $zminus = $zminus - 1;
                        }
                    }
                } elseif ($activeskill == "ArrowShot" || $activeskill == "Mage") {
                    $yaw = $p->getYaw();
                    $pitch = $p->getPitch();
                    $nbt = new CompoundTag("", [
                        "Pos" => new ListTag("Pos", [
                            new DoubleTag("", $p->getX()),
                            new DoubleTag("", $p->getY() + 1),
                            new DoubleTag("", $p->getZ()),
                                ]),
                        "Motion" => new ListTag("Motion", [
                            new DoubleTag("", -sin($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI)),
                            new DoubleTag("", -sin($pitch / 180 * M_PI)),
                            new DoubleTag("", cos($yaw / 180 * M_PI) * cos($pitch / 180 * M_PI)),
                                ]),
                        "Rotation" => new ListTag("Rotation", [
                            new FloatTag("", $yaw),
                            new FloatTag("", $pitch),
                                ])
                    ]);
                    $shootingArrow = Entity::createEntity("Arrow", $p->getLevel(), $nbt);
                    $shootingArrow->spawnTo($p);
                }
            } elseif ($system == "SpellBreakerSystem") {
                $activeskill = $this->getSkill($user);
                if ($activeskill == "FireBreak") {
                    $p->extinguish();
                } elseif ($activeskill == "AbnormalBreak") {
                    $p->removeAllEffects();
                } elseif ($activeskill == "MoltenBreak") {
                    $p->extinguish();
                    $p->addEffect(new EffectInstance(Effect::getEffect(Effect::FIRE_RESISTANCE), 20 * 5, 2, false));
                } elseif ($activeskill == "AntiMage") {
                        $p->extinguish();
                        $p->removeAllEffects();
                        $p->addEffect(new EffectInstance(Effect::getEffect(Effect::FIRE_RESISTANCE), 20 * 5, 2, false));
                }
            } elseif ($system == "HealerSystem") {
                $activeskill = $this->getSkill($user);
                if ($activeskill == "Heal") {
                    $currentHealth = $p->getHealth();
                    $p->setHealth($currentHealth + 1);
                } elseif ($activeskill == "BigHeal") {
                    $currentHealth = $p->getHealth();
                    $p->setHealth($currentHealth + 4);
                } elseif ($activeskill == "MaxHeal") {
                    $maxHealth = $p->getMaxHealth();
                    $p->setHealth($maxHealth);
                } elseif ($activeskill == "BlessedHeal") {
                    foreach ($p->getLevel()->getNearbyEntities($p->getBoundingBox()->expandedCopy(5, 5, 5)) as $entity) {
                        if ($entity instanceof Player) {
                            $x = $entity->getX();
                            $y = $entity->getY();
                            $z = $entity->getZ();
                            $entity->removeAllEffects();
                            $maxHealth = $entity->getMaxHealth();
                            $entity->setHealth($maxHealth);
                            $entity->addEffect(new EffectInstance(Effect::getEffect(Effect::REGENERATION), 20 * 5, 2, false));
                            $entity->getLevel()->addParticle(new HeartParticle(new Vector3($x, $y + 1, $z + 1)));
                        }
                    }
                } elseif ($activeskill == "Buff") {
                    foreach ($p->getLevel()->getNearbyEntities($p->getBoundingBox()->expandedCopy(5, 5, 5)) as $entity) {
                        if ($entity instanceof Player) {
                            $x = $entity->getX();
                            $y = $entity->getY();
                            $z = $entity->getZ();
                            $entity->addEffect(new EffectInstance(Effect::getEffect(Effect::REGENERATION), 20 * 5, 3, false));
                            $entity->addEffect(new EffectInstance(Effect::getEffect(Effect::RESISTANCE), 20 * 5, 2, false));
                            $entity->addEffect(new EffectInstance(Effect::getEffect(Effect::FIRE_RESISTANCE), 20 * 5, 1, false));
                            $entity->getLevel()->addParticle(new HeartParticle(new Vector3($x, $y + 1, $z + 1)));
                        }
                    }
                } elseif ($activeskill == "Debuff") {
                    foreach ($p->getLevel()->getNearbyEntities($p->getBoundingBox()->expandedCopy(5, 5, 5)) as $entity) {
                        if ($entity->getId() !== $p->getId()) {
                            if ($entity instanceof Player) {
                                $x = $entity->getX();
                                $y = $entity->getY();
                                $z = $entity->getZ();
                                $entity->addEffect(new EffectInstance(Effect::getEffect(Effect::POISON), 20 * 5, 1, false));
                                $entity->addEffect(new EffectInstance(Effect::getEffect(Effect::BLINDNESS), 20 * 5, 2, false));
                                $entity->addEffect(new EffectInstance(Effect::getEffect(Effect::WEAKNESS), 20 * 5, 1, false));
                                $entity->addEffect(new EffectInstance(Effect::getEffect(Effect::NAUSEA), 20 * 5, 1, false));
                                $entity->getLevel()->addParticle(new SporeParticle(new Vector3($x, $y + 1, $z + 1)));
                            }
                        }
                    }
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
            $arr = ["system", "system", "system"];
            $this->playerskills->set($user, $arr);
            $this->playerskills->save();
            $p->sendMessage($this->Msg("\nSystem synchronising to soul...\nSystem loading...\nSYSTEM LOADED!\nWelcome to the $system!\n\n==== You can look at your stats and select skills in /system ===="));
        } else {
            $system = $this->getSystem($user);
            $p->sendMessage($this->Msg("$system Loaded!"));
        }
    }

    public function setSkillForm($p, $user) {
        $form = new CustomForm(function (Player $player, $data) {
                    $user = $player->getName();
                    $skills = $this->playerskills->get($user);
                    $skillOne = $skills[0];
                    $skillTwo = $skills[1];
                    $skillThree = $skills[2];
                    if (!$data == null) {
                        if ($data[0] === $skillOne) {
                            $this->setSkill($user, $data[0]);
                            return true;
                        } elseif ($data[0] === $skillTwo) {
                            $this->setSkill($user, $data[0]);
                            return true;
                        } elseif ($data[0] === $skillThree) {
                            $this->setSkill($user, $data[0]);
                            return true;
                        } else {
                            $player->sendMessage(TextFormat::RED . "You don't have this wisdom!");
                            return false;
                        }
                    } else {
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

    public function skillShopForm($p, $user) {
        $MenuInfoImage = $this->config->get("MenuInfo");
        $MenuSkillsImage = $this->config->get("MenuSkills");
        $MenuShopImage = $this->config->get("MenuShop");
        $system = $this->getSystem($user);
        if ($system == "MageSystem") {
            $form = new SimpleForm(function (Player $player, $data) {
                        switch ($data) {
                            case 0:
                                $form = new SimpleForm(function (Player $player, $data) {
                                            switch ($data) {
                                                case 0:
                                                    if ($this->getSkillPoints($player->getName()) >= $this->config->get("FlameStar")) {
                                                        $form = new SimpleForm(function (Player $player, $data) {
                                                                    switch ($data) {
                                                                        case 0:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("FlameStar");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillOne = "FlameStar";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 1:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("FlameStar");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillTwo = "FlameStar";
                                                                            $skillOne = $cskill[0];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 2:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("FlameStar");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillThree = "FlameStar";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillOne = $cskill[0];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 3:
                                                                            return;
                                                                    }
                                                                });
                                                        $user = $player->getName();
                                                        $system = $this->getSystem($user);
                                                        $cskill = $this->playerskills->get($user);
                                                        $skillThree = $cskill[2];
                                                        $skillTwo = $cskill[1];
                                                        $skillOne = $cskill[0];
                                                        $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                                        $form->setContent(TextFormat::BOLD . TextFormat::GOLD . "Replace Skill (Can not get it back for free)");
                                                        $form->addButton(TextFormat::RED . "$skillOne");
                                                        $form->addButton(TextFormat::RED . "$skillTwo");
                                                        $form->addButton(TextFormat::RED . "$skillThree");
                                                        $form->addButton(TextFormat::RED . "Exit");
                                                        $form->sendToPlayer($player);
                                                        return;
                                                    } else {
                                                        $player->sendMessage(TextFormat::RED . "You don't have the skill points to learn");
                                                    }
                                            }
                                        });
                                $user = $player->getName();
                                $system = $this->getSystem($user);
                                $price = $this->config->get("FlameStar");
                                $MenuShopImage = $this->config->get("MenuShop");
                                $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                $form->setContent(TextFormat::WHITE . "Creates a star of fire on the ground from where you are standing when you crouch. (Will not break blocks that are flame proof and won't appear at half blocks)\nBecomes stronger the higher your level.");
                                $form->addButton(TextFormat::GREEN . "Buy: $price SP", 1, $MenuShopImage);
                                $form->addButton(TextFormat::RED . "Back", 1, $MenuShopImage);
                                $form->sendToPlayer($player);
                                return;
                            case 1:
                                $form = new SimpleForm(function (Player $player, $data) {
                                            switch ($data) {
                                                case 0:
                                                    if ($this->getSkillPoints($player->getName()) >= $this->config->get("FlameCross")) {
                                                        $form = new SimpleForm(function (Player $player, $data) {
                                                                    switch ($data) {
                                                                        case 0:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("FlameCross");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillOne = "FlameCross";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 1:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("FlameCross");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillTwo = "FlameCross";
                                                                            $skillOne = $cskill[0];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 2:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("FlameCross");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillThree = "FlameCross";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillOne = $cskill[0];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 3:
                                                                            return;
                                                                    }
                                                                });
                                                        $user = $player->getName();
                                                        $system = $this->getSystem($user);
                                                        $cskill = $this->playerskills->get($user);
                                                        $skillThree = $cskill[2];
                                                        $skillTwo = $cskill[1];
                                                        $skillOne = $cskill[0];
                                                        $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                                        $form->setContent(TextFormat::BOLD . TextFormat::GOLD . "Replace Skill (Can not get it back for free)");
                                                        $form->addButton(TextFormat::RED . "$skillOne");
                                                        $form->addButton(TextFormat::RED . "$skillTwo");
                                                        $form->addButton(TextFormat::RED . "$skillThree");
                                                        $form->addButton(TextFormat::RED . "Exit");
                                                        $form->sendToPlayer($player);
                                                        return;
                                                    } else {
                                                        $player->sendMessage(TextFormat::RED . "You don't have the skill points to learn");
                                                    }
                                            }
                                        });
                                $user = $player->getName();
                                $system = $this->getSystem($user);
                                $price = $this->config->get("FlameCross");
                                $MenuShopImage = $this->config->get("MenuShop");
                                $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                $form->setContent(TextFormat::WHITE . "Creates a Cross of fire on the ground from where you are standing when you crouch. (Will not break blocks that are flame proof and won't appear at half blocks)\nBecomes stronger the higher your level.");
                                $form->addButton(TextFormat::GREEN . "Buy: $price SP", 1, $MenuShopImage);
                                $form->addButton(TextFormat::RED . "Back", 1, $MenuShopImage);
                                $form->sendToPlayer($player);
                                return;

                            case 2:
                                $form = new SimpleForm(function (Player $player, $data) {
                                            switch ($data) {
                                                case 0:
                                                    if ($this->getSkillPoints($player->getName()) >= $this->config->get("MoltenStar")) {
                                                        $form = new SimpleForm(function (Player $player, $data) {
                                                                    switch ($data) {
                                                                        case 0:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("MoltenStar");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillOne = "MoltenStar";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 1:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("MoltenStar");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillTwo = "MoltenStar";
                                                                            $skillOne = $cskill[0];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 2:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("MoltenStar");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillThree = "MoltenStar";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillOne = $cskill[0];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 3:
                                                                            return;
                                                                    }
                                                                });
                                                        $user = $player->getName();
                                                        $system = $this->getSystem($user);
                                                        $cskill = $this->playerskills->get($user);
                                                        $skillThree = $cskill[2];
                                                        $skillTwo = $cskill[1];
                                                        $skillOne = $cskill[0];
                                                        $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                                        $form->setContent(TextFormat::BOLD . TextFormat::GOLD . "Replace Skill (Can not get it back for free)");
                                                        $form->addButton(TextFormat::RED . "$skillOne");
                                                        $form->addButton(TextFormat::RED . "$skillTwo");
                                                        $form->addButton(TextFormat::RED . "$skillThree");
                                                        $form->addButton(TextFormat::RED . "Exit");
                                                        $form->sendToPlayer($player);
                                                        return;
                                                    } else {
                                                        $player->sendMessage(TextFormat::RED . "You don't have the skill points to learn");
                                                    }
                                            }
                                        });
                                $user = $player->getName();
                                $system = $this->getSystem($user);
                                $price = $this->config->get("MoltenStar");
                                $MenuShopImage = $this->config->get("MenuShop");
                                $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                $form->setContent(TextFormat::WHITE . "Creates a star of molten lava on the ground from where you are standing when you crouch. (Will not break blocks that are flame proof and won't appear at half blocks)\nBecomes stronger the higher your level.");
                                $form->addButton(TextFormat::GREEN . "Buy: $price SP", 1, $MenuShopImage);
                                $form->addButton(TextFormat::RED . "Back", 1, $MenuShopImage);
                                $form->sendToPlayer($player);
                                return;

                            case 3:
                                $form = new SimpleForm(function (Player $player, $data) {
                                            switch ($data) {
                                                case 0:
                                                    if ($this->getSkillPoints($player->getName()) >= $this->config->get("MoltenCross")) {
                                                        $form = new SimpleForm(function (Player $player, $data) {
                                                                    switch ($data) {
                                                                        case 0:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("MoltenCross");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillOne = "MoltenCross";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 1:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("MoltenCross");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillTwo = "MoltenCross";
                                                                            $skillOne = $cskill[0];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 2:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("MoltenCross");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillThree = "MoltenCross";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillOne = $cskill[0];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 3:
                                                                            return;
                                                                    }
                                                                });
                                                        $user = $player->getName();
                                                        $system = $this->getSystem($user);
                                                        $cskill = $this->playerskills->get($user);
                                                        $skillThree = $cskill[2];
                                                        $skillTwo = $cskill[1];
                                                        $skillOne = $cskill[0];
                                                        $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                                        $form->setContent(TextFormat::BOLD . TextFormat::GOLD . "Replace Skill (Can not get it back for free)");
                                                        $form->addButton(TextFormat::RED . "$skillOne");
                                                        $form->addButton(TextFormat::RED . "$skillTwo");
                                                        $form->addButton(TextFormat::RED . "$skillThree");
                                                        $form->addButton(TextFormat::RED . "Exit");
                                                        $form->sendToPlayer($player);
                                                        return;
                                                    } else {
                                                        $player->sendMessage(TextFormat::RED . "You don't have the skill points to learn");
                                                    }
                                            }
                                        });
                                $user = $player->getName();
                                $system = $this->getSystem($user);
                                $price = $this->config->get("MoltenCross");
                                $MenuShopImage = $this->config->get("MenuShop");
                                $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                $form->setContent(TextFormat::WHITE . "Creates a cross of molten lava on the ground from where you are standing when you crouch. (Will not break blocks that are flame proof and won't appear at half blocks)\nBecomes stronger the higher your level.");
                                $form->addButton(TextFormat::GREEN . "Buy: $price SP", 1, $MenuShopImage);
                                $form->addButton(TextFormat::RED . "Back", 1, $MenuShopImage);
                                $form->sendToPlayer($player);
                                return;

                            case 4:
                                $form = new SimpleForm(function (Player $player, $data) {
                                            switch ($data) {
                                                case 0:
                                                    if ($this->getSkillPoints($player->getName()) >= $this->config->get("ArrowShot")) {
                                                        $form = new SimpleForm(function (Player $player, $data) {
                                                                    switch ($data) {
                                                                        case 0:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("ArrowShot");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillOne = "ArrowShot";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 1:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("ArrowShot");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillTwo = "ArrowShot";
                                                                            $skillOne = $cskill[0];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 2:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("ArrowShot");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillThree = "ArrowShot";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillOne = $cskill[0];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 3:
                                                                            return;
                                                                    }
                                                                });
                                                        $user = $player->getName();
                                                        $system = $this->getSystem($user);
                                                        $cskill = $this->playerskills->get($user);
                                                        $skillThree = $cskill[2];
                                                        $skillTwo = $cskill[1];
                                                        $skillOne = $cskill[0];
                                                        $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                                        $form->setContent(TextFormat::BOLD . TextFormat::GOLD . "Replace Skill (Can not get it back for free)");
                                                        $form->addButton(TextFormat::RED . "$skillOne");
                                                        $form->addButton(TextFormat::RED . "$skillTwo");
                                                        $form->addButton(TextFormat::RED . "$skillThree");
                                                        $form->addButton(TextFormat::RED . "Exit");
                                                        $form->sendToPlayer($player);
                                                        return;
                                                    } else {
                                                        $player->sendMessage(TextFormat::RED . "You don't have the skill points to learn");
                                                    }
                                            }
                                        });
                                $user = $player->getName();
                                $system = $this->getSystem($user);
                                $price = $this->config->get("ArrowShot");
                                $MenuShopImage = $this->config->get("MenuShop");
                                $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                $form->setContent(TextFormat::WHITE . "Shoots arrows in the direction that you are looking.");
                                $form->addButton(TextFormat::GREEN . "Buy: $price SP", 1, $MenuShopImage);
                                $form->addButton(TextFormat::RED . "Back", 1, $MenuShopImage);
                                $form->sendToPlayer($player);
                                return;

                            case 5:
                                $form = new SimpleForm(function (Player $player, $data) {
                                            switch ($data) {
                                                case 0:
                                                    if ($this->getSkillPoints($player->getName()) >= $this->config->get("Summon")) {
                                                        $form = new SimpleForm(function (Player $player, $data) {
                                                                    switch ($data) {
                                                                        case 0:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("Summon");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillOne = "Summon";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 1:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("Summon");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillTwo = "Summon";
                                                                            $skillOne = $cskill[0];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 2:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("Summon");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillThree = "Summon";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillOne = $cskill[0];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 3:
                                                                            return;
                                                                    }
                                                                });
                                                        $user = $player->getName();
                                                        $system = $this->getSystem($user);
                                                        $cskill = $this->playerskills->get($user);
                                                        $skillThree = $cskill[2];
                                                        $skillTwo = $cskill[1];
                                                        $skillOne = $cskill[0];
                                                        $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                                        $form->setContent(TextFormat::BOLD . TextFormat::GOLD . "Replace Skill (Can not get it back for free)");
                                                        $form->addButton(TextFormat::RED . "$skillOne");
                                                        $form->addButton(TextFormat::RED . "$skillTwo");
                                                        $form->addButton(TextFormat::RED . "$skillThree");
                                                        $form->addButton(TextFormat::RED . "Exit");
                                                        $form->sendToPlayer($player);
                                                        return;
                                                    } else {
                                                        $player->sendMessage(TextFormat::RED . "You don't have the skill points to learn");
                                                    }
                                            }
                                        });
                                $user = $player->getName();
                                $system = $this->getSystem($user);
                                $price = $this->config->get("Summon");
                                $MenuShopImage = $this->config->get("MenuShop");
                                $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                $form->setContent(TextFormat::WHITE . "Can summon skeletons, zombies, vindicators and snow golems. (can be used for a quick escape or for offense but the mobs are not loyal)");
                                $form->addButton(TextFormat::GREEN . "Buy: $price SP", 1, $MenuShopImage);
                                $form->addButton(TextFormat::RED . "Back", 1, $MenuShopImage);
                                $form->sendToPlayer($player);
                                return;
                        }
                    });
            $system = $this->getSystem($user);
            $FlameStarImage = $this->config->get("FlameStarImage");
            $FlameCrossImage = $this->config->get("FlameCrossImage");
            $MoltenStarImage = $this->config->get("MoltenStarImage");
            $MoltenCrossImage = $this->config->get("MoltenCrossImage");
            $ArrowShotImage = $this->config->get("ArrowShotImage");
            $SummonImage = $this->config->get("SummonImage");
            $form->setTitle(TextFormat::DARK_PURPLE . $system);
            $form->setContent("");
            $form->addButton("FlameStar", 1, $FlameStarImage);
            $form->addButton("FlameCross", 1, $FlameCrossImage);
            $form->addButton("MoltenStar", 1, $MoltenStarImage);
            $form->addButton("MoltenCross", 1, $MoltenCrossImage);
            $form->addButton("ArrowShot", 1, $ArrowShotImage);
            $form->addButton("Summon", 1, $SummonImage);
            $form->sendToPlayer($p);
            return true;
        } elseif ($system == "SpellBreakerSystem") {
            $form = new SimpleForm(function (Player $player, $data) {
                        switch ($data) {
                            case 0:
                                $form = new SimpleForm(function (Player $player, $data) {
                                            switch ($data) {
                                                case 0:
                                                    if ($this->getSkillPoints($player->getName()) >= $this->config->get("FireBreak")) {
                                                        $form = new SimpleForm(function (Player $player, $data) {
                                                                    switch ($data) {
                                                                        case 0:
                                                                            $user = $player->getName();
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillOne = "FireBreak";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillThree = $cskill[2];
                                                                            $price = $this->config->get("FireBreak");
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 1:
                                                                            $user = $player->getName();
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillTwo = "FireBreak";
                                                                            $skillOne = $cskill[0];
                                                                            $skillThree = $cskill[2];
                                                                            $price = $this->config->get("FireBreak");
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 2:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("FireBreak");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillThree = "FireBreak";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillOne = $cskill[0];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillTwo]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 3:
                                                                            return;
                                                                    }
                                                                });
                                                        $user = $player->getName();
                                                        $system = $this->getSystem($user);
                                                        $cskill = $this->playerskills->get($user);
                                                        $skillThree = $cskill[2];
                                                        $skillTwo = $cskill[1];
                                                        $skillOne = $cskill[0];
                                                        $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                                        $form->setContent(TextFormat::BOLD . TextFormat::GOLD . "Replace Skill (Can not get it back for free)");
                                                        $form->addButton(TextFormat::RED . "$skillOne");
                                                        $form->addButton(TextFormat::RED . "$skillTwo");
                                                        $form->addButton(TextFormat::RED . "$skillThree");
                                                        $form->addButton(TextFormat::RED . "Exit");
                                                        $form->sendToPlayer($player);
                                                        return;
                                                    } else {
                                                        $player->sendMessage(TextFormat::RED . "You don't have the skill points to learn");
                                                    }
                                            }
                                        });
                                $user = $player->getName();
                                $price = $this->config->get("FireBreak");
                                $system = $this->getSystem($user);
                                $MenuShopImage = $this->config->get("MenuShop");
                                $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                $form->setContent(TextFormat::WHITE . "Cancel out spells that set fire to you when you crouch (except molten spells).");
                                $form->addButton(TextFormat::GREEN . "Buy: $price SP", 1, $MenuShopImage);
                                $form->addButton(TextFormat::RED . "Back", 1, $MenuShopImage);
                                $form->sendToPlayer($player);
                                return;

                            case 1:
                                $form = new SimpleForm(function (Player $player, $data) {
                                            switch ($data) {
                                                case 0:
                                                    if ($this->getSkillPoints($player->getName()) >= $this->config->get("AbnormalBreak")) {
                                                        $form = new SimpleForm(function (Player $player, $data) {
                                                                    switch ($data) {
                                                                        case 0:
                                                                            $user = $player->getName();
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillOne = "AbnormalBreak";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillThree = $cskill[2];
                                                                            $price = $this->config->get("AbnormalBreak");
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 1:
                                                                            $user = $player->getName();
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillTwo = "AbnormalBreak";
                                                                            $skillOne = $cskill[0];
                                                                            $skillThree = $cskill[2];
                                                                            $price = $this->config->get("AbnormalBreak");
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 2:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("AbnormalBreak");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillThree = "AbnormalBreak";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillOne = $cskill[0];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillTwo]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 3:
                                                                            return;
                                                                    }
                                                                });
                                                        $user = $player->getName();
                                                        $system = $this->getSystem($user);
                                                        $cskill = $this->playerskills->get($user);
                                                        $skillThree = $cskill[2];
                                                        $skillTwo = $cskill[1];
                                                        $skillOne = $cskill[0];
                                                        $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                                        $form->setContent(TextFormat::BOLD . TextFormat::GOLD . "Replace Skill (Can not get it back for free)");
                                                        $form->addButton(TextFormat::RED . "$skillOne");
                                                        $form->addButton(TextFormat::RED . "$skillTwo");
                                                        $form->addButton(TextFormat::RED . "$skillThree");
                                                        $form->addButton(TextFormat::RED . "Exit");
                                                        $form->sendToPlayer($player);
                                                        return;
                                                    } else {
                                                        $player->sendMessage(TextFormat::RED . "You don't have the skill points to learn");
                                                    }
                                            }
                                        });
                                $user = $player->getName();
                                $price = $this->config->get("AbnormalBreak");
                                $system = $this->getSystem($user);
                                $MenuShopImage = $this->config->get("MenuShop");
                                $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                $form->setContent(TextFormat::WHITE . "Cancel out abnormal effect spells.");
                                $form->addButton(TextFormat::GREEN . "Buy: $price SP", 1, $MenuShopImage);
                                $form->addButton(TextFormat::RED . "Back", 1, $MenuShopImage);
                                $form->sendToPlayer($player);
                                return;

                            case 2:
                                $form = new SimpleForm(function (Player $player, $data) {
                                            switch ($data) {
                                                case 0:
                                                    if ($this->getSkillPoints($player->getName()) >= $this->config->get("MoltenBreak")) {
                                                        $form = new SimpleForm(function (Player $player, $data) {
                                                                    switch ($data) {
                                                                        case 0:
                                                                            $user = $player->getName();
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillOne = "MoltenBreak";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillThree = $cskill[2];
                                                                            $price = $this->config->get("MoltenBreak");
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 1:
                                                                            $user = $player->getName();
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillTwo = "MoltenBreak";
                                                                            $skillOne = $cskill[0];
                                                                            $skillThree = $cskill[2];
                                                                            $price = $this->config->get("MoltenBreak");
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 2:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("MoltenBreak");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillThree = "MoltenBreak";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillOne = $cskill[0];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillTwo]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 3:
                                                                            return;
                                                                    }
                                                                });
                                                        $user = $player->getName();
                                                        $system = $this->getSystem($user);
                                                        $cskill = $this->playerskills->get($user);
                                                        $skillThree = $cskill[2];
                                                        $skillTwo = $cskill[1];
                                                        $skillOne = $cskill[0];
                                                        $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                                        $form->setContent(TextFormat::BOLD . TextFormat::GOLD . "Replace Skill (Can not get it back for free)");
                                                        $form->addButton(TextFormat::RED . "$skillOne");
                                                        $form->addButton(TextFormat::RED . "$skillTwo");
                                                        $form->addButton(TextFormat::RED . "$skillThree");
                                                        $form->addButton(TextFormat::RED . "Exit");
                                                        $form->sendToPlayer($player);
                                                        return;
                                                    } else {
                                                        $player->sendMessage(TextFormat::RED . "You don't have the skill points to learn");
                                                    }
                                            }
                                        });
                                $user = $player->getName();
                                $price = $this->config->get("MoltenBreak");
                                $system = $this->getSystem($user);
                                $MenuShopImage = $this->config->get("MenuShop");
                                $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                $form->setContent(TextFormat::WHITE . "Cancel out Molten spells. (Not in the nether, hell is too powerful)");
                                $form->addButton(TextFormat::GREEN . "Buy: $price SP", 1, $MenuShopImage);
                                $form->addButton(TextFormat::RED . "Back", 1, $MenuShopImage);
                                $form->sendToPlayer($player);
                                return;

                            case 3:
                                $form = new SimpleForm(function (Player $player, $data) {
                                            switch ($data) {
                                                case 0:
                                                    if ($this->getSkillPoints($player->getName()) >= $this->config->get("AntiMage")) {
                                                        $form = new SimpleForm(function (Player $player, $data) {
                                                                    switch ($data) {
                                                                        case 0:
                                                                            $user = $player->getName();
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillOne = "AntiMage";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillThree = $cskill[2];
                                                                            $price = $this->config->get("AntiMage");
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 1:
                                                                            $user = $player->getName();
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillTwo = "AntiMage";
                                                                            $skillOne = $cskill[0];
                                                                            $skillThree = $cskill[2];
                                                                            $price = $this->config->get("AntiMage");
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 2:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("AntiMage");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillThree = "AntiMage";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillOne = $cskill[0];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillTwo]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 3:
                                                                            return;
                                                                    }
                                                                });
                                                        $user = $player->getName();
                                                        $system = $this->getSystem($user);
                                                        $cskill = $this->playerskills->get($user);
                                                        $skillThree = $cskill[2];
                                                        $skillTwo = $cskill[1];
                                                        $skillOne = $cskill[0];
                                                        $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                                        $form->setContent(TextFormat::BOLD . TextFormat::GOLD . "Replace Skill (Can not get it back for free)");
                                                        $form->addButton(TextFormat::RED . "$skillOne");
                                                        $form->addButton(TextFormat::RED . "$skillTwo");
                                                        $form->addButton(TextFormat::RED . "$skillThree");
                                                        $form->addButton(TextFormat::RED . "Exit");
                                                        $form->sendToPlayer($player);
                                                        return;
                                                    } else {
                                                        $player->sendMessage(TextFormat::RED . "You don't have the skill points to learn");
                                                    }
                                            }
                                        });
                                $user = $player->getName();
                                $price = $this->config->get("AntiMage");
                                $system = $this->getSystem($user);
                                $MenuShopImage = $this->config->get("MenuShop");
                                $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                $form->setContent(TextFormat::WHITE . "Cancel out Mage spells. (Not in the nether, hell is too powerful)");
                                $form->addButton(TextFormat::GREEN . "Buy: $price SP", 1, $MenuShopImage);
                                $form->addButton(TextFormat::RED . "Back", 1, $MenuShopImage);
                                $form->sendToPlayer($player);
                                return;

                            case 4:
                                return;
                        }
                    });
            $system = $this->getSystem($user);
            $FireBreakImage = $this->config->get("FireBreakImage");
            $AbnormalBreakImage = $this->config->get("AbnormalBreakImage");
            $MoltenBreakImage = $this->config->get("MoltenBreakImage");
            $AntiMageImage = $this->config->get("AntiMageImage");
            $form->setTitle(TextFormat::DARK_PURPLE . $system);
            $form->setContent("");
            $form->addButton("FireBreak", 1, $FireBreakImage);
            $form->addButton("AbnormalBreak", 1, $AbnormalBreakImage);
            $form->addButton("MoltenBreak", 1, $MoltenBreakImage);
            $form->addButton("AntiMage", 1, $AntiMageImage);
            $form->sendToPlayer($p);
        } elseif ($system == "HealerSystem") {
            $form = new SimpleForm(function (Player $player, $data) {
                        switch ($data) {
                            case 0:
                                $form = new SimpleForm(function (Player $player, $data) {
                                            switch ($data) {
                                                case 0:
                                                    if ($this->getSkillPoints($player->getName()) >= $this->config->get("Heal")) {
                                                        $form = new SimpleForm(function (Player $player, $data) {
                                                                    switch ($data) {
                                                                        case 0:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("Heal");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillOne = "Heal";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 1:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("Heal");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillTwo = "Heal";
                                                                            $skillOne = $cskill[0];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 2:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("Heal");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillThree = "Heal";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillOne = $cskill[0];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 3:
                                                                            return;
                                                                    }
                                                                });
                                                        $user = $player->getName();
                                                        $system = $this->getSystem($user);
                                                        $cskill = $this->playerskills->get($user);
                                                        $skillThree = $cskill[2];
                                                        $skillTwo = $cskill[1];
                                                        $skillOne = $cskill[0];
                                                        $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                                        $form->setContent(TextFormat::BOLD . TextFormat::GOLD . "Replace Skill (Can not get it back for free)");
                                                        $form->addButton(TextFormat::RED . "$skillOne");
                                                        $form->addButton(TextFormat::RED . "$skillTwo");
                                                        $form->addButton(TextFormat::RED . "$skillThree");
                                                        $form->addButton(TextFormat::RED . "Exit");
                                                        $form->sendToPlayer($player);
                                                        return;
                                                    } else {
                                                        $player->sendMessage(TextFormat::RED . "You don't have the skill points to learn");
                                                    }
                                            }
                                        });
                                $user = $player->getName();
                                $price = $this->config->get("Heal");
                                $system = $this->getSystem($user);
                                $MenuShopImage = $this->config->get("MenuShop");
                                $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                $form->setContent(TextFormat::WHITE . "Basic heal spell that adds 1 hp when you crouch.");
                                $form->addButton(TextFormat::GREEN . "Buy: $price SP", 1, $MenuShopImage);
                                $form->addButton(TextFormat::RED . "Back", 1, $MenuShopImage);
                                $form->sendToPlayer($player);
                                return;

                            case 1:
                                $form = new SimpleForm(function (Player $player, $data) {
                                            switch ($data) {
                                                case 0:
                                                    if ($this->getSkillPoints($player->getName()) >= $this->config->get("BigHeal")) {
                                                        $form = new SimpleForm(function (Player $player, $data) {
                                                                    switch ($data) {
                                                                        case 0:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("BigHeal");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillOne = "BigHeal";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 1:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("BigHeal");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillTwo = "BigHeal";
                                                                            $skillOne = $cskill[0];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 2:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("BigHeal");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillThree = "BigHeal";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillOne = $cskill[0];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 3:
                                                                            return;
                                                                    }
                                                                });
                                                        $user = $player->getName();
                                                        $system = $this->getSystem($user);
                                                        $cskill = $this->playerskills->get($user);
                                                        $skillThree = $cskill[2];
                                                        $skillTwo = $cskill[1];
                                                        $skillOne = $cskill[0];
                                                        $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                                        $form->setContent(TextFormat::BOLD . TextFormat::GOLD . "Replace Skill (Can not get it back for free)");
                                                        $form->addButton(TextFormat::RED . "$skillOne");
                                                        $form->addButton(TextFormat::RED . "$skillTwo");
                                                        $form->addButton(TextFormat::RED . "$skillThree");
                                                        $form->addButton(TextFormat::RED . "Exit");
                                                        $form->sendToPlayer($player);
                                                        return;
                                                    } else {
                                                        $player->sendMessage(TextFormat::RED . "You don't have the skill points to learn");
                                                    }
                                            }
                                        });
                                $user = $player->getName();
                                $price = $this->config->get("BigHeal");
                                $system = $this->getSystem($user);
                                $MenuShopImage = $this->config->get("MenuShop");
                                $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                $form->setContent(TextFormat::WHITE . "Average heal spell that adds 4 hp when you crouch.");
                                $form->addButton(TextFormat::GREEN . "Buy: $price SP", 1, $MenuShopImage);
                                $form->addButton(TextFormat::RED . "Back", 1, $MenuShopImage);
                                $form->sendToPlayer($player);
                                return;

                            case 2:
                                $form = new SimpleForm(function (Player $player, $data) {
                                            switch ($data) {
                                                case 0:
                                                    if ($this->getSkillPoints($player->getName()) >= $this->config->get("MaxHeal")) {
                                                        $form = new SimpleForm(function (Player $player, $data) {
                                                                    switch ($data) {
                                                                        case 0:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("MaxHeal");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillOne = "MaxHeal";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 1:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("MaxHeal");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillTwo = "MaxHeal";
                                                                            $skillOne = $cskill[0];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 2:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("MaxHeal");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillThree = "MaxHeal";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillOne = $cskill[0];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 3:
                                                                            return;
                                                                    }
                                                                });
                                                        $user = $player->getName();
                                                        $system = $this->getSystem($user);
                                                        $cskill = $this->playerskills->get($user);
                                                        $skillThree = $cskill[2];
                                                        $skillTwo = $cskill[1];
                                                        $skillOne = $cskill[0];
                                                        $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                                        $form->setContent(TextFormat::BOLD . TextFormat::GOLD . "Replace Skill (Can not get it back for free)");
                                                        $form->addButton(TextFormat::RED . "$skillOne");
                                                        $form->addButton(TextFormat::RED . "$skillTwo");
                                                        $form->addButton(TextFormat::RED . "$skillThree");
                                                        $form->addButton(TextFormat::RED . "Exit");
                                                        $form->sendToPlayer($player);
                                                        return;
                                                    } else {
                                                        $player->sendMessage(TextFormat::RED . "You don't have the skill points to learn");
                                                    }
                                            }
                                        });
                                $user = $player->getName();
                                $price = $this->config->get("MaxHeal");
                                $system = $this->getSystem($user);
                                $MenuShopImage = $this->config->get("MenuShop");
                                $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                $form->setContent(TextFormat::WHITE . "Advanced heal spell that heals to max hp when you crouch.");
                                $form->addButton(TextFormat::GREEN . "Buy: $price SP", 1, $MenuShopImage);
                                $form->addButton(TextFormat::RED . "Back", 1, $MenuShopImage);
                                $form->sendToPlayer($player);
                                return;

                            case 3:
                                $form = new SimpleForm(function (Player $player, $data) {
                                            switch ($data) {
                                                case 0:
                                                    if ($this->getSkillPoints($player->getName()) >= $this->config->get("BlessedHeal")) {
                                                        $form = new SimpleForm(function (Player $player, $data) {
                                                                    switch ($data) {
                                                                        case 0:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("BlessedHeal");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillOne = "BlessedHeal";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 1:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("BlessedHeal");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillTwo = "BlessedHeal";
                                                                            $skillOne = $cskill[0];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 2:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("BlessedHeal");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillThree = "BlessedHeal";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillOne = $cskill[0];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 3:
                                                                            return;
                                                                    }
                                                                });
                                                        $user = $player->getName();
                                                        $system = $this->getSystem($user);
                                                        $cskill = $this->playerskills->get($user);
                                                        $skillThree = $cskill[2];
                                                        $skillTwo = $cskill[1];
                                                        $skillOne = $cskill[0];
                                                        $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                                        $form->setContent(TextFormat::BOLD . TextFormat::GOLD . "Replace Skill (Can not get it back for free)");
                                                        $form->addButton(TextFormat::RED . "$skillOne");
                                                        $form->addButton(TextFormat::RED . "$skillTwo");
                                                        $form->addButton(TextFormat::RED . "$skillThree");
                                                        $form->addButton(TextFormat::RED . "Exit");
                                                        $form->sendToPlayer($player);
                                                        return;
                                                    } else {
                                                        $player->sendMessage(TextFormat::RED . "You don't have the skill points to learn");
                                                    }
                                            }
                                        });
                                $user = $player->getName();
                                $price = $this->config->get("BlessedHeal");
                                $system = $this->getSystem($user);
                                $MenuShopImage = $this->config->get("MenuShop");
                                $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                $form->setContent(TextFormat::WHITE . "God Tier heal spell that adds max hp when you crouch and adds buffs (5 block AOE).");
                                $form->addButton(TextFormat::GREEN . "Buy: $price SP", 1, $MenuShopImage);
                                $form->addButton(TextFormat::RED . "Back", 1, $MenuShopImage);
                                $form->sendToPlayer($player);
                                return;

                            case 4:
                                $form = new SimpleForm(function (Player $player, $data) {
                                            switch ($data) {
                                                case 0:
                                                    if ($this->getSkillPoints($player->getName()) >= $this->config->get("Buff")) {
                                                        $form = new SimpleForm(function (Player $player, $data) {
                                                                    switch ($data) {
                                                                        case 0:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("Buff");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillOne = "Buff";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 1:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("Buff");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillTwo = "Buff";
                                                                            $skillOne = $cskill[0];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 2:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("Buff");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillThree = "Buff";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillOne = $cskill[0];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 3:
                                                                            return;
                                                                    }
                                                                });
                                                        $user = $player->getName();
                                                        $system = $this->getSystem($user);
                                                        $cskill = $this->playerskills->get($user);
                                                        $skillThree = $cskill[2];
                                                        $skillTwo = $cskill[1];
                                                        $skillOne = $cskill[0];
                                                        $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                                        $form->setContent(TextFormat::BOLD . TextFormat::GOLD . "Replace Skill (Can not get it back for free)");
                                                        $form->addButton(TextFormat::RED . "$skillOne");
                                                        $form->addButton(TextFormat::RED . "$skillTwo");
                                                        $form->addButton(TextFormat::RED . "$skillThree");
                                                        $form->addButton(TextFormat::RED . "Exit");
                                                        $form->sendToPlayer($player);
                                                        return;
                                                    } else {
                                                        $player->sendMessage(TextFormat::RED . "You don't have the skill points to learn");
                                                    }
                                            }
                                        });
                                $user = $player->getName();
                                $price = $this->config->get("Buff");
                                $system = $this->getSystem($user);
                                $MenuShopImage = $this->config->get("MenuShop");
                                $SkillImage = $this->config->get("BuufImage");
                                $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                $form->setContent(TextFormat::WHITE . "Support buff spell that activates when you crouch. (AOE, 5 blocks");
                                $form->addButton(TextFormat::GREEN . "Buy: $price SP", 1, $MenuShopImage);
                                $form->addButton(TextFormat::RED . "Back", 1, $MenuShopImage);
                                $form->sendToPlayer($player);
                                return;

                            case 5:
                                $form = new SimpleForm(function (Player $player, $data) {
                                            switch ($data) {
                                                case 0:
                                                    if ($this->getSkillPoints($player->getName()) >= $this->config->get("Debuff")) {
                                                        $form = new SimpleForm(function (Player $player, $data) {
                                                                    switch ($data) {
                                                                        case 0:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("Debuff");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillOne = "Debuff";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 1:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("Debuff");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillTwo = "Debuff";
                                                                            $skillThree = $cskill[2];
                                                                            $skillOne = $cskill[0];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 2:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("Debuff");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillThree = "Debuff";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillOne = $cskill[0];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 3:
                                                                            return;
                                                                    }
                                                                });
                                                        $user = $player->getName();
                                                        $system = $this->getSystem($user);
                                                        $cskill = $this->playerskills->get($user);
                                                        $skillThree = $cskill[2];
                                                        $skillTwo = $cskill[1];
                                                        $skillOne = $cskill[0];
                                                        $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                                        $form->setContent(TextFormat::BOLD . TextFormat::GOLD . "Replace Skill (Can not get it back for free)");
                                                        $form->addButton(TextFormat::RED . "$skillOne");
                                                        $form->addButton(TextFormat::RED . "$skillTwo");
                                                        $form->addButton(TextFormat::RED . "$skillThree");
                                                        $form->addButton(TextFormat::RED . "Exit");
                                                        $form->sendToPlayer($player);
                                                        return;
                                                    } else {
                                                        $player->sendMessage(TextFormat::RED . "You don't have the skill points to learn");
                                                    }
                                            }
                                        });
                                $user = $player->getName();
                                $price = $this->config->get("Debuff");
                                $system = $this->getSystem($user);
                                $MenuShopImage = $this->config->get("MenuShop");
                                $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                $form->setContent(TextFormat::WHITE . "Advanced Debuff spell that activates when you crouch. (AOE, 5 blocks)");
                                $form->addButton(TextFormat::GREEN . "Buy: $price SP", 1, $MenuShopImage);
                                $form->addButton(TextFormat::RED . "Back", 1, $MenuShopImage);
                                $form->sendToPlayer($player);
                                return;
                        }
                    });
            $system = $this->getSystem($user);
            $MenuInfoImage = $this->config->get("MenuInfo");
            $HealImage = $this->config->get("HealImage");
            $BigHealImage = $this->config->get("BigHealImage");
            $MaxHealImage = $this->config->get("MaxHealImage");
            $BlessedHealImage = $this->config->get("BlessedHealImage");
            $BuffImage = $this->config->get("BuffImage");
            $DebuffImage = $this->config->get("DebuffImage");
            $form->setTitle(TextFormat::DARK_PURPLE . $system);
            $form->setContent("");
            $form->addButton("Heal", 1, $HealImage);
            $form->addButton("BigHeal", 1, $BigHealImage);
            $form->addButton("MaxHeal", 1, $MaxHealImage);
            $form->addButton("BlessedHeal", 1, $BlessedHealImage);
            $form->addButton("Buff", 1, $BuffImage);
            $form->addButton("Debuff", 1, $DebuffImage);
            $form->sendToPlayer($p);
        } elseif ($system == "BerserkerSystem") {
            $form = new SimpleForm(function (Player $player, $data) {
                        switch ($data) {
                            case 0:
                                $form = new SimpleForm(function (Player $player, $data) {
                                            switch ($data) {
                                                case 0:
                                                    if ($this->getSkillPoints($player->getName()) >= $this->config->get("DoubleDamage")) {
                                                        $form = new SimpleForm(function (Player $player, $data) {
                                                                    switch ($data) {
                                                                        case 0:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("DoubleDamage");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillOne = "DoubleDamage";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 1:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("DoubleDamage");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillTwo = "DoubleDamage";
                                                                            $skillOne = $cskill[0];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 2:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("DoubleDamage");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillThree = "DoubleDamage";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillOne = $cskill[0];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 3:
                                                                            return;
                                                                    }
                                                                });
                                                        $user = $player->getName();
                                                        $system = $this->getSystem($user);
                                                        $cskill = $this->playerskills->get($user);
                                                        $skillThree = $cskill[2];
                                                        $skillTwo = $cskill[1];
                                                        $skillOne = $cskill[0];
                                                        $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                                        $form->setContent(TextFormat::BOLD . TextFormat::GOLD . "Replace Skill (Can not get it back for free)");
                                                        $form->addButton(TextFormat::RED . "$skillOne");
                                                        $form->addButton(TextFormat::RED . "$skillTwo");
                                                        $form->addButton(TextFormat::RED . "$skillThree");
                                                        $form->addButton(TextFormat::RED . "Exit");
                                                        $form->sendToPlayer($player);
                                                        return;
                                                    } else {
                                                        $player->sendMessage(TextFormat::RED . "You don't have the skill points to learn");
                                                    }
                                            }
                                        });
                                $user = $player->getName();
                                $price = $this->config->get("DoubleDamage");
                                $system = $this->getSystem($user);
                                $MenuShopImage = $this->config->get("MenuShop");
                                $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                $form->setContent(TextFormat::WHITE . "Deal double the damage.");
                                $form->addButton(TextFormat::GREEN . "Buy: $price SP", 1, $MenuShopImage);
                                $form->addButton(TextFormat::RED . "Back", 1, $MenuShopImage);
                                $form->sendToPlayer($player);
                                return;

                            case 1:
                                $form = new SimpleForm(function (Player $player, $data) {
                                            switch ($data) {
                                                case 0:
                                                    if ($this->getSkillPoints($player->getName()) >= $this->config->get("TripleDamage")) {
                                                        $form = new SimpleForm(function (Player $player, $data) {
                                                                    switch ($data) {
                                                                        case 0:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("TripleDamage");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillOne = "TripleDamage";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 1:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("TripleDamage");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillTwo = "TripleDamage";
                                                                            $skillOne = $cskill[0];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 2:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("TripleDamage");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillThree = "TripleDamage";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillOne = $cskill[0];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 3:
                                                                            return;
                                                                    }
                                                                });
                                                        $user = $player->getName();
                                                        $system = $this->getSystem($user);
                                                        $cskill = $this->playerskills->get($user);
                                                        $skillThree = $cskill[2];
                                                        $skillTwo = $cskill[1];
                                                        $skillOne = $cskill[0];
                                                        $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                                        $form->setContent(TextFormat::BOLD . TextFormat::GOLD . "Replace Skill (Can not get it back for free)");
                                                        $form->addButton(TextFormat::RED . "$skillOne");
                                                        $form->addButton(TextFormat::RED . "$skillTwo");
                                                        $form->addButton(TextFormat::RED . "$skillThree");
                                                        $form->addButton(TextFormat::RED . "Exit");
                                                        $form->sendToPlayer($player);
                                                        return;
                                                    } else {
                                                        $player->sendMessage(TextFormat::RED . "You don't have the skill points to learn");
                                                    }
                                            }
                                        });
                                $user = $player->getName();
                                $price = $this->config->get("TripleDamage");
                                $system = $this->getSystem($user);
                                $MenuShopImage = $this->config->get("MenuShop");
                                $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                $form->setContent(TextFormat::WHITE . "Deal triple the damage.");
                                $form->addButton(TextFormat::GREEN . "Buy: $price SP", 1, $MenuShopImage);
                                $form->addButton(TextFormat::RED . "Back", 1, $MenuShopImage);
                                $form->sendToPlayer($player);
                                return;

                            case 2:
                                $form = new SimpleForm(function (Player $player, $data) {
                                            switch ($data) {
                                                case 0:
                                                    if ($this->getSkillPoints($player->getName()) >= $this->config->get("Bloodlust")) {
                                                        $form = new SimpleForm(function (Player $player, $data) {
                                                                    switch ($data) {
                                                                        case 0:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("Bloodlust");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillOne = "Bloodlust";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 1:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("Bloodlust");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillTwo = "Bloodlust";
                                                                            $skillOne = $cskill[0];
                                                                            $skillThree = $cskill[2];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 2:
                                                                            $user = $player->getName();
                                                                            $price = $this->config->get("Bloodlust");
                                                                            $cskill = $this->playerskills->get($user);
                                                                            $skillThree = "Bloodlust";
                                                                            $skillTwo = $cskill[1];
                                                                            $skillOne = $cskill[0];
                                                                            $this->playerskills->remove($user);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->playerskills->set($user, [$skillOne, $skillTwo, $skillThree]);
                                                                            $this->playerskills->save();
                                                                            $this->playerskills->reload();
                                                                            $this->minusSkillPoints($user, $price);
                                                                            return;
                                                                        case 3:
                                                                            return;
                                                                    }
                                                                });
                                                        $user = $player->getName();
                                                        $system = $this->getSystem($user);
                                                        $cskill = $this->playerskills->get($user);
                                                        $skillThree = $cskill[2];
                                                        $skillTwo = $cskill[1];
                                                        $skillOne = $cskill[0];
                                                        $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                                        $form->setContent(TextFormat::BOLD . TextFormat::GOLD . "Replace Skill (Can not get it back for free)");
                                                        $form->addButton(TextFormat::RED . "$skillOne");
                                                        $form->addButton(TextFormat::RED . "$skillTwo");
                                                        $form->addButton(TextFormat::RED . "$skillThree");
                                                        $form->addButton(TextFormat::RED . "Exit");
                                                        $form->sendToPlayer($player);
                                                        return;
                                                    } else {
                                                        $player->sendMessage(TextFormat::RED . "You don't have the skill points to learn");
                                                    }
                                            }
                                        });
                                $user = $player->getName();
                                $price = $this->config->get("Bloodlust");
                                $system = $this->getSystem($user);
                                $MenuShopImage = $this->config->get("MenuShop");
                                $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                $form->setContent(TextFormat::WHITE . "Deal 1 heart of damage no matter the armour, gain resistance 2, Deal double damage.");
                                $form->addButton(TextFormat::GREEN . "Buy: $price SP", 1, $MenuShopImage);
                                $form->addButton(TextFormat::RED . "Back", 1, $MenuShopImage);
                                $form->sendToPlayer($player);
                                return;

                            case 3:
                                return;
                        }
                    });
            $system = $this->getSystem($user);
            $DoubleDamageImage = $this->config->get("DoubleDamageImage");
            $TripleDamageImage = $this->config->get("TripleDamageImage");
            $BloodlustImage = $this->config->get("BloodlustImage");
            $form->setTitle(TextFormat::DARK_PURPLE . $system);
            $form->setContent("");
            $form->addButton("DoubleDamage", 1, $DoubleDamageImage);
            $form->addButton("TripleDamage", 1, $TripleDamageImage);
            $form->addButton("Bloodlust", 1, $BloodlustImage);
            $form->sendToPlayer($p);
        } else {
            $p->sendMessage(TextFormat::RED . "System not Supported");
            return false;
        }
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
                    $MenuShopImage = $this->config->get("MenuShop");
                    $SummonImage = $this->config->get("SummonImage");
                    $world = $sender->getLevel()->getFolderName();
                    if ($world == $this->config->get("World")) {
                        if ($system == "MageSystem") {
                            $form = new SimpleForm(function (Player $player, $data) {
                                        switch ($data) {
                                            case 0:
                                                $form = new CustomForm(function (Player $player, $data) {
                                                            switch ($data) {
                                                                case 0:
                                                                    return;
                                                            }
                                                        });
                                                $user = $player->getName();
                                                $system = $this->getSystem($user);
                                                $lvl = $this->getLvl($user);
                                                $activeskill = $this->getSkill($user);
                                                $skillpoints = $this->getSkillPoints($user);
                                                $sxp = $this->getSxp($user);
                                                $MenuInfoImage = $this->config->get("MenuInfo");
                                                $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                                $form->addLabel(TextFormat::GREEN . "System User: $user");
                                                $form->addLabel(TextFormat::GREEN . "Level: $lvl");
                                                $form->addLabel(TextFormat::GREEN . "XP: $sxp");
                                                $form->addLabel(TextFormat::GREEN . "Skill Points: $skillpoints");
                                                $form->addLabel(TextFormat::GREEN . "Active Skill: $activeskill");
                                                $skillList = $this->playerskills->get($user);
                                                $form->sendToPlayer($player);
                                                return;
                                            case 1:
                                                $form = new SimpleForm(function (Player $player, $data) {
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
                                            case 3:
                                                $user = $player->getName();
                                                $this->skillShopForm($player, $user);
                                                return;
                                            case 4:
                                                $activeskill = $this->getSkill($player->getName());
                                                if ($activeskill == "Summon") {
                                                    $form = new CustomForm(function (Player $player, $data) {
                                                                if (!$data == null) {
                                                                    $monster = $data[0];
                                                                    if ($monster == "sombie" || $monster == "snow_solem" || $monster == "skeleton" || $monster == "sindicator") {
                                                                        $className = PureEntities::getInstance()->getRegisteredClassNameFromShortName($monster);
                                                                        $level = $player->getLevel();
                                                                        $pos = new Position((float) $player->getX() + 5, (float) $player->getY(), (float) $player->getZ() + 5, $level);
                                                                        PureEntities::getInstance()->scheduleCreatureSpawn($pos, $className::NETWORK_ID, $level, "");
                                                                        return;
                                                                    } else {
                                                                        return;
                                                                    }
                                                                } else {
                                                                    return;
                                                                }
                                                            });
                                                    $MenuInfoImage = $this->config->get("MenuInfo");
                                                    $form->setTitle(TextFormat::GOLD . TextFormat::BOLD . "Summon");
                                                    $form->addInput(TextFormat::AQUA . "Mob:", "skeleton");
                                                    $form->sendToPlayer($player);
                                                    return;
                                                } else {
                                                    $player->sendMessage("Skill not activated");
                                                    return;
                                                }
                                            case 5:
                                                return;
                                        }
                                    });
                            $form->setTitle($system);
                            $form->setContent(TextFormat::DARK_PURPLE . "Welcome, user of the $system, this is your menu!");
                            $form->addButton("Info", 1, "$MenuInfoImage");
                            $form->addButton("Skills", 1, "$MenuSkillsImage");
                            $form->addButton("Select Skill", 1, "$MenuSkillsImage");
                            $form->addButton("Skill Store", 1, "$MenuShopImage");
                            $form->addButton("Summon", 1, "$SummonImage");
                            $form->addButton(TextFormat::RED . "Exit");
                            $form->sendToPlayer($sender);
                            return true;
                        } elseif ($system == "HealerSystem") {
                            $form = new SimpleForm(function (Player $player, $data) {
                                        switch ($data) {
                                            case 0:
                                                $form = new CustomForm(function (Player $player, $data) {
                                                            switch ($data) {
                                                                case 0:
                                                                    return;
                                                            }
                                                        });
                                                $user = $player->getName();
                                                $system = $this->getSystem($user);
                                                $lvl = $this->getLvl($user);
                                                $activeskill = $this->getSkill($user);
                                                $sp = $this->getSkillPoints($user);
                                                $sxp = $this->getSxp($user);
                                                $MenuInfoImage = $this->config->get("MenuInfo");
                                                $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                                $form->addLabel(TextFormat::GREEN . "System User: $user");
                                                $form->addLabel(TextFormat::GREEN . "Level: $lvl");
                                                $form->addLabel(TextFormat::GREEN . "XP: $sxp");
                                                $form->addLabel(TextFormat::GREEN . "Skill Points: $sp");
                                                $form->addLabel(TextFormat::GREEN . "Active Skill: $activeskill");
                                                $skillList = $this->playerskills->get($user);
                                                $form->sendToPlayer($player);
                                                return;
                                            case 1:
                                                $form = new SimpleForm(function (Player $player, $data) {
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
                                            case 3:
                                                $user = $player->getName();
                                                $this->skillShopForm($player, $user);
                                                return;
                                            case 4:
                                                return;
                                        }
                                    });
                            $form->setTitle($system);
                            $form->setContent(TextFormat::DARK_PURPLE . "Welcome, user of the $system, this is your menu!");
                            $form->addButton("Info", 1, "$MenuInfoImage");
                            $form->addButton("Skills", 1, "$MenuSkillsImage");
                            $form->addButton("Select Skill", 1, "$MenuSkillsImage");
                            $form->addButton("Skill Store", 1, "$MenuShopImage");
                            $form->addButton(TextFormat::RED . "Exit");
                            $form->sendToPlayer($sender);
                        } elseif ($system == "SpellBreakerSystem") {
                            $form = new SimpleForm(function (Player $player, $data) {
                                        switch ($data) {
                                            case 0:
                                                $form = new CustomForm(function (Player $player, $data) {
                                                            switch ($data) {
                                                                case 0:
                                                                    return;
                                                            }
                                                        });
                                                $user = $player->getName();
                                                $system = $this->getSystem($user);
                                                $lvl = $this->getLvl($user);
                                                $activeskill = $this->getSkill($user);
                                                $sp = $this->getSkillPoints($user);
                                                $sxp = $this->getSxp($user);
                                                $MenuInfoImage = $this->config->get("MenuInfo");
                                                $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                                $form->addLabel(TextFormat::GREEN . "System User: $user");
                                                $form->addLabel(TextFormat::GREEN . "Level: $lvl");
                                                $form->addLabel(TextFormat::GREEN . "XP: $sxp");
                                                $form->addLabel(TextFormat::GREEN . "Skill Points: $sp");
                                                $form->addLabel(TextFormat::GREEN . "Active Skill: $activeskill");
                                                $skillList = $this->playerskills->get($user);
                                                $form->sendToPlayer($player);
                                                return;
                                            case 1:
                                                $form = new SimpleForm(function (Player $player, $data) {
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
                                            case 3:
                                                $user = $player->getName();
                                                $this->skillShopForm($player, $user);
                                                return;
                                            case 4:
                                                return;
                                        }
                                    });
                            $form->setTitle($system);
                            $form->setContent(TextFormat::DARK_PURPLE . "Welcome, user of the $system, this is your menu!");
                            $form->addButton("Info", 1, "$MenuInfoImage");
                            $form->addButton("Skills", 1, "$MenuSkillsImage");
                            $form->addButton("Select Skill", 1, "$MenuSkillsImage");
                            $form->addButton("Skill Store", 1, "$MenuShopImage");
                            $form->addButton(TextFormat::RED . "Exit");
                            $form->sendToPlayer($sender);
                        } elseif ($system == "BerserkerSystem") {
                            $form = new SimpleForm(function (Player $player, $data) {
                                        switch ($data) {
                                            case 0:
                                                $form = new CustomForm(function (Player $player, $data) {
                                                            switch ($data) {
                                                                case 0:
                                                                    return;
                                                            }
                                                        });
                                                $user = $player->getName();
                                                $system = $this->getSystem($user);
                                                $lvl = $this->getLvl($user);
                                                $activeskill = $this->getSkill($user);
                                                $sp = $this->getSkillPoints($user);
                                                $sxp = $this->getSxp($user);
                                                $MenuInfoImage = $this->config->get("MenuInfo");
                                                $form->setTitle(TextFormat::DARK_PURPLE . $system);
                                                $form->addLabel(TextFormat::GREEN . "System User: $user");
                                                $form->addLabel(TextFormat::GREEN . "Level: $lvl");
                                                $form->addLabel(TextFormat::GREEN . "XP: $sxp");
                                                $form->addLabel(TextFormat::GREEN . "Skill Points: $sp");
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
                                            case 3:
                                                $user = $player->getName();
                                                $this->skillShopForm($player, $user);
                                                return;
                                            case 4:
                                                return;
                                        }
                                    });
                            $form->setTitle($system);
                            $form->setContent(TextFormat::DARK_PURPLE . "Welcome, user of the $system, this is your menu!");
                            $form->addButton("Info", 1, "$MenuInfoImage");
                            $form->addButton("Skills", 1, "$MenuSkillsImage");
                            $form->addButton("Select Skill", 1, "$MenuSkillsImage");
                            $form->addButton("Skill Store", 1, "$MenuShopImage");
                            $form->addButton("Exit");
                            $form->sendToPlayer($sender);
                        } else {
                            $sender->sendMessage(TextFormat::RED . "System Invalid, please talk to a System Lord");
                        }
                    } else {
                        $sender->sendMessage(TextFormat::RED . "You were not blessed by the system in this world");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "Must be in-game");
                }
            } else {
                $sender->sendMessage(TextFormat::RED . "You were locked out of the system (csystem.use)");
                return false;
            }
        }

        if (strtolower($command->getName()) == "systemadmin") {
            if ($sender->hasPermission("csystem.admin")) {
                if ($sender instanceof Player) {
                    $user = $sender->getName();
                    $lvl = $this->getLvl($user);
                    $sxp = $this->getSxp($user);
                    $system = $this->getSystem($user);
                    $MenuInfoImage = $this->config->get("MenuInfo");
                    $MenuSkillsImage = $this->config->get("MenuSkills");
                    $MenuShopImage = $this->config->get("MenuShop");
                    $world = $sender->getLevel()->getFolderName();
                    if ($world == $this->config->get("World")) {
                        $form = new SimpleForm(function (Player $player, $data) {
                                    switch ($data) {
                                        case 0:
                                            $form = new CustomForm(function (Player $player, $data) {
                                                        if (!$data == null) {
                                                            $name = $data[0];
                                                            $form = new CustomForm(function (Player $player, $data) use ($name) {
                                                                        if (!$data == null) {
                                                                            $amount = $data[0];
                                                                            $this->addSXP($name, $amount);
                                                                        } else {
                                                                            return;
                                                                        }
                                                                    });
                                                            $MenuInfoImage = $this->config->get("MenuInfo");
                                                            $form->setTitle(TextFormat::DARK_PURPLE . TextFormat::BOLD . "Admin");
                                                            $form->addInput(TextFormat::AQUA . "Amount:", 10);
                                                            $form->sendToPlayer($player);
                                                            return;
                                                        } else {
                                                            return;
                                                        }
                                                    });
                                            $MenuInfoImage = $this->config->get("MenuInfo");
                                            $form->setTitle(TextFormat::DARK_PURPLE . TextFormat::BOLD . "Admin");
                                            $form->addInput(TextFormat::AQUA . "Name:", "MrDevCat");
                                            $form->sendToPlayer($player);
                                            return;
                                        case 1:
                                            $form = new CustomForm(function (Player $player, $data) {
                                                        if (!$data == null) {
                                                            $name = $data[0];
                                                            $form = new CustomForm(function (Player $player, $data) use ($name) {
                                                                        if (!$data == null) {
                                                                            $amount = $data[0];
                                                                            $this->lvlUp($name, $amount);
                                                                        } else {
                                                                            return;
                                                                        }
                                                                    });
                                                            $MenuInfoImage = $this->config->get("MenuInfo");
                                                            $form->setTitle(TextFormat::DARK_PURPLE . TextFormat::BOLD . "Admin");
                                                            $form->addInput(TextFormat::AQUA . "Amount:", 10);
                                                            $form->sendToPlayer($player);
                                                            return;
                                                        } else {
                                                            return;
                                                        }
                                                    });
                                            $MenuInfoImage = $this->config->get("MenuInfo");
                                            $form->setTitle(TextFormat::DARK_PURPLE . TextFormat::BOLD . "Admin");
                                            $form->addInput(TextFormat::AQUA . "Name:", "MrDevCat");
                                            $form->sendToPlayer($player);
                                            return;
                                        case 2:
                                            $form = new CustomForm(function (Player $player, $data) {
                                                        if (!$data == null) {
                                                            $name = $data[0];
                                                            $form = new CustomForm(function (Player $player, $data) use ($name) {
                                                                        if (!$data == null) {
                                                                            $amount = $data[0];
                                                                            $this->addSkillPoints($name, $amount);
                                                                        } else {
                                                                            return;
                                                                        }
                                                                    });
                                                            $MenuInfoImage = $this->config->get("MenuInfo");
                                                            $form->setTitle(TextFormat::DARK_PURPLE . TextFormat::BOLD . "Admin");
                                                            $form->addInput(TextFormat::AQUA . "Amount:", 10);
                                                            $form->sendToPlayer($player);
                                                            return;
                                                        } else {
                                                            return;
                                                        }
                                                    });
                                            $MenuInfoImage = $this->config->get("MenuInfo");
                                            $form->setTitle(TextFormat::DARK_PURPLE . TextFormat::BOLD . "Admin");
                                            $form->addInput(TextFormat::AQUA . "Name:", "MrDevCat");
                                            $form->sendToPlayer($player);
                                            return;
                                        case 3:
                                            $form = new SimpleForm(function (Player $player, $data) {
                                                        switch ($data) {
                                                            case 0:
                                                                $form = new CustomForm(function (Player $player, $data) {
                                                                            if (!$data == null) {
                                                                                $name = $data[0];
                                                                                $form = new CustomForm(function (Player $player, $data) use ($name) {
                                                                                            if (!$data == null) {
                                                                                                $system = $data[0];
                                                                                                $this->setSystem($name, $system);
                                                                                            } else {
                                                                                                return;
                                                                                            }
                                                                                        });
                                                                                $MenuInfoImage = $this->config->get("MenuInfo");
                                                                                $form->setTitle(TextFormat::DARK_PURPLE . TextFormat::BOLD . "Admin");
                                                                                $form->addInput(TextFormat::AQUA . "System:", "BerserkerSystem");
                                                                                $form->sendToPlayer($player);
                                                                                return;
                                                                            } else {
                                                                                return;
                                                                            }
                                                                        });
                                                                $MenuInfoImage = $this->config->get("MenuInfo");
                                                                $form->setTitle(TextFormat::DARK_PURPLE . TextFormat::BOLD . "Admin");
                                                                $form->addInput(TextFormat::AQUA . "Name:", "MrDevCat");
                                                                $form->sendToPlayer($player);
                                                                return;
                                                            case 1:
                                                                return;
                                                        }
                                                    });
                                            $form->setTitle(TextFormat::DARK_PURPLE . TextFormat::BOLD . "Admin");
                                            $form->setContent(TextFormat::BOLD . TextFormat::DARK_AQUA . "Warning, everything but saved skills will be lost and the selected skill will be reset.");
                                            $form->addButton(TextFormat::GREEN . "Continue");
                                            $form->addButton(TextFormat::RED . "Exit");
                                            $form->sendToPlayer($player);
                                            return;
                                        case 4:
                                            return;
                                    }
                                });
                        $system = $this->getSystem($user);
                        $MenuInfoImage = $this->config->get("MenuInfo");
                        $form->setTitle(TextFormat::DARK_PURPLE . TextFormat::BOLD . "Admin");
                        $form->setContent(TextFormat::DARK_PURPLE . "");
                        $form->addButton(TextFormat::GOLD . "Add SXP");
                        $form->addButton(TextFormat::GOLD . "Add level");
                        $form->addButton(TextFormat::GOLD . "Add Skill points");
                        $form->addButton(TextFormat::GOLD . "Set system");
                        $form->addButton(TextFormat::RED . "Back");
                        $form->sendToPlayer($sender);
                        return true;
                    } else {
                        $sender->sendMessage(TextFormat::RED . "You were not blessed by the system in this world");
                    }
                } else {
                    $sender->sendMessage(TextFormat::RED . "Must be in-game");
                }
            } else {
                $sender->sendMessage(TextFormat::RED . "System does not recognize master, shutting down system");
                return false;
            }
        }
        return false;
    }

}
