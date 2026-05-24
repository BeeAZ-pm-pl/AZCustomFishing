<?php

namespace BeeAZ\AZCustomFishing;

use pocketmine\plugin\PluginBase;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\world\World;
use pocketmine\item\StringToItemParser;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use pocketmine\item\ItemTypeIds;
use pocketmine\scheduler\ClosureTask;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use BeeAZ\AZCustomFishing\entity\CustomHook;
use BeeAZ\AZCustomFishing\item\CustomFishingRod;
use BeeAZ\AZCustomFishing\command\FishingCommand;
use BeeAZ\AZCustomFishing\command\GiveRodCommand;
use BeeAZ\AZCustomFishing\database\Database;
use BeeAZ\AZCustomFishing\utils\EconomyManager;

class Main extends PluginBase {
    private static self $instance;
    public array $fishingSession = [];
    public array $eventParticipants = [];
    public bool $eventActive = false;
    public int $eventTimer = 0;
    public Database $db;
    public Config $fishConfig;

    protected function onLoad(): void {
        self::$instance = $this;
    }

    public static function getInstance(): self {
        return self::$instance;
    }

    protected function onEnable(): void {
        $this->saveDefaultConfig();
        
        $timezone = $this->getConfig()->getNested("settings.timezone", "");
        if ($timezone !== "" && in_array($timezone, timezone_identifiers_list(), true)) {
            date_default_timezone_set($timezone);
        }

        $this->saveResource("fish.json");
        $this->fishConfig = new Config($this->getDataFolder() . "fish.json", Config::JSON);
        $this->db = new Database($this);

        $ecoPlugin = $this->getConfig()->get("economy", "SimpleEconomy");
        $ecoClasses = [
            "SimpleEconomy" => \NhanAZ\SimpleEconomy\Main::class,
            "EconomyAPI" => \onebone\economyapi\EconomyAPI::class,
            "BedrockEconomy" => \cooldogedev\BedrockEconomy\api\BedrockEconomyAPI::class
        ];

        if (!isset($ecoClasses[$ecoPlugin]) || !class_exists($ecoClasses[$ecoPlugin])) {
            $this->getLogger()->error("Error: Economy plugin class '$ecoPlugin' not found. Please check your config.yml!");
            $this->getServer()->getPluginManager()->disablePlugin($this);
            return;
        }

        EntityFactory::getInstance()->register(CustomHook::class, function (World $world, CompoundTag $nbt): CustomHook {
            return new CustomHook(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
        }, ['AZCustomFishingHook', 'minecraft:fishing_hook']);

        GlobalItemDataHandlers::getDeserializer()->map(ItemTypeIds::FISHING_ROD, fn() => new CustomFishingRod());
        StringToItemParser::getInstance()->register("custom_fishing_rod", fn() => new CustomFishingRod());
        
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
        $this->getServer()->getCommandMap()->registerAll("AZCustomFishing", [
            new FishingCommand($this),
            new GiveRodCommand($this)
        ]);

        $this->eventTimer = $this->getConfig()->getNested("event.interval", 3600);
        $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(fn() => $this->checkEventTime()), 20);
    }

    public function getMessage(string $key, array $replacements = []): string {
        $msg = $this->getConfig()->getNested("messages.$key", $key);
        $prefix = $this->getConfig()->getNested("messages.prefix", "");
        $msg = str_replace("{prefix}", $prefix, $msg);
        foreach ($replacements as $search => $replace) {
            $msg = str_replace("{" . $search . "}", (string)$replace, $msg);
        }
        return $msg;
    }

    public function checkEventTime(): void {
        if (!$this->getConfig()->getNested("event.enabled", true)) return;
        if ($this->eventActive) {
            $this->eventTimer--;
            if ($this->eventTimer <= 0) $this->endEvent();
        } else {
            $this->eventTimer--;
            if ($this->eventTimer <= 0) $this->startEvent();
        }
    }

    public function startEvent(): void {
        $this->eventActive = true;
        $this->eventTimer = $this->getConfig()->getNested("event.duration", 600);
        $this->eventParticipants = [];
        $this->getServer()->broadcastMessage($this->getMessage("event_start", ["time" => ($this->eventTimer / 60)]));
    }

    public function endEvent(): void {
        $this->eventActive = false;
        $this->eventTimer = $this->getConfig()->getNested("event.interval", 3600);
        $this->getServer()->broadcastMessage($this->getMessage("event_end"));
        $topPlayer = "";
        $topScore = 0;
        foreach ($this->eventParticipants as $player => $score) {
            if ($score > $topScore) {
                $topScore = $score;
                $topPlayer = $player;
            }
        }
        if ($topPlayer !== "") {
            $this->getServer()->broadcastMessage($this->getMessage("event_top", ["player" => $topPlayer, "score" => $topScore]));
            $playerObj = $this->getServer()->getPlayerExact($topPlayer);
            if ($playerObj !== null) {
                EconomyManager::addMoney($playerObj, 10000);
            }
            $this->getServer()->broadcastMessage($this->getMessage("event_reward", ["player" => $topPlayer, "reward" => 10000]));
        }
        $this->eventParticipants = [];
    }

    public function registerParticipant(string $name): void {
        if ($this->eventActive && !isset($this->eventParticipants[$name])) $this->eventParticipants[$name] = 0;
    }

    public function handleEventCatch(Player $player, float $len, string $fishName): void {
        $name = $player->getName();
        $pts = (int)($len);
        $this->db->addPoints($name, $pts);
        if ($this->eventActive && isset($this->eventParticipants[$name])) {
            $this->eventParticipants[$name] += $pts;
            $player->sendActionBarMessage($this->getMessage("event_add_point", ["points" => $pts]));
        }
    }

    public function saveRecord(Player $player, string $fishName, float $len): void {
        $this->db->updateMaxFish($player->getName(), $fishName, $len, time());
    }
}
