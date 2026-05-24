<?php

declare(strict_types=1);

namespace BeeAZ\AZCustomFishing;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\item\FishingRod;
use pocketmine\entity\Location;
use pocketmine\world\sound\ThrowSound;
use BeeAZ\AZCustomFishing\entity\CustomHook;

class EventListener implements Listener
{
    public function onUseRod(PlayerItemUseEvent $ev): void
    {
        $player = $ev->getPlayer();
        $item = $ev->getItem();
        if ($item instanceof FishingRod) {
            $ev->cancel();
            $name = $player->getName();
            $tier = $item->getNamedTag()->getTag("tier") !== null ? $item->getNamedTag()->getInt("tier") : 1;
            $session = Main::getInstance()->fishingSession;

            if (isset($session[$name])) {
                $hook = $player->getWorld()->getEntity($session[$name]);
                if ($hook instanceof CustomHook && !$hook->isClosed()) {
                    $hook->reelLine($tier);
                } else {
                    unset(Main::getInstance()->fishingSession[$name]);
                }
                if (!$player->isCreative()) {
                    $item->applyDamage(1);
                    $player->getInventory()->setItemInHand($item);
                }
            } else {
                $loc = Location::fromObject($player->getEyePos(), $player->getWorld(), $player->getLocation()->getYaw(), $player->getLocation()->getPitch());
                $hook = new CustomHook($loc, $player);
                $hook->rodTier = $tier;
                $hook->setMotion($player->getDirectionVector()->multiply(1.6));
                $hook->spawnToAll();
                $player->getWorld()->addSound($player->getPosition(), new ThrowSound());
                Main::getInstance()->fishingSession[$name] = $hook->getId();
                Main::getInstance()->registerParticipant($name);
            }
        }
    }
}
