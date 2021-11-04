<?php

declare(strict_types = 1);

# Ty verge <3 couldnt figure out how to check if player clicked more then 5 slots !

namespace vale\tasks;

//Base Libraries
use pocketmine\scheduler\Task;
use pocketmine\{Server, Player};
use pocketmine\math\Vector3;
use vale\inventory\SpaceChestInventory as SCI;
use pocketmine\entity\{Entity, Effect, EffectInstance};
//Level
use pocketmine\level\{Location, Level};
use pocketmine\level\particle\FlameParticle;
//Core
use vale\Main;

class SpaceChestTickTask extends Task
{

	public $pl;
	public $player;
	public $type;

	public function __construct(Main $plugin, Player $player, string $type)
	{
		$this->pl = $plugin;
		$this->player = $player;
		$this->type = $type;
	}

	public function onRun($tick)
	{
		switch ($this->type) {
			case "simple":
				if (!in_array($this->player->getName(), SCI::$simpleChest)) {
					array_push(SCI::$simpleChest, $this->player->getName());
					SCI::$simpleChest[$this->player->getName()] = 0;
				} elseif (in_array($this->player->getName(), SCI::$simpleChest)) {
					SCI::$simpleChest[$this->player->getName()]++;

				}
				break;
		}
	}
}