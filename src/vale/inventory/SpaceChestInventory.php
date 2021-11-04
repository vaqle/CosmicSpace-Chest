<?php
namespace vale\inventory;

use Closure;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\MenuIds;
use muqsit\invmenu\transaction\DeterministicInvMenuTransaction;
use pocketmine\nbt\tag\CompoundTag;
use vale\inventory\SpaceChestInventory as SCI;
use vale\tasks\AnimateSlotsTask;
use vale\tasks\SpaceChestTickTask;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Item;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;
use pocketmine\Server;
use vale\Main;

class SpaceChestInventory
{

	/** @var array|string[]|null */
	public ?array $names = ["Simple" => "Simple Space Chest"];

	/** @var array|int[] $OUTSIDE_GRID */
	public static array $OUTSIDE_GRID = [
		0, 1, 2, 3, 4, 5, 6, 7, 8, 17, 26, 35, 45, 53, 52, 51, 50, 49, 48, 47, 46, 45, 44, 36, 27, 18, 9
	];

	/** @var array|int[] $INSIDE_GRID */
	public static array $INSIDE_GRID = [
		10, 11, 12, 13, 14, 15, 16, 19, 20, 21, 22, 23, 24, 25, 28, 29, 30, 31, 32, 33, 34, 37, 38, 39, 40, 41, 42, 43
	];
	public static array $slots = [];

	/** @var array $simpleChest */
	public static array $simpleChest = [];


	public function setSpecificClicks(Player $player, string $type){
		switch ($type){
			case "simple":
				if(!in_array($player->getName(), self::$simpleChest)){
					array_push(self::$slots, $player->getName());
					array_push(self::$simpleChest, $player->getName());
					self::$simpleChest[$player->getName()] = 0;
					self::$slots[$player->getName()][] = 99;
				}elseif(in_array($player->getName(), self::$simpleChest)){
					self::$simpleChest[$player->getName()] = 0;
				}
				break;
		}
	}

	/**
	 * @param InvMenu $menu
	 * @param Item $item
	 */
	public function fillGrid(InvMenu $menu, Item $item, string $type, string $name): void
	{
		switch ($type) {
			case "outside":
				foreach (self::$OUTSIDE_GRID as $id) {
					$menu->getInventory()->setItem($id, $item->setCustomName("$name"));
				}
				break;
			case "inside":
				$slot = 0;
				foreach (self::$INSIDE_GRID as $id) {
					$menu->getInventory()->setItem($id, $item->setCustomName("$name" . " §r§7#" . $slot));
					$slot++;
				}
				break;
		}
	}


	/**
	 * @param string $key
	 * @return array|string
	 */
	public function getSpaceChestName(string $key): string
	{
		return $key;
	}

	/**
	 * @param Player $player
	 * @param string $type
	 */
	public function open(Player $player, string $type)
	{
		switch ($type) {
			case "simple":
				$opened = 0;
				$this->setSpecificClicks($player, "simple");
				$menu = InvMenu::create(MenuIds::TYPE_DOUBLE_CHEST);
				$menu->setName($this->getSpaceChestName("Simple"));
				$simple = Item::get(Item::STAINED_GLASS, 0 ,1)->setLore([
					'§r§7Choose §f5 mystery items. §7and',
					'§r§f§lSimple §r§7loot will be revealed.',
					'']);
				$simple->getNamedTag()->setTag(new StringTag("reward"));
				$this->fillGrid($menu, Item::get(Item::STAINED_GLASS_PANE, 15, 1), "outside", " ");
				$this->fillGrid($menu, $simple,"inside","§r§f§l???");
				$menu->send($player);
				$menu->setListener(InvMenu::readonly(function (DeterministicInvMenuTransaction $transaction) use ($player, $menu,$opened) {
					$this->handle($transaction, $player, $menu,$opened);
				}));
		}
	}

	public static function getPlayerClickedSlots(Player $player){
			return self::$slots[$player->getName()];
		}

	/**
	 * @param DeterministicInvMenuTransaction $transaction
	 * @param Player $player
	 * @param InvMenu $menu
	 * @param $opened
	 */
	public function handle(DeterministicInvMenuTransaction $transaction, Player $player, InvMenu $menu, $opened)
	{
		$itemclicked = $transaction->getItemClicked();
		$slot = $transaction->getAction()->getSlot();
		if($itemclicked->getNamedTag()->hasTag("reward1")){
              $menu->getInventory()->setItem($slot, $this->determineLootTier());
		}
		if($itemclicked->getNamedTag()->hasTag("reward3")){
			$menu->getInventory()->setItem($slot, self::randomItems());
			$player->getInventory()->addItem($menu->getInventory()->getItem($slot));
		}
		if ($itemclicked->getNamedTag()->hasTag("reward")) {
			self::$slots[$player->getName()][] = $transaction->getAction()->getSlot();
			#var_dump(self::$slots);
			if (self::$simpleChest[$player->getName()] < 5) {
				$this->setRewardChest($slot, $itemclicked->getCustomName(), $menu, "simple");
				Main::getInstance()->getScheduler()->scheduleTask(new SpaceChestTickTask(Main::getInstance(), $player, "simple"));
			}
			if (self::$simpleChest[$player->getName()] >= 4) {
				Main::getInstance()->getScheduler()->scheduleRepeatingTask(new AnimateSlotsTask($player, "simple", $menu), 15);

			} elseif (self::$simpleChest[$player->getName()] > 5) {
				unset(self::$simpleChest[array_search($player->getName(), self::$simpleChest)]);
				$player->sendMessage("only 4 mystery boxes can be selected");
				$player->removeWindow($menu->getInventory());
			}
		}
	}

	/**
	 * @param int $slot
	 * @param string $name
	 * @param InvMenu $menu
	 * @param string $type
	 */
	public function setRewardChest(int $slot, string $name, InvMenu $menu, string $type): void
	{
		switch ($type) {
			case "simple":
		$reward = Item::get(Item::CHEST, 0, 1)->setCustomName($name);
		$reward->setLore(['§r§7You have selected this mystery item.']);
		$reward->getNamedTag()->setTag(new StringTag("reward1"));
		$menu->getInventory()->setItem($slot, $reward);
		break;

		}
	}

	public static function randomItems(){
		$item1 = Item::get(Item::DIRT);
		$item1->setCustomBlockData(new CompoundTag("", [new StringTag("reward", "test")]));
		$item2 = Item::get(Item::LEVER);
		$item2->setCustomBlockData(new CompoundTag("", [new StringTag("reward", "test")]));
		$item3 = Item::get(Item::APPLE);
		$item3->setCustomBlockData(new CompoundTag("", [new StringTag("reward", "test")]));
		$item4 = Item::get(Item::SKULL);
		$item4->setCustomBlockData(new CompoundTag("", [new StringTag("reward", "test")]));
		$item5 = Item::get(Item::EMERALD, 0, 1);
		$item5->setCustomBlockData(new CompoundTag("", [new StringTag("reward", "test")]));
		$item6 = Item::get(Item::ACACIA_SIGN,0,1);
		$item6->setCustomBlockData(new CompoundTag("", [new StringTag("reward", "test")]));
		$item7 = Item::get(Item::ACACIA_FENCE_GATE);
		$item7->setCustomBlockData(new CompoundTag("", [new StringTag("reward", "test")]));
		$item8 = Item::get(Item::REDSTONE);
		$item8->setCustomBlockData(new CompoundTag("", [new StringTag("reward", "test")]));
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
		$simple->getNamedTag()->setTag(new StringTag("reward3"));
		$unique = Item::get(Item::STAINED_GLASS_PANE,5,1);
		$unique->setCustomName("§r§a§lUnique Mystery Item");
		$unique->setLore([
			'§r§7Click here to reveal a',
			'§r§aUnique §r§7item from the',
			'§r§7loot table.',
			'',
			'§r§f§a+'.$rand . '% §r§aRare Loot Chance'
		]);
		$unique->getNamedTag()->setTag(new StringTag("reward3"));
		$elite = Item::get(Item::STAINED_GLASS_PANE,3,1);
		$elite->setCustomName("§r§b§lElite Mystery Item");
		$elite->setLore([
			'§r§7Click here to reveal a',
			'§r§bElite §r§7item from the',
			'§r§7loot table.',
			'',
			'§r§f§b+'.$rand . '% §r§bRare Loot Chance'
		]);
		$elite->getNamedTag()->setTag(new StringTag("reward3"));
		$rewards = [$simple,$unique, $elite];
		$reward = $rewards[array_rand($rewards)];
		return $reward;
	}
}