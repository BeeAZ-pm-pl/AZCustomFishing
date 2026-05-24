<?php

declare(strict_types=1);

namespace BeeAZ\AZCustomFishing\database;

use BeeAZ\AZCustomFishing\Main;

class Database {
    private string $dbPath;
    private array $callbacks = [];
    private int $nextId = 0;

    public function __construct(Main $plugin) {
        $this->dbPath = $plugin->getDataFolder() . "custom_fishing.db";
        $db = new \SQLite3($this->dbPath);
        $db->exec("CREATE TABLE IF NOT EXISTS fishing (username TEXT PRIMARY KEY, points INT, max_size FLOAT, max_fish TEXT, catch_time INTEGER DEFAULT 0)");
        @$db->exec("ALTER TABLE fishing ADD COLUMN catch_time INTEGER DEFAULT 0");
        $db->close();
    }

    public function getPlayerData(string $name, \Closure $callback): void {
        $id = $this->nextId++;
        $this->callbacks[$id] = $callback;
        Main::getInstance()->getServer()->getAsyncPool()->submitTask(new DatabaseQueryTask(
            $this->dbPath,
            "get_player",
            ["name" => strtolower($name), "requestId" => $id]
        ));
    }

    public function addPoints(string $name, int $points): void {
        Main::getInstance()->getServer()->getAsyncPool()->submitTask(new DatabaseQueryTask(
            $this->dbPath,
            "add_points",
            ["name" => strtolower($name), "points" => $points]
        ));
    }

    public function updateMaxFish(string $name, string $fishName, float $size, int $time): void {
        Main::getInstance()->getServer()->getAsyncPool()->submitTask(new DatabaseQueryTask(
            $this->dbPath,
            "update_max_fish",
            ["name" => strtolower($name), "fish_name" => $fishName, "size" => $size, "time" => $time]
        ));
    }

    public function getTop10(\Closure $callback): void {
        $id = $this->nextId++;
        $this->callbacks[$id] = $callback;
        Main::getInstance()->getServer()->getAsyncPool()->submitTask(new DatabaseQueryTask(
            $this->dbPath,
            "get_top",
            ["requestId" => $id]
        ));
    }

    public function handleCompletion(string $action, array $params, $result): void {
        $id = $params["requestId"] ?? null;
        if ($id !== null && isset($this->callbacks[$id])) {
            $callback = $this->callbacks[$id];
            unset($this->callbacks[$id]);
            $callback($result);
        }
    }
}
