<?php

declare(strict_types=1);

namespace BeeAZ\AZCustomFishing\utils;

use BeeAZ\AZCustomFishing\Main;

class FishManager {
    public static function getRodInfo(int $tier): array {
        return Main::getInstance()->getConfig()->get("rods", [])[$tier] ?? ["name" => "Basic Fishing Rod", "price" => 1000, "wait_min" => 100, "wait_max" => 200, "break_chance" => 5, "max_size" => 50];
    }
    public static function getRandomFish(int $tier): array {
        $fishList = Main::getInstance()->fishConfig->get((string)$tier, []);
        if (empty($fishList)) $fishList = ["default" => ["name" => "Trash Fish", "base_length" => 5.0, "base_price" => 5]];
        $keys = array_keys($fishList);
        $fishData = $fishList[$keys[array_rand($keys)]];
        $maxSizeRod = self::getRodInfo($tier)['max_size'];
        $baseLen = $fishData['base_length'];
        
        $min = (int)($baseLen * 5);
        $max = (int)($maxSizeRod * 10);
        if ($max < $min) {
            $max = $min + (int)($baseLen * 5);
        }
        
        $length = round(mt_rand($min, $max) / 10, 1);
        $price = (int)($fishData['base_price'] * ($length / $baseLen));
        return ["name" => $fishData['name'], "length" => $length, "price" => $price];
    }
}
