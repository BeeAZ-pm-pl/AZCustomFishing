<?php

declare(strict_types=1);

namespace BeeAZ\AZCustomFishing\utils;

use pocketmine\player\Player;
use BeeAZ\AZCustomFishing\Main;
use NhanAZ\SimpleEconomy\Main as SimpleEconomy;
use onebone\economyapi\EconomyAPI;
use cooldogedev\BedrockEconomy\api\BedrockEconomyAPI;

class EconomyManager {
    public static function getProvider(): string {
        return Main::getInstance()->getConfig()->get("economy", "SimpleEconomy");
    }

    public static function getMoney(Player $player, \Closure $callback): void {
        $provider = self::getProvider();
        if ($provider === "SimpleEconomy") {
            $callback(SimpleEconomy::getInstance()->getMoney($player->getName()) ?? 0);
        } elseif ($provider === "EconomyAPI") {
            $callback(EconomyAPI::getInstance()->myMoney($player));
        } elseif ($provider === "BedrockEconomy") {
            BedrockEconomyAPI::CLOSURE()->get(
                $player->getXuid(), $player->getName(),
                function(array $res) use ($callback) { $callback($res["amount"]); },
                function(\Exception $e) use ($callback) { $callback(0); }
            );
        } else {
            $callback(0);
        }
    }

    public static function addMoney(Player $player, int $amount, \Closure $callback = null): void {
        $provider = self::getProvider();
        if ($provider === "SimpleEconomy") {
            SimpleEconomy::getInstance()->addMoney($player->getName(), $amount);
            if ($callback !== null) $callback(true);
        } elseif ($provider === "EconomyAPI") {
            EconomyAPI::getInstance()->addMoney($player, $amount);
            if ($callback !== null) $callback(true);
        } elseif ($provider === "BedrockEconomy") {
            BedrockEconomyAPI::CLOSURE()->add(
                $player->getXuid(), $player->getName(), $amount, 0,
                function() use ($callback) { if ($callback !== null) $callback(true); },
                function(\Exception $e) use ($callback) { if ($callback !== null) $callback(false); }
            );
        } else {
            if ($callback !== null) $callback(false);
        }
    }

    public static function reduceMoney(Player $player, int $amount, \Closure $callback): void {
        $provider = self::getProvider();
        if ($provider === "SimpleEconomy") {
            $res = SimpleEconomy::getInstance()->reduceMoney($player->getName(), $amount);
            $callback($res);
        } elseif ($provider === "EconomyAPI") {
            $res = EconomyAPI::getInstance()->reduceMoney($player, $amount) === EconomyAPI::RET_SUCCESS;
            $callback($res);
        } elseif ($provider === "BedrockEconomy") {
            BedrockEconomyAPI::CLOSURE()->subtract(
                $player->getXuid(), $player->getName(), $amount, 0,
                function() use ($callback) { $callback(true); },
                function(\Exception $e) use ($callback) { $callback(false); }
            );
        } else {
            $callback(false);
        }
    }
}
