<?php
namespace vale;

use muqsit\invmenu\InvMenuHandler;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\item\ItemIds;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use vale\inventory\SpaceChestInventory;


class Main extends PluginBase implements Listener
{
	public static $instance;

	public function onEnable(): void
	{
		if (!InvMenuHandler::isRegistered()) InvMenuHandler::register($this);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
		self::$instance = $this;
	}

	public static function getInstance(): self{
		return self::$instance;
	}


	public function onInteract(PlayerInteractEvent $event){
		$player = $event->getPlayer();
		$item = $event->getItem();
		if($item->getId() === ItemIds::DIAMOND_SWORD){
			$inv = new SpaceChestInventory();
			$inv->open($player, "simple");
		}
	}
}