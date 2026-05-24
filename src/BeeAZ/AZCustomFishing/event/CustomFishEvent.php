<?php

declare(strict_types=1);

namespace BeeAZ\AZCustomFishing\event;

use pocketmine\event\Event;
use pocketmine\event\Cancellable;
use pocketmine\event\CancellableTrait;
use pocketmine\player\Player;

class CustomFishEvent extends Event implements Cancellable
{
    use CancellableTrait;

    private array $loot;
    private int $exp;

    public function __construct(private Player $player, array $loot, int $exp) {
        $this->loot = $loot;
        $this->exp = $exp;
    }

    public function getPlayer(): Player
    {
        return $this->player;
    }

    public function getLoot(): array
    {
        return $this->loot;
    }

    public function setLoot(array $loot): void
    {
        $this->loot = $loot;
    }

    public function getExp(): int
    {
        return $this->exp;
    }

    public function setExp(int $exp): void
    {
        $this->exp = $exp;
    }
}
