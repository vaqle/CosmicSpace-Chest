<?php


namespace vale\tasks;


use muqsit\invmenu\InvMenu;
use pocketmine\item\Item;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use vale\inventory\SpaceChestInventory;
use vale\Main;

class AnimateSlotsTask extends Task
{

	public $player;

	public $name;

	public $menu;

	public $duration = 25;

	/**
	 * AnimateSlotsTask constructor.
	 * @param Player $player
	 * @param string $string
	 * @param InvMenu $menu
	 */
	public function __construct(Player $player, string $string, InvMenu $menu)
	{
		$this->player = $player;
		$this->name = $string;
		$this->menu = $menu;
	}

	/**
	 * @return InvMenu
	 */
	public function getMenu(): InvMenu
	{
		return $this->menu;
	}

	public function onRun(int $currentTick)
	{
		--$this->duration;
		if ($this->duration === 0) {
			unset(SpaceChestInventory::$slots[$this->player->getName()]);
			unset(SpaceChestInventory::$simpleChest[$this->player->getName()]);
			Main::getInstance()->getScheduler()->cancelTask($this->getTaskId());
		}
		if ($this->duration <= 29) {
			foreach ($this->menu->getInventory()->getContents() as $slot => $item) {
				if (!$item->getNamedTag()->hasTag("reward1") && !$item->getNamedTag()->hasTag("reward3") && !in_array($slot, SpaceChestInventory::$OUTSIDE_GRID) && !$item->hasCustomBlockData()) {
					$this->menu->getInventory()->setItem($slot, $this->determineLootTier());
				}
				if ($this->duration <= 18) {
					if (!$item->getNamedTag()->hasTag("reward1") && !$item->getNamedTag()->hasTag("reward3") && !in_array($slot, SpaceChestInventory::$OUTSIDE_GRID) && !$item->hasCustomBlockData()) {
						$toset = Item::get(Item::DIAMOND_SWORD);
						$this->menu->getInventory()->setItem($slot, $this->randomItemsShowCase());
					}
					if ($this->duration <= 7) {
						if (!$item->getNamedTag()->hasTag("reward1") && !$item->getNamedTag()->hasTag("reward3") && !in_array($slot, SpaceChestInventory::$OUTSIDE_GRID) && !$item->hasCustomBlockData()) {
							$toset = Item::get(Item::AIR);
							$this->menu->getInventory()->setItem($slot, $toset);
						}
					}
				}
			}
		}
	}
	public function randomItemsShowCase(){
		$item1 = Item::get(Item::DIRT);
		$item2 = Item::get(Item::LEVER);
		$item3 = Item::get(Item::APPLE);
		$item4 = Item::get(Item::SKULL);
		$item5 = Item::get(Item::EMERALD, 0, 1);
		$item6 = Item::get(Item::ACACIA_SIGN,0,1);
		$item7 = Item::get(Item::ACACIA_FENCE_GATE);
		$item8 = Item::get(Item::REDSTONE);
		$rewards = [$item1,$item2, $item3,$item4,$item5,$item6,$item7,$item8];
		$reward = $rewards[array_rand($rewards)];
		return $reward;
	}
	public function determineLootTier(){
		$rand = mt_rand(1,100);
		$simple = Item::get(Item::STAINED_GLASS_PANE,0,1);
		$simple->setCustomName("§r§f§lSimple Mystery Item");
		$simple->setLore([
			'§r§7Click here to reveal a',
			'§r§fSimple §r§7item from the',
			'§r§7loot table.',
			'',
			'§r§f§l+'.$rand . '% §r§fRare Loot Chance'
		]);
		$unique = Item::get(Item::STAINED_GLASS_PANE,5,1);
		$unique->setCustomName("§r§a§lUnique Mystery Item");
		$unique->setLore([
			'§r§7Click here to reveal a',
			'§r§aUnique §r§7item from the',
			'§r§7loot table.',
			'',
			'§r§f§a+'.$rand . '% §r§aRare Loot Chance'
		]);

		$elite = Item::get(Item::STAINED_GLASS_PANE,3,1);
		$elite->setCustomName("§r§b§lElite Mystery Item");
		$elite->setLore([
			'§r§7Click here to reveal a',
			'§r§bElite §r§7item from the',
			'§r§7loot table.',
			'',
			'§r§f§b+'.$rand . '% §r§bRare Loot Chance'
		]);
		$rewards = [$simple,$unique, $elite];
		$reward = $rewards[array_rand($rewards)];
		return $reward;
	}
}