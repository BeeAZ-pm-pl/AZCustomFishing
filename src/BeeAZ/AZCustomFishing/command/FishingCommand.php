<?php

declare(strict_types=1);

namespace BeeAZ\AZCustomFishing\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\PluginOwned;
use pocketmine\plugin\PluginOwnedTrait;
use BeeAZ\AZCustomFishing\Main;
use BeeAZ\AZCustomFishing\form\FishingForm;

class FishingCommand extends Command implements PluginOwned {
    use PluginOwnedTrait {
        PluginOwnedTrait::__construct as private __traitConstruct;
    }

    public function __construct(private Main $plugin) {
        $this->__traitConstruct($plugin);
        parent::__construct("fishing", "Open fishing menu", "/fishing", ["fishingrod"]);
        $this->setPermission("azcustomfishing.command.user");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if ($sender instanceof Player) {
            FishingForm::sendMenu($sender);
            return true;
        }
        return false;
    }
}
