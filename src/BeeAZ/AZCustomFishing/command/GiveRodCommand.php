<?php

declare(strict_types=1);

namespace BeeAZ\AZCustomFishing\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\VanillaItems;
use pocketmine\plugin\PluginOwned;
use pocketmine\plugin\PluginOwnedTrait;
use BeeAZ\AZCustomFishing\Main;

class GiveRodCommand extends Command implements PluginOwned {
    use PluginOwnedTrait {
        PluginOwnedTrait::__construct as private __traitConstruct;
    }

    public function __construct(private Main $plugin) {
        $this->__traitConstruct($plugin);
        parent::__construct("givefishing", "Give Fishing Rod", "/givefishing <player> <tier>", []);
        $this->setPermission("azcustomfishing.command.admin");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$this->testPermission($sender)) return false;
        if (count($args) < 2) return false;
        $player = $this->plugin->getServer()->getPlayerByPrefix($args[0]);
        if ($player === null) {
            $sender->sendMessage($this->plugin->getMessage("player_offline"));
            return false;
        }
        $tier = (int)$args[1];
        $rods = $this->plugin->getConfig()->get("rods", []);
        if (!isset($rods[$tier])) {
            $sender->sendMessage($this->plugin->getMessage("tier_invalid"));
            return false;
        }
        $data = $rods[$tier];
        $rod = VanillaItems::FISHING_ROD();
        $rod->setCustomName($data['name']);
        
        $loreConfig = $this->plugin->getConfig()->getNested("items.rod_lore", []);
        $lore = [];
        foreach ($loreConfig as $line) {
            $lore[] = str_replace(["{tier}", "{max_size}"], [(string)$tier, (string)$data['max_size']], $line);
        }
        $rod->setLore($lore);
        
        $nbt = $rod->getNamedTag();
        $nbt->setInt("tier", $tier);
        $rod->setNamedTag($nbt);
        if ($player->getInventory()->canAddItem($rod)) {
            $player->getInventory()->addItem($rod);
            $sender->sendMessage($this->plugin->getMessage("give_success", ["tier" => $tier, "player" => $player->getName()]));
        } else {
            $sender->sendMessage($this->plugin->getMessage("item_full"));
        }
        return true;
    }
}
