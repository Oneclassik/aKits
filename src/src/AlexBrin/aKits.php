<?php

	namespace AlexBrin;

	use pocketmine\plugin\PluginBase;
	use pocketmine\utils\Config;
	use pocketmine\utils\Color;
	use pocketmine\command\Command;
	use pocketmine\command\CommandSender;
	use pocketmine\inventory\PlayerInventory;
	use pocketmine\item\Item;
	use pocketmine\item\enchantment\Enchantment;
	use pocketmine\scheduler\CallbackTask;
	use pocketmine\item\Armor;
	use pocketmine\Player;
	use pocketmine\Server;

	class aKits extends PluginBase {
		public $config,
					 $kits,
					 $given = array();

		public $v = "3";

		public function onEnable() {
			if(!is_dir($this->getDataFolder()))
				@mkdir($this->getDataFolder());
			$this->saveDefaultConfig();
			$this->saveResource("kits.yml");
			$this->config = (new Config($this->getDataFolder()."config.yml", Config::YAML))->getAll();
			$this->kits = (new Config($this->getDataFolder()."kits.yml", Config::YAML))->getAll();
			$this->users = (new Config($this->getDataFolder()."users.json", Config::JSON))->getAll();
			$this->getLogger()->info("§bПлагин загружен");
		}

		public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
			if($sender instanceof Player) {
				if(strtolower($command->getName()) == 'kit') {
					if(count($args) == 0) {
						$sender->sendMessage($this->help($sender));
						return true;
					}
					if(count($args) > 1) {
						$sender->sendMessage($this->help($sender));
						return true;
					}
					$kitName = strtolower($args[0]);
					if(!isset($this->kits[$kitName])) {
						$sender->sendMessage($this->config['notExist']);
						return true;
					}
					if(!$sender->hasPermission("akits.$kitName")) {
						$sender->sendMessage($this->config['notAvailable']);
						return true;
					}
					$kit = $this->kits[$kitName];
					$nickname = strtolower($sender->getName());
					$time = time();
					if(isset($this->users[$nickname][$kitName]))
						if($this->users[$nickname][$kitName] > $time) {
							$sender->sendMessage(str_replace('{time}', $this->users[$nickname][$kitName] - $time, $this->config['wait']));
							return true;
						}
					if(isset($kit['helmet']))
						if(is_array($kit['helmet']))
							$sender->getInventory()->setHelmet($this->item(['kit' => $kitName, 'player' => $sender->getName()], $kit['helmet']));
					if(isset($kit['chestplate']))
						if(is_array($kit['chestplate']))
							$sender->getInventory()->setChestplate($this->item(['kit' => $kitName, 'player' => $sender->getName()], $kit['chestplate']));
					if(isset($kit['leggings']))
						if(is_array($kit['leggings']))
							$sender->getInventory()->setLeggings($this->item(['kit' => $kitName, 'player' => $sender->getName()], $kit['leggings']));
					if(isset($kit['boots']))
						if(is_array($kit['boots']))
							$sender->getInventory()->setBoots($this->item(['kit' => $kitName, 'player' => $sender->getName()], $kit['boots']));
					foreach($kit['items'] as $item)
						$sender->getInventory()->addItem($this->item(['kit' => $kitName, 'player' => $sender->getName()], $item));
					$sender->sendMessage(str_replace(['{kit}', '{time}'], [$kitName, $kit['time']], $this->config['give']));
					$this->users[$nickname][$kitName] = $time + $kit['time'];
					$this->saveUsers();
				}
			} else $sender->sendMessage("§cOnly for players!");
		}

		/**
		 * @param array $info
		 * @param array $i
		 * @return Item $item
		 */
		private function item($info, $i) {
			if(empty($i['damage']))
				$i['damage'] = 0;
			if(empty($i['count']))
				$i['count'] = 1;
			$item = Item::get($i['id'], $i['damage'], $i['count']);
			if(!empty($i['name']))
				$item->setCustomName(str_replace(['{kit}', '{name}'], [$info['kit'], $info['player']], $i['name']));
			if($item->isArmor())
				if(!empty($i['color'])) {
					$rgb = explode(' ', $i['color']);
					if(count($rgb) == 3)
						$item->setCustomColor(Color::getRGB($rgb[0], $rgb[1], $rgb[2]));
				}
 			if(isset($i['enchants'])) {
 				if(is_array($i['enchants'])) {
 					foreach($i['enchants'] as $ench) {
 						if(!isset($ench['level']))
 							$ench['level'] = 1;
 						$ench = Enchantment::getEnchantment($ench['id'])->setLevel($ench['level']);
 						$item->addEnchantment($ench);
 					}
 				}
 			}
 			return $item;
		}

		private function help($player) {
			$kits = "";
			foreach($this->kits as $kitName => $kit)
				if($player->hasPermission("akits.$kitName"))
					$kits .= str_replace('{kit}', $kitName.', ', $this->config['kit']);
			if(explode(', ', $kits) > 0)
				$kits = substr($kits, 0, -2);
			return $this->config['title'].$kits;
		}

		public function saveUsers() {
			$cfg = new Config($this->getDataFolder().'users.json', Config::JSON);
			$cfg->setAll($this->users);
			$cfg->save();
		}

	}

?>