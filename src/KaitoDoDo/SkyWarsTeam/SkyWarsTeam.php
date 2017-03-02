<?php
# plugin hecho por KaitoDoDo
namespace KaitoDoDo\SkyWarsTeam;

use pocketmine\plugin\PluginBase;
use pocketmine\scheduler\PluginTask;
use pocketmine\event\Listener;

use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;
use pocketmine\math\Vector3;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\tile\Sign;
use pocketmine\level\Level;
use pocketmine\item\Item;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\entity\Effect;
use pocketmine\tile\Chest;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\inventory\ChestInventory;
use onebone\economyapi\EconomyAPI;

class SkyWarsTeam extends PluginBase implements Listener {

    public $prefix = TextFormat::GRAY . "[" . TextFormat::AQUA . TextFormat::BOLD . "Sky" . TextFormat::GREEN . "Wars§6Team" . TextFormat::RESET . TextFormat::GRAY . "]";
	public $mode = 0;
	public $arenas = array();
	public $currentLevel = "";
	
	public function onEnable()
	{
		  $this->getLogger()->info(TextFormat::AQUA . "Sky§aWars§6Team §bby KaitoDoDo");

                $this->getServer()->getPluginManager()->registerEvents($this ,$this);
                $this->economy = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
                if(!empty($this->economy))
                {
                $this->api = EconomyAPI::getInstance ();
                }
		@mkdir($this->getDataFolder());
                $config2 = new Config($this->getDataFolder() . "/rank.yml", Config::YAML);
		$config2->save();
		$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
		if($config->get("arenas")!=null)
		{
			$this->arenas = $config->get("arenas");
		}
                foreach($this->arenas as $lev)
		{
			$this->getServer()->loadLevel($lev);
		}
		$items = array(array(1,0,30),array(1,0,20),array(3,0,15),array(3,0,25),array(4,0,35),array(4,0,15),array(260,0,5),array(261,0,1),array(262,0,6),array(267,0,1),array(268,0,1),array(272,0,1),array(276,0,1),array(283,0,1),array(297,0,3),array(298,0,1),array(299,0,1),array(300,0,1),array(301,0,1),array(303,0,1),array(304,0,1),array(310,0,1),array(313,0,1),array(314,0,1),array(315,0,1),array(316,0,1),array(317,0,1),array(320,0,4),array(354,0,1),array(364,0,4),array(366,0,5),array(391,0,5));
		if($config->get("chestitems")==null)
		{
			$config->set("chestitems",$items);
		}
		$config->save();
		
		$playerlang = new Config($this->getDataFolder() . "/languages.yml", Config::YAML);
		$playerlang->save();
		
		$lang = new Config($this->getDataFolder() . "/lang.yml", Config::YAML);
		if($lang->get("en")==null)
		{
			$messages = array();
			$messages["kill"] = "was killed by";
			$messages["cannotjoin"] = "The game already started.";
			$messages["seconds"] = "§bseconds to start";
			$messages["won"] = "Won SkyWars!";
			$messages["deathmatchminutes"] = "minutes to DeathMatch!";
			$messages["deathmatchseconds"] = "seconds to DeathMatch!";
			$messages["chestrefill"] = "The chest has been refilled!";
			$messages["remainingminutes"] = "remaining minutes!";
			$messages["remainingseconds"] = "remaining seconds!";
			$messages["nowinner"] = "No winner this time!";
			$messages["moreplayers"] = "Need more players!";
			$lang->set("en",$messages);
		}
		$lang->save();
		
		$statistic = new Config($this->getDataFolder() . "/statistic.yml", Config::YAML);
		$statistic->save();
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new GameSender($this), 20);
		$this->getServer()->getScheduler()->scheduleRepeatingTask(new RefreshSigns($this), 10);
        }
	
	public function onDeath(PlayerDeathEvent $event){
        $jugador = $event->getEntity();
        $name = $jugador->getName();
        $jugador->setNameTag($name);
        $statistic = new Config($this->getDataFolder() . "/statistic.yml", Config::YAML);
	$stats = $statistic->get($jugador->getName());
	$soFarPlayer = $stats[1];
	$soFarPlayer++;
	$stats[1] = $soFarPlayer;
	$statistic->set($jugador->getName(),$stats);
	$statistic->save();
        $level = $jugador->getLevel();
        $cause = $jugador->getLastDamageCause();
        if(in_array($level,$this->arenas))
		{
			$event->setDeathMessage("");
        if(!($cause instanceof EntityDamageByEntityEvent)) return;
        $asassin = ($cause->getDamager() instanceof Player ? $cause->getDamager() : false);
		if($asassin !== false) {
			foreach($level->getPlayers() as $pl)
			{
				$playerlang = new Config($this->getDataFolder() . "/languages.yml", Config::YAML);
				$lang = new Config($this->getDataFolder() . "/lang.yml", Config::YAML);
				$toUse = $lang->get($playerlang->get($pl->getName()));
                                $muerto = $jugador->getNameTag();
                                $asesino = $asassin->getNameTag();
				$pl->sendMessage(TextFormat::RED . $muerto . TextFormat::YELLOW . " " . $toUse["kill"] . " " . TextFormat::GREEN . $asesino . TextFormat::YELLOW . ".");
			}
			$statistic = new Config($this->getDataFolder() . "/statistic.yml", Config::YAML);
			$stats = $statistic->get($asassin->getName());
			$soFarPlayer = $stats[0];
			$soFarPlayer++;
			$stats[0] = $soFarPlayer;
			$statistic->set($asassin->getName(),$stats);
			$statistic->save();
                }
                }
	}
	
	public function onChangeLevel(EntityLevelChangeEvent $event)
        {
            $pl = $event->getEntity();
            if($pl instanceof Player)
            {
                $lev = $event->getOrigin();
                if($lev instanceof Level && in_array($lev->getFolderName(),$this->arenas))
		{
                    $pl->setGamemode(0);
                    $level = $lev->getFolderName();
                    $pl->extinguish();
                    $pl->removeAllEffects();
                    $pl->getInventory()->clearAll();
                    $pl->setNameTag($pl->getName());
                }
            }
        }
        
        public function onMove(PlayerMoveEvent $event)
	{
		$player = $event->getPlayer();
		$level = $player->getLevel()->getFolderName();
		if(in_array($level,$this->arenas))
		{
			$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
			$sofar = $config->get($level . "StartTime");
			if($sofar > 0)
			{
				$to = clone $event->getFrom();
				$to->yaw = $event->getTo()->yaw;
				$to->pitch = $event->getTo()->pitch;
				$event->setTo($to);
			}
		}
	}
	
	public function onLogin(PlayerLoginEvent $event)
	{
		$player = $event->getPlayer();
		$playerlang = new Config($this->getDataFolder() . "/languages.yml", Config::YAML);
		if($playerlang->get($player->getName())==null)
		{
			$playerlang->set($player->getName(),"en");
			$playerlang->save();
		}
		$statistic = new Config($this->getDataFolder() . "/statistic.yml", Config::YAML);
		if($statistic->get($player->getName())==null)
		{
			$statistic->set($player->getName(),array(0,0));
			$statistic->save();
		}
		$player->getInventory()->clearAll();
		$spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
		$this->getServer()->getDefaultLevel()->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
		$player->teleport($spawn,0,0);
	}
	
	public function onBlockBreak(BlockBreakEvent $event)
	{
		$player = $event->getPlayer();
		$level = $player->getLevel()->getFolderName();
		if(in_array($level,$this->arenas))
		{
			$event->setCancelled(false);
		}
	}
	
	
	
	public function onBlockPlace(BlockPlaceEvent $event)
	{
		$player = $event->getPlayer();
		$level = $player->getLevel()->getFolderName();
		if(in_array($level,$this->arenas))
		{
			$event->setCancelled(false);
		}
	}
	
	public function onDamage(EntityDamageEvent $event)
	{
		if($event instanceof EntityDamageByEntityEvent)
		{
			$player = $event->getEntity();
			$damager = $event->getDamager();
			if($player instanceof Player)
			{
				if($damager instanceof Player)
				{
					$level = $player->getLevel()->getFolderName();
					$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
					if($config->get($level . "PlayTime") != null)
					{
						if($config->get($level . "PlayTime") > 750)
						{
							$event->setCancelled(true);
						}
					}
				}
			}
		}
	}
        
        public function onEntityDamage(EntityDamageEvent $event){
            if ($event instanceof EntityDamageByEntityEvent) {
                if ($event->getEntity() instanceof Player && $event->getDamager() instanceof Player) {
            $golpeado = $event->getEntity()->getNameTag();
            $golpeador = $event->getDamager()->getNameTag();
            if ((strpos($golpeado, "§l§c[RED]") !== false) && (strpos($golpeador, "§l§c[RED]") !== false)) { 
                    
                $event->setCancelled();
                }
                else if ((strpos($golpeado, "§l§9[BLUE]") !== false) && (strpos($golpeador, "§l§9[BLUE]") !== false)) { 
                    
                $event->setCancelled();
                }
                else if ((strpos($golpeado, "§l§a[GREEN]") !== false) && (strpos($golpeador, "§l§a[GREEN]") !== false)) { 
                    
                $event->setCancelled();
                }
                }
            }
        }
	
	public function onCommand(CommandSender $player, Command $cmd, $label, array $args) {
		$lang = new Config($this->getDataFolder() . "/lang.yml", Config::YAML);
        switch($cmd->getName()){
			case "stm":
				if($player->isOp())
				{
					if(!empty($args[0]))
					{
						if($args[0]=="make")
						{
							if(!empty($args[1]))
							{
								if(file_exists($this->getServer()->getDataPath() . "/worlds/" . $args[1]))
								{
									$this->getServer()->loadLevel($args[1]);
									$this->getServer()->getLevelByName($args[1])->loadChunk($this->getServer()->getLevelByName($args[1])->getSafeSpawn()->getFloorX(), $this->getServer()->getLevelByName($args[1])->getSafeSpawn()->getFloorZ());
									array_push($this->arenas,$args[1]);
									$this->currentLevel = $args[1];
									$this->mode = 1;
									$player->sendMessage($this->prefix . "Touch the player spawn!");
									$player->setGamemode(1);
									$player->teleport($this->getServer()->getLevelByName($args[1])->getSafeSpawn(),0,0);
                                                                        $name = $args[1];
                                                                        $this->zip($player, $name);
								}
								else
								{
									$player->sendMessage($this->prefix . "ERROR missing world.");
								}
							}
							else
							{
								$player->sendMessage($this->prefix . "ERROR missing parameters.");
							}
						}
						else if($args[0]=="leave")
						{
							$spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
							$this->getServer()->getDefaultLevel()->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
							$player->teleport($spawn,0,0);
						}
						else
						{
							$player->sendMessage($this->prefix . "Invalid Command.");
						}
					}
					else
					{
						$player->sendMessage($this->prefix . "/stm <make-leave> : Create Arena | Leave the game");
                                                $player->sendMessage($this->prefix . "/rankstm <Rank> <Player> : Set Rank(Ranks: Warrior, Warrior+, Archer, Pyromancer)");
                                                $player->sendMessage($this->prefix . "/stmstart : Start the game in 10 seconds");
					}
				}
				else
				{
					if(!empty($args[0]))
					{
						if($args[0]=="leave")
						{
							$spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
							$this->getServer()->getDefaultLevel()->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
							$player->teleport($spawn,0,0);
						}
					}
				}
			return true;
                        
                        case "rankstm":
				if($player->isOp())
				{
				if(!empty($args[0]))
				{
					if(!empty($args[1]))
					{
					$rank = "";
					if($args[0]=="Warrior+")
					{
						$rank = "§b[§aWarrior§4+§b]";
					}
					else if($args[0]=="Archer")
					{
						$rank = "§b[§cArcher§b]";
					}
					else if($args[0]=="Pyromancer")
					{
						$rank = "§b[§6Pyromancer§b]";
					}
					else
					{
						$rank = "§b[§a" . $args[0] . "§b]";
					}
					$config = new Config($this->getDataFolder() . "/rank.yml", Config::YAML);
					$config->set($args[1],$rank);
					$config->save();
					$player->sendMessage($args[1] . " got rank: " . $rank);
					}
					else
					{
						$player->sendMessage("Missing parameter(s)");
					}
				}
				else
				{
					$player->sendMessage("Missing parameter(s)");
				}
				}
			return true;
			
			case "lang":
				if(!empty($args[0]))
				{
					if($lang->get($args[0])!=null)
					{
						$playerlang = new Config($this->getDataFolder() . "/languages.yml", Config::YAML);
						$playerlang->set($player->getName(),$args[0]);
						$playerlang->save();
						$player->sendMessage(TextFormat::GREEN . "Lenguaje: " . $args[0]);
					}
					else
					{
						$player->sendMessage(TextFormat::RED . "Lenguaje no encontrado!");
					}
				}
			return true;
                        
                        case "stmstart":
                            if($player->isOp())
				{
                                $player->sendMessage("§aForce start in 10 seconds...");
                                $config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
                                $config->set("arenas",$this->arenas);
		foreach($this->arenas as $arena)
		{
			$config->set($arena . "PlayTime", 780);
			$config->set($arena . "StartTime", 10);
		}
		$config->save();
                                }
                                return true;
		}
	}
        
        	public function onChat(PlayerChatEvent $event)
	{
		$player = $event->getPlayer();
		$message = $event->getMessage();
		$config = new Config($this->getDataFolder() . "/rank.yml", Config::YAML);
		$rank = "";
		if($config->get($player->getName()) != null)
		{
			$rank = $config->get($player->getName());
		}
		$event->setFormat($rank . TextFormat::WHITE . $player->getName() . " §d:§f " . $message);
	}
	
	public function onInteract(PlayerInteractEvent $event)
	{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$tile = $player->getLevel()->getTile($block);
		
		if($tile instanceof Sign) 
		{
			if($this->mode==26)
			{
				$tile->setText(TextFormat::AQUA . "[Join]",TextFormat::YELLOW  . "0 / 12","§a" . $this->currentLevel,$this->prefix);
				$this->refreshArenas();
				$this->currentLevel = "";
				$this->mode = 0;
				$player->sendMessage($this->prefix . "Arena Registered!");
			}
			else
			{
				$text = $tile->getText();
				if($text[3] == $this->prefix)
				{
					if($text[0]==TextFormat::AQUA . "[Join]")
					{
						$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
                                                $namemap = str_replace("§a", "", $text[2]);
						$level = $this->getServer()->getLevelByName($namemap);
                                        if($text[1]==TextFormat::YELLOW  . "0 / 12")
					{
						$player->setNameTag("§l§c[RED]" . $player->getName());
                                                $thespawn = $config->get($namemap . "Spawn1");
                                                $name = $player->getName();
                                                $player->sendMessage($this->prefix . "You entered in §c[RED] §fteam");
                                                foreach($level->getPlayers() as $playersinarena)
                                                {
                                                $playersinarena->sendMessage($name . " Has entered in §c[RED] §fteam");
                                                }
					}
                                        else if($text[1]==TextFormat::YELLOW  . "1 / 12")
					{
						$player->setNameTag("§l§9[BLUE]" . $player->getName());
                                                $thespawn = $config->get($namemap . "Spawn2");
                                                $name = $player->getName();
                                                $player->sendMessage($this->prefix . "You entered in §9[BLUE] §fteam");
                                                foreach($level->getPlayers() as $playersinarena)
                                                {
                                                $playersinarena->sendMessage($name . " Has entered in §9[BLUE] §fteam");
                                                }
					}
                                        else if($text[1]==TextFormat::YELLOW  . "2 / 12")
					{
						$player->setNameTag("§l§a[GREEN]" . $player->getName());
                                                $thespawn = $config->get($namemap . "Spawn3");
                                                $name = $player->getName();
                                                $player->sendMessage($this->prefix . "You entered in §a[GREEN] §fteam");
                                                foreach($level->getPlayers() as $playersinarena)
                                                {
                                                $playersinarena->sendMessage($name . " Has entered §a[GREEN] §fteam");
                                                }
					}
                                        else if($text[1]==TextFormat::YELLOW  . "3 / 12")
					{
						$player->setNameTag("§l§c[RED]" . $player->getName());
                                                $thespawn = $config->get($namemap . "Spawn4");
                                                $name = $player->getName();
                                                $player->sendMessage($this->prefix . "You entered in §c[RED] §fteam");
                                                foreach($level->getPlayers() as $playersinarena)
                                                {
                                                $playersinarena->sendMessage($name . " Has entered in §c[RED] §fteam");
                                                }
					}
                                        else if($text[1]==TextFormat::YELLOW  . "4 / 12")
					{
						$player->setNameTag("§l§9[BLUE]" . $player->getName());
                                                $thespawn = $config->get($namemap . "Spawn5");
                                                $name = $player->getName();
                                                $player->sendMessage($this->prefix . "You entered in §9[BLUE] §fteam");
                                                foreach($level->getPlayers() as $playersinarena)
                                                {
                                                $playersinarena->sendMessage($name . " Has entered in §9[BLUE] §fteam");
                                                }
					}
                                        else if($text[1]==TextFormat::YELLOW  . "5 / 12")
					{
						$player->setNameTag("§l§a[GREEN]" . $player->getName());
                                                $thespawn = $config->get($namemap . "Spawn6");
                                                $name = $player->getName();
                                                $player->sendMessage($this->prefix . "You entered in §a[GREEN] §fteam");
                                                foreach($level->getPlayers() as $playersinarena)
                                                {
                                                $playersinarena->sendMessage($name . " Has entered §a[GREEN] §fteam");
                                                }
					}
                                        else if($text[1]==TextFormat::YELLOW  . "6 / 12")
					{
						$player->setNameTag("§l§c[RED]" . $player->getName());
                                                $thespawn = $config->get($namemap . "Spawn7");
                                                $name = $player->getName();
                                                $player->sendMessage($this->prefix . "You entered in §c[RED] §fteam");
                                                foreach($level->getPlayers() as $playersinarena)
                                                {
                                                $playersinarena->sendMessage($name . " Has entered in §c[RED] §fteam");
                                                }
					}
                                        else if($text[1]==TextFormat::YELLOW  . "7 / 12")
					{
						$player->setNameTag("§l§9[BLUE]" . $player->getName());
                                                $thespawn = $config->get($namemap . "Spawn8");
                                                $name = $player->getName();
                                                $player->sendMessage($this->prefix . "You entered in §9[BLUE] §fteam");
                                                foreach($level->getPlayers() as $playersinarena)
                                                {
                                                $playersinarena->sendMessage($name . " Has entered in §9[BLUE] §fteam");
                                                }
					}
                                        else if($text[1]==TextFormat::YELLOW  . "8 / 12")
					{
						$player->setNameTag("§l§a[GREEN]" . $player->getName());
                                                $thespawn = $config->get($namemap . "Spawn9");
                                                $name = $player->getName();
                                                $player->sendMessage($this->prefix . "You entered in §a[GREEN] §fteam");
                                                foreach($level->getPlayers() as $playersinarena)
                                                {
                                                $playersinarena->sendMessage($name . " Has entered §a[GREEN] §fteam");
                                                }
					}
                                        else if($text[1]==TextFormat::YELLOW  . "9 / 12")
					{
						$player->setNameTag("§l§c[RED]" . $player->getName());
                                                $thespawn = $config->get($namemap . "Spawn10");
                                                $name = $player->getName();
                                                $player->sendMessage($this->prefix . "You entered in §c[RED] §fteam");
                                                foreach($level->getPlayers() as $playersinarena)
                                                {
                                                $playersinarena->sendMessage($name . " Has entered in §c[RED] §fteam");
                                                }
					}
                                        else if($text[1]==TextFormat::YELLOW  . "10 / 12")
					{
						$player->setNameTag("§l§9[BLUE]" . $player->getName());
                                                $thespawn = $config->get($namemap . "Spawn11");
                                                $name = $player->getName();
                                                $player->sendMessage($this->prefix . "You entered in §9[BLUE] §fteam");
                                                foreach($level->getPlayers() as $playersinarena)
                                                {
                                                $playersinarena->sendMessage($name . " Has entered in §9[BLUE] §fteam");
                                                }
					}
                                        else if($text[1]==TextFormat::YELLOW  . "11 / 12")
					{
						$player->setNameTag("§l§a[GREEN]" . $player->getName());
                                                $thespawn = $config->get($namemap . "Spawn12");
                                                $name = $player->getName();
                                                $player->sendMessage($this->prefix . "You entered in §a[GREEN] §fteam");
                                                foreach($level->getPlayers() as $playersinarena)
                                                {
                                                $playersinarena->sendMessage($name . " Has entered §a[GREEN] §fteam");
                                                }
					}
						$spawn = new Position($thespawn[0]+0.5,$thespawn[1],$thespawn[2]+0.5,$level);
						$level->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
						$player->teleport($spawn,0,0);
						$player->getInventory()->clearAll();
                                                $player->removeAllEffects();
                                                $player->setHealth(20);
                                                $config2 = new Config($this->getDataFolder() . "/rank.yml", Config::YAML);
						$rank = $config2->get($player->getName());
						if($rank == "§b[§aWarrior§4+§b]")
						{
							$player->getInventory()->setContents(array(Item::get(0, 0, 0)));
							$player->getInventory()->setHelmet(Item::get(Item::GOLD_HELMET));
							$player->getInventory()->setChestplate(Item::get(Item::GOLD_CHESTPLATE));
							$player->getInventory()->setLeggings(Item::get(Item::GOLD_LEGGINGS));
							$player->getInventory()->setBoots(Item::get(Item::GOLD_BOOTS));
							$player->getInventory()->setItem(0, Item::get(Item::DIAMOND_AXE, 0, 1));
							$player->getInventory()->setHotbarSlotIndex(0, 0);
						}
						else if($rank == "§b[§aWarrior§b]")
						{
							$player->getInventory()->setContents(array(Item::get(0, 0, 0)));
							$player->getInventory()->setHelmet(Item::get(Item::GOLD_HELMET));
							$player->getInventory()->setChestplate(Item::get(Item::GOLD_CHESTPLATE));
							$player->getInventory()->setLeggings(Item::get(Item::GOLD_LEGGINGS));
							$player->getInventory()->setBoots(Item::get(Item::GOLD_BOOTS));
							$player->getInventory()->setItem(0, Item::get(Item::IRON_AXE, 0, 1));
							$player->getInventory()->setHotbarSlotIndex(0, 0);
						}
						else if($rank == "§b[§cArcher§b]")
						{
							$player->getInventory()->setContents(array(Item::get(0, 0, 0)));
							$player->getInventory()->setHelmet(Item::get(Item::GOLD_HELMET));
							$player->getInventory()->setChestplate(Item::get(Item::GOLD_CHESTPLATE));
							$player->getInventory()->setLeggings(Item::get(Item::GOLD_LEGGINGS));
							$player->getInventory()->setBoots(Item::get(Item::GOLD_BOOTS));
							$player->getInventory()->setItem(0, Item::get(Item::BOW, 0, 1));
                                                        $player->getInventory()->setItem(1, Item::get(Item::ARROW, 0, 10));
							$player->getInventory()->setHotbarSlotIndex(0, 0);
						}
						else if($rank == "§b[§6Pyromancer§b]")
						{
							$player->getInventory()->setContents(array(Item::get(0, 0, 0)));
							$player->getInventory()->setHelmet(Item::get(Item::IRON_HELMET));
							$player->getInventory()->setChestplate(Item::get(Item::CHAIN_CHESTPLATE));
							$player->getInventory()->setLeggings(Item::get(Item::CHAIN_LEGGINGS));
							$player->getInventory()->setBoots(Item::get(Item::IRON_BOOTS));
							$player->getInventory()->setItem(0, Item::get(Item::TNT, 0, 2));
                                                        $player->getInventory()->setItem(1, Item::get(Item::FLINT_AND_STEEL, 0, 1));
							$player->getInventory()->setHotbarSlotIndex(0, 0);
						}
					}
					else
					{
						$playerlang = new Config($this->getDataFolder() . "/languages.yml", Config::YAML);
						$lang = new Config($this->getDataFolder() . "/lang.yml", Config::YAML);
						$toUse = $lang->get($playerlang->get($player->getName()));
						$player->sendMessage($this->prefix . $toUse["cannotjoin"]);
					}
				}
			}
		}
		else if($this->mode>=1&&$this->mode<=12)
		{
			$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
			$config->set($this->currentLevel . "Spawn" . $this->mode, array($block->getX(),$block->getY()+1,$block->getZ()));
			$player->sendMessage($this->prefix . "Spawn " . $this->mode . " has been registered!");
			$this->mode++;
			if($this->mode==13)
			{
				$player->sendMessage($this->prefix . "Now tap any block for back to the lobby.");
			}
			$config->save();
		}
		else if($this->mode==13)
		{
			$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
			$level = $this->getServer()->getLevelByName($this->currentLevel);
			$level->setSpawn = (new Vector3($block->getX(),$block->getY()+2,$block->getZ()));
			$config->set("arenas",$this->arenas);
			$player->sendMessage($this->prefix . "Now tap the sign to register arena!");
                        $player->sendMessage($this->prefix . "Dont forget ZIP your map to SkyWarsTeamDoDo/arenas!");
			$spawn = $this->getServer()->getDefaultLevel()->getSafeSpawn();
			$this->getServer()->getDefaultLevel()->loadChunk($spawn->getFloorX(), $spawn->getFloorZ());
			$player->teleport($spawn,0,0);
			$config->save();
			$this->mode=26;
		}
	}
	
	public function refreshArenas()
	{
		$config = new Config($this->getDataFolder() . "/config.yml", Config::YAML);
		$config->set("arenas",$this->arenas);
		foreach($this->arenas as $arena)
		{
			$config->set($arena . "PlayTime", 780);
			$config->set($arena . "StartTime", 90);
		}
		$config->save();
	}
        
        public function zip($player, $name)
        {
        $path = realpath($player->getServer()->getDataPath() . 'worlds/' . $name);
				$zip = new \ZipArchive;
				@mkdir($this->getDataFolder() . 'arenas/', 0755);
				$zip->open($this->getDataFolder() . 'arenas/' . $name . '.zip', $zip::CREATE | $zip::OVERWRITE);
				$files = new \RecursiveIteratorIterator(
					new \RecursiveDirectoryIterator($path),
					\RecursiveIteratorIterator::LEAVES_ONLY
				);
                                foreach ($files as $nu => $file) {
					if (!$file->isDir()) {
						$relativePath = $name . '/' . substr($file, strlen($path) + 1);
						$zip->addFile($file, $relativePath);
					}
				}
				$zip->close();
				$player->getServer()->loadLevel($name);
				unset($zip, $path, $files);
        }
}

class RefreshSigns extends PluginTask {
    public $prefix = TextFormat::GRAY . "[" . TextFormat::AQUA . TextFormat::BOLD . "Sky" . TextFormat::GREEN . "Wars§6Team" . TextFormat::RESET . TextFormat::GRAY . "]";
	public function __construct($plugin)
	{
		$this->plugin = $plugin;
		parent::__construct($plugin);
	}
  
	public function onRun($tick)
	{
		$allplayers = $this->plugin->getServer()->getOnlinePlayers();
		$level = $this->plugin->getServer()->getDefaultLevel();
		$tiles = $level->getTiles();
		foreach($tiles as $t) {
			if($t instanceof Sign) {	
				$text = $t->getText();
				if($text[3]==$this->prefix)
				{
					$aop = 0;
                                        $namemap = str_replace("§a", "", $text[2]);
					foreach($allplayers as $player){if($player->getLevel()->getFolderName()==$namemap){$aop=$aop+1;}}
					$ingame = TextFormat::AQUA . "[Join]";
					$config = new Config($this->plugin->getDataFolder() . "/config.yml", Config::YAML);
					if($config->get($namemap . "PlayTime")!=780)
					{
						$ingame = TextFormat::DARK_PURPLE . "[Running]";
					}
					else if($aop>=12)
					{
						$ingame = TextFormat::GOLD . "[Full]";
					}
					$t->setText($ingame,TextFormat::YELLOW  . $aop . " / 12",$text[2],$this->prefix);
				}
			}
		}
	}
}

class GameSender extends PluginTask {
    public $prefix = TextFormat::GRAY . "[" . TextFormat::AQUA . TextFormat::BOLD . "Sky" . TextFormat::GREEN . "Wars§6Team" . TextFormat::RESET . TextFormat::GRAY . "]";
	public function __construct($plugin)
	{
		$this->plugin = $plugin;
		parent::__construct($plugin);
	}
  
	public function onRun($tick)
	{
		$config = new Config($this->plugin->getDataFolder() . "/config.yml", Config::YAML);
		$arenas = $config->get("arenas");
		if(!empty($arenas))
		{
			foreach($arenas as $arena)
			{
				$time = $config->get($arena . "PlayTime");
				$timeToStart = $config->get($arena . "StartTime");
				$levelArena = $this->plugin->getServer()->getLevelByName($arena);
				if($levelArena instanceof Level)
				{
					$playersArena = $levelArena->getPlayers();
					if(count($playersArena)==0)
					{
						$config->set($arena . "PlayTime", 780);
						$config->set($arena . "StartTime", 90);
					}
					else
					{
						if(count($playersArena)>=2)
						{
							if($timeToStart>0)
							{
								$timeToStart--;
								foreach($playersArena as $pl)
								{
									$playerlang = new Config($this->plugin->getDataFolder() . "/languages.yml", Config::YAML);
									$lang = new Config($this->plugin->getDataFolder() . "/lang.yml", Config::YAML);
									$toUse = $lang->get($playerlang->get($pl->getName()));
									$pl->sendTip("§e< " . TextFormat::GREEN . $timeToStart . " " . $toUse["seconds"] . "§e >");
								}
                                                                if($timeToStart==89)
                                                                {
                                                                    $levelArena->setTime(7000);
                                                                        $levelArena->stopTime();
                                                                }
								if($timeToStart<=0)
								{
									$this->refillChests($levelArena);
								}
								$config->set($arena . "StartTime", $timeToStart);
							}
							else
							{
								$aop = count($levelArena->getPlayers());
                                                                $tages = array();
                                                                $colors = array();
								if($aop>=1)
								{
                                                                foreach($playersArena as $pl)
                                                                {
                                                                    $tags = $pl->getNameTag();
                                                                    array_push($tages, $tags);
                                                                }
                                                                    
                                                                    $nametags = implode("-", $tages);
                                                                    
									foreach($playersArena as $pl)
									{
                                                                            if((strpos($nametags, "§l§c[RED]") !== false) && (strpos($nametags, "§l§9[BLUE]") === false) && (strpos($nametags, "§l§a[GREEN]") === false))
                                                                                    {
										foreach($this->plugin->getServer()->getOnlinePlayers() as $plpl)
										{
											$playerlang = new Config($this->plugin->getDataFolder() . "/languages.yml", Config::YAML);
											$lang = new Config($this->plugin->getDataFolder() . "/lang.yml", Config::YAML);
											$toUse = $lang->get($playerlang->get($plpl->getName()));
											$plpl->sendMessage($this->prefix . "§l§c[RED] §l§bWon the Game");
										}
										$pl->getInventory()->clearAll();
										$pl->removeAllEffects();
										$pl->setNameTag($pl->getName());
										$spawn = $this->plugin->getServer()->getDefaultLevel()->getSafeSpawn();
                                                                                $this->plugin->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
										$pl->teleport($spawn,0,0);
                                                                                $pl->setHealth(20);
                                                                                if(!empty($this->plugin->api))
                                                                        {
                                                                        $this->plugin->api->addMoney($pl,500);
                                                                        }
                                                                                $this->reload($levelArena);
                                                                                
									
									$config->set($arena . "PlayTime", 780);
									$config->set($arena . "StartTime", 180);
                                                                            }
                                                                            if((strpos($nametags, "§l§c[RED]") === false) && (strpos($nametags, "§l§9[BLUE]") !== false) && (strpos($nametags, "§l§a[GREEN]") === false))
                                                                                    {
										foreach($this->plugin->getServer()->getOnlinePlayers() as $plpl)
										{
											$playerlang = new Config($this->plugin->getDataFolder() . "/languages.yml", Config::YAML);
											$lang = new Config($this->plugin->getDataFolder() . "/lang.yml", Config::YAML);
											$toUse = $lang->get($playerlang->get($plpl->getName()));
											$plpl->sendMessage($this->prefix . "§l§9[BLUE] §l§bWon the Game");
										}
										$pl->getInventory()->clearAll();
										$pl->removeAllEffects();
										$pl->setNameTag($pl->getName());
										$spawn = $this->plugin->getServer()->getDefaultLevel()->getSafeSpawn();
                                                                                $this->plugin->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
										$pl->teleport($spawn,0,0);
                                                                                $pl->setHealth(20);
                                                                                if(!empty($this->plugin->api))
                                                                        {
                                                                        $this->plugin->api->addMoney($pl,500);
                                                                        }
                                                                                $this->reload($levelArena);
                                                                                
									
									$config->set($arena . "PlayTime", 780);
									$config->set($arena . "StartTime", 180);
                                                                            }
                                                                            if((strpos($nametags, "§l§c[RED]") === false) && (strpos($nametags, "§l§9[BLUE]") === false) && (strpos($nametags, "§l§a[GREEN]") !== false))
                                                                                    {
										foreach($this->plugin->getServer()->getOnlinePlayers() as $plpl)
										{
											$playerlang = new Config($this->plugin->getDataFolder() . "/languages.yml", Config::YAML);
											$lang = new Config($this->plugin->getDataFolder() . "/lang.yml", Config::YAML);
											$toUse = $lang->get($playerlang->get($plpl->getName()));
											$plpl->sendMessage($this->prefix . "§l§a[GREEN] §l§bWon the Game");
										}
										$pl->getInventory()->clearAll();
										$pl->removeAllEffects();
										$pl->setNameTag($pl->getName());
										$spawn = $this->plugin->getServer()->getDefaultLevel()->getSafeSpawn();
                                                                                $this->plugin->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
										$pl->teleport($spawn,0,0);
                                                                                $pl->setHealth(20);
                                                                                if(!empty($this->plugin->api))
                                                                        {
                                                                        $this->plugin->api->addMoney($pl,500);
                                                                        }
                                                                                $this->reload($levelArena);
									
									$config->set($arena . "PlayTime", 780);
									$config->set($arena . "StartTime", 180);
                                                                            }
                                                                        }
								}
                                                                if(($aop>=2))
                                                                    {
                                                                    foreach($playersArena as $pl)
                                                                        {
                                                                        $nametag = $pl->getNameTag();
                                                                        array_push($colors, $nametag);
                                                                        }
                                                                        $names = implode("-", $colors);
                                                                        $reds = substr_count($names, "§l§c[RED]");
                                                                        $blues = substr_count($names, "§l§9[BLUE]");
                                                                        $greens = substr_count($names, "§l§a[GREEN]");
                                                                        foreach($playersArena as $pla)
                                                                        {
                                                                        $pla->sendTip("§l§cRED:" . $reds . "  §9BLUE:" . $blues . "  §aGREEN:" . $greens);
                                                                        }
                                                                }
								$time--;
								if($time == 779)
								{
									foreach($playersArena as $pl)
									{
										$pl->sendMessage("§e>--------------------------------");
                                                                                $pl->sendMessage("§e>§c¡Attention: §6The game is starting!");
                                                                                $pl->sendMessage("§e>§fUsing the map: §a" . $arena);
                                                                                $pl->sendMessage("§e>§bYou have §a30 §bseconds of Invencibility");
                                                                                $pl->sendMessage("§e>--------------------------------");
									}
								}
                                                                if($time == 765)
								{
									foreach($playersArena as $pl)
									{
										$pl->sendMessage("§e>§e--------------------------------");
                                                                                $pl->sendMessage("§e>§bYou have §a15 §bseconds of Invencibility");
                                                                                $pl->sendMessage("§e>§e--------------------------------");
									}
								}
								if($time == 750)
								{
									foreach($playersArena as $pl)
									{
										$pl->sendMessage("§e>§e-------------------");
                                                                                $pl->sendMessage("§e>§bYou are not Invincible");
                                                                                $pl->sendMessage("§e>§e-------------------");
									}
								}
                                                                if($time == 550)
								{
									foreach($playersArena as $pl)
									{
										$pl->sendMessage("§e>§e--------------------------");
                                                                                $pl->sendMessage("§e>§bPlugin remake by KaitoDoDoYT");
                                                                                $pl->sendMessage("§e>§e--------------------------");
									}
								}
                                                                if($time == 480)
								{
									foreach($playersArena as $pl)
									{
										$pl->sendMessage("§e>§e--------------------------");
                                                                                $pl->sendMessage("§e>§bThe chest has been refilled");
                                                                                $pl->sendMessage("§e>§e--------------------------");
									}
									$this->refillChests($levelArena);
									
								}
								if($time>=240)
								{
								$time2 = $time - 180;
								$minutes = $time2 / 60;
								}
								else
								{
									$minutes = $time / 60;
									if(is_int($minutes) && $minutes>0)
									{
										foreach($playersArena as $pl)
										{
											$playerlang = new Config($this->plugin->getDataFolder() . "/languages.yml", Config::YAML);
                                                                                        $lang = new Config($this->plugin->getDataFolder() . "/lang.yml", Config::YAML);
											$toUse = $lang->get($playerlang->get($pl->getName()));
											$pl->sendMessage($this->prefix . $minutes . " " . $toUse["remainingminutes"]);
										}
									}
									else if($time == 30 || $time == 15 || $time == 10 || $time ==5 || $time ==4 || $time ==3 || $time ==2 || $time ==1)
									{
										foreach($playersArena as $pl)
										{
											$playerlang = new Config($this->plugin->getDataFolder() . "/languages.yml", Config::YAML);
                                                                                        $lang = new Config($this->plugin->getDataFolder() . "/lang.yml", Config::YAML);
											$toUse = $lang->get($playerlang->get($pl->getName()));
											$pl->sendMessage($this->prefix . $time . " " . $toUse["remainingseconds"]);
										}
									}
									if($time <= 0)
									{
										$spawn = $this->plugin->getServer()->getDefaultLevel()->getSafeSpawn();
										$this->plugin->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
										foreach($playersArena as $pl)
										{
											$pl->teleport($spawn,0,0);
											$playerlang = new Config($this->plugin->getDataFolder() . "/languages.yml", Config::YAML);
                                                                                        $lang = new Config($this->plugin->getDataFolder() . "/lang.yml", Config::YAML);
											$toUse = $lang->get($playerlang->get($pl->getName()));
											$pl->sendMessage($this->prefix . $toUse["nowinner"]);
											$pl->getInventory()->clearAll();
										}
										$time = 780;
									}
								}
								$config->set($arena . "PlayTime", $time);
							}
						}
						else
						{
							if($timeToStart<=0)
							{
								foreach($playersArena as $pl)
								{
									foreach($this->plugin->getServer()->getOnlinePlayers() as $plpl)
									{
										$playerlang = new Config($this->plugin->getDataFolder() . "/languages.yml", Config::YAML);
                                                                                $lang = new Config($this->plugin->getDataFolder() . "/lang.yml", Config::YAML);
										$toUse = $lang->get($playerlang->get($plpl->getName()));
										$plpl->sendMessage($this->prefix . $pl->getNameTag() . "§l§b " . $toUse["won"]);
									}
                                                                        $pl->getInventory()->clearAll();
                                                                        $pl->removeAllEffects();
									$pl->setNameTag($pl->getName());
									$spawn = $this->plugin->getServer()->getDefaultLevel()->getSafeSpawn();
									$this->plugin->getServer()->getDefaultLevel()->loadChunk($spawn->getX(), $spawn->getZ());
									$pl->teleport($spawn,0,0);
                                                                        $pl->setHealth(20);
                                                                        if(!empty($this->plugin->api))
                                                                        {
                                                                        $this->plugin->api->addMoney($pl,500);
                                                                        }
                                                                        $this->reload($levelArena);
								}
								$config->set($arena . "PlayTime", 780);
								$config->set($arena . "StartTime", 90);
							}
							else
							{
								foreach($playersArena as $pl)
								{
									$playerlang = new Config($this->plugin->getDataFolder() . "/languages.yml", Config::YAML);
									$lang = new Config($this->plugin->getDataFolder() . "/lang.yml", Config::YAML);
									$toUse = $lang->get($playerlang->get($pl->getName()));
									$pl->sendTip(TextFormat::DARK_AQUA . $toUse["moreplayers"]);
								}
								$config->set($arena . "PlayTime", 780);
								$config->set($arena . "StartTime", 90);
							}
						}
					}
				}
			}
		}
		$config->save();
	}
	
	public function refillChests(Level $level)
	{
		$config = new Config($this->plugin->getDataFolder() . "/config.yml", Config::YAML);
		$tiles = $level->getTiles();
		foreach($tiles as $t) {
			if($t instanceof Chest) 
			{
				$chest = $t;
				$chest->getInventory()->clearAll();
				if($chest->getInventory() instanceof ChestInventory)
				{
					for($i=0;$i<=26;$i++)
					{
						$rand = rand(1,3);
						if($rand==1)
						{
							$k = array_rand($config->get("chestitems"));
							$v = $config->get("chestitems")[$k];
							$chest->getInventory()->setItem($i, Item::get($v[0],$v[1],$v[2]));
						}
					}									
				}
			}
		}
	}
        
        public function reload(Level $lev)
	{
		//Map reset
                $name = $lev->getFolderName();
		if ($this->plugin->getServer()->isLevelLoaded($name))
			$this->plugin->getServer()->unloadLevel($this->plugin->getServer()->getLevelByName($name));
		if (!is_file($this->plugin->getDataFolder() . 'arenas/' . $name . '.zip'))
			return false;
		$zip = new \ZipArchive;
		$zip->open($this->plugin->getDataFolder() . 'arenas/' . $name . '.zip');
		$zip->extractTo($this->plugin->getServer()->getDataPath() . 'worlds');
		$zip->close();
		unset($zip);
		$this->plugin->getServer()->loadLevel($name);
		return true;
	}
}

