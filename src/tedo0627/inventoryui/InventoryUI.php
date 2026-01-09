<?php

namespace tedo0627\inventoryui;

use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\cache\StaticPacketCache;
use pocketmine\plugin\PluginBase;
use pocketmine\world\World;
use tedo0627\inventoryui\exception\InventoryUIResourcePackException;

class InventoryUI {

    private static bool $setup = false;

    private static PluginBase $instance;

    private const uuid = "21f0427f-572a-416d-a90e-c5d9becb0fa3";
    private const version = "1.1.0";

    public static function setup(PluginBase $plugin): void {
        if (self::$setup) return;

        self::$instance = $plugin;

        $server = $plugin->getServer();

        $server->getPluginManager()->registerEvents(new EventListener(), $plugin);

        EntityFactory::getInstance()->register(InventoryEntity::class, function (World $world, CompoundTag $nbt): InventoryEntity {
            return new InventoryEntity(EntityDataHelper::parseLocation($nbt, $world), $nbt);
        }, ["inventoryui:inventory"]);

        $plugin->getScheduler()->scheduleRepeatingTask(new InventoryTickTask($server), 1);

        $nbt = new CompoundTag();
        $nbt->setString("bid", "minecraft:");
        $nbt->setByte("hasspawnegg", false);
        $nbt->setString("id", "inventoryui:inventoryui");
        $nbt->setByte("summonable", true);

        $maxRuntimeId = -1;
        $packet = StaticPacketCache::getInstance()->getAvailableActorIdentifiers();
        $tag = $packet->identifiers->getRoot();
        if ($tag instanceof CompoundTag) {
            $list = $tag->getListTag("idlist");
            if ($list !== null) {
                foreach ($list->getValue() as $childTag) {
                    if (!($childTag instanceof CompoundTag)) continue;

                    $maxRuntimeId = max($childTag->getInt("rid"), $maxRuntimeId);
                }
            }

            $nbt->setInt("rid", $maxRuntimeId + 1);
            $list->push($nbt);
        }

        self::$setup = true;
    }

    public static function getInstance(): PluginBase {
        return self::$instance;
    }
}
