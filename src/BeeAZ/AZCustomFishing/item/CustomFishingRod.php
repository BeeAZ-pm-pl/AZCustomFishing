<?php

declare(strict_types=1);

namespace BeeAZ\AZCustomFishing\item;

use pocketmine\item\FishingRod;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemTypeIds;

class CustomFishingRod extends FishingRod
{
    public function __construct()
    {
        parent::__construct(new ItemIdentifier(ItemTypeIds::FISHING_ROD), "Fishing Rod");
    }
    public function getTier(): int
    {
        $nbt = $this->getNamedTag();
        return $nbt->getTag("tier") !== null ? $nbt->getInt("tier") : 1;
    }
}
