<?php

namespace superbobby\VanishV2;

use pocketmine\block\Block;
use pocketmine\event\entity\EntityCombustEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\server\QueryRegenerateEvent;
use pocketmine\inventory\DoubleChestInventory;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;
use muqsit\invmenu\InvMenu;

use function array_search;
use function in_array;

class EventListener implements Listener {

    private VanishV2 $plugin;

    public function __construct(VanishV2 $plugin) {
        $this->plugin = $plugin;
    }

    public function onQuit(PlayerQuitEvent $event) {
        $player = $event->getPlayer();
        $name = $player->getName();
        if(in_array($name, VanishV2::$vanish)) {
            if($this->plugin->getConfig()->get("unvanish-after-leaving")) {
                unset(VanishV2::$vanish[array_search($name, VanishV2::$vanish)]);
            }
        }
        if(in_array($player, VanishV2::$online, true)){
            unset(VanishV2::$online[array_search($player, VanishV2::$online, true)]);
            $this->plugin->updateHudPlayerCount();
        }
    }

    public function PickUp(InventoryPickupItemEvent $event) {
        $inv = $event->getInventory();
        $player = $inv->getHolder();
        $name = $player->getName();
        if(in_array($name, VanishV2::$vanish)) {
            $event->setCancelled();
        }
    }

    public function onDamage(EntityDamageEvent $event) {
        $player = $event->getEntity();
        if($player instanceof Player) {
            $name = $player->getName();
            if(in_array($name, VanishV2::$vanish)) {
                if($this->plugin->getConfig()->get("disable-damage")) {
                    $event->setCancelled();
                }
            }
        }
    }

    public function onPlayerBurn(EntityCombustEvent $event) {
        $player = $event->getEntity();
        if($player instanceof Player) {
            $name = $player->getName();
            if(in_array($name, VanishV2::$vanish)) {
                if($this->plugin->getConfig()->get("disable-damage")) {
                    $event->setCancelled();
                }
            }
        }
    }

    public function onExhaust(PlayerExhaustEvent $event) {
        $player = $event->getPlayer();
        if(in_array($player->getName(), VanishV2::$vanish)){
            if(!$this->plugin->getConfig()->get("hunger")){
                $event->setCancelled();
            }
        }
    }

    public function onJoin(PlayerJoinEvent $event){
        $player = $event->getPlayer();
        if(!in_array($player->getName(), VanishV2::$vanish)){
            if(!in_array($player, VanishV2::$online, true)) {
                VanishV2::$online[] = $player;
                $this->plugin->updateHudPlayerCount();
            }
        }
    }

    public function onQuery(QueryRegenerateEvent $event) {
        $event->setPlayerList(VanishV2::$online);
        foreach(Server::getInstance()->getOnlinePlayers() as $p) {
            if(in_array($p->getName(), VanishV2::$vanish)) {
                    $online = $event->getPlayerCount();
                    $event->setPlayerCount($online - 1);
            }
        }
    }

    public function onInteract(PlayerInteractEvent $event) {
        $player = $event->getPlayer();
        $block = $event->getBlock()->getId();
        $chest = $event->getBlock();
        $tile = $chest->getLevel()->getTile(new Vector3($chest->x, $chest->y, $chest->z));
        $action = $event->getAction();
        if(in_array($player->getName(), VanishV2::$vanish)) {
            if($this->plugin->getConfig()->get("silent-chest")) {
                if($block === Block::CHEST or $block === Block::TRAPPED_CHEST) {
                    if($action === $event::RIGHT_CLICK_BLOCK) {
                        if(!$player->isSneaking()) {
                            $event->setCancelled();
                            $name = $tile->getName();
                            $inv = $tile->getInventory();
                            $content = $tile->getInventory()->getContents();
                            if($content != null) {
                                if($inv instanceof DoubleChestInventory) {
                                    $menu = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
                                }else{
                                    $menu = InvMenu::create(InvMenu::TYPE_CHEST);
                                }
                                $menu->getInventory()->setContents($content);
                                $menu->setListener(InvMenu::readonly());
                                $menu->setName($name);
                                $menu->send($player);
                            }else{
                                $player->sendMessage(VanishV2::PREFIX . TextFormat::RED . "This chest is empty");
                            }
                        }
                    }else{
                        $event->setCancelled();
                    }
                }
            }
        }
    }

    public function silentJoin(PlayerJoinEvent $event) {
        if ($event->getPlayer()->hasPermission("vanish.silent")) {
            if ($this->plugin->getConfig()->get("silent-join-leave")["join"]) {
                if (!$this->plugin->getConfig()->get("silent-join-leave")["vanished-only"]) {
                    $event->setJoinMessage(null);
                }else{
                    if (in_array($event->getPlayer()->getName(), VanishV2::$vanish)){
                        $event->setJoinMessage(null);
                    }
                }
            }
        }
    }

    public function silentLeave(PlayerQuitEvent $event) {
        if ($event->getPlayer()->hasPermission("vanish.silent")) {
            if ($this->plugin->getConfig()->get("silent-join-leave")["leave"]) {
                if (!$this->plugin->getConfig()->get("silent-join-leave")["vanished-only"]) {
                    $event->setQuitMessage(null);
                }else{
                    if (in_array($event->getPlayer()->getName(), VanishV2::$vanish)){
                        $event->setQuitMessage(null);
                    }
                }
            }
        }
    }
}