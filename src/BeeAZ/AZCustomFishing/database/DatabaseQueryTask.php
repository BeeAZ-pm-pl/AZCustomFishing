<?php

declare(strict_types=1);

namespace BeeAZ\AZCustomFishing\database;

use pocketmine\scheduler\AsyncTask;
use BeeAZ\AZCustomFishing\Main;
use function serialize;
use function unserialize;

class DatabaseQueryTask extends AsyncTask {
    private string $serializedParams;

    public function __construct(
        private string $dbPath,
        private string $action,
        array $params
    ) {
        $this->serializedParams = serialize($params);
    }

    public function onRun() : void {
        $db = new \SQLite3($this->dbPath);
        $db->exec("PRAGMA foreign_keys = ON;");
        $db->busyTimeout(5000);

        $result = null;
        $params = unserialize($this->serializedParams);

        switch ($this->action) {
            case "get_player":
                $name = $params["name"];
                $stmt = $db->prepare("SELECT * FROM fishing WHERE username = :name");
                $stmt->bindValue(":name", $name, SQLITE3_TEXT);
                $res = $stmt->execute();
                if ($res && $row = $res->fetchArray(SQLITE3_ASSOC)) {
                    $result = $row;
                } else {
                    $result = ["username" => $name, "points" => 0, "max_size" => 0.0, "max_fish" => "Unknown", "catch_time" => 0];
                }
                break;

            case "add_points":
                $name = $params["name"];
                $points = $params["points"];

                $stmt = $db->prepare("SELECT points FROM fishing WHERE username = :name");
                $stmt->bindValue(":name", $name, SQLITE3_TEXT);
                $res = $stmt->execute();
                
                if ($res && $row = $res->fetchArray(SQLITE3_ASSOC)) {
                    $stmt = $db->prepare("UPDATE fishing SET points = points + :points WHERE username = :name");
                    $stmt->bindValue(":name", $name, SQLITE3_TEXT);
                    $stmt->bindValue(":points", $points, SQLITE3_INTEGER);
                    $stmt->execute();
                } else {
                    $stmt = $db->prepare("INSERT INTO fishing (username, points, max_size, max_fish, catch_time) VALUES (:name, :points, 0.0, 'Unknown', 0)");
                    $stmt->bindValue(":name", $name, SQLITE3_TEXT);
                    $stmt->bindValue(":points", $points, SQLITE3_INTEGER);
                    $stmt->execute();
                }
                break;

            case "update_max_fish":
                $name = $params["name"];
                $fishName = $params["fish_name"];
                $size = $params["size"];
                $time = $params["time"];

                $stmt = $db->prepare("SELECT max_size FROM fishing WHERE username = :name");
                $stmt->bindValue(":name", $name, SQLITE3_TEXT);
                $res = $stmt->execute();
                
                if ($res && $row = $res->fetchArray(SQLITE3_ASSOC)) {
                    if ($size > $row['max_size']) {
                        $stmt = $db->prepare("UPDATE fishing SET max_size = :size, max_fish = :fish, catch_time = :time WHERE username = :name");
                        $stmt->bindValue(":name", $name, SQLITE3_TEXT);
                        $stmt->bindValue(":size", $size, SQLITE3_FLOAT);
                        $stmt->bindValue(":fish", $fishName, SQLITE3_TEXT);
                        $stmt->bindValue(":time", $time, SQLITE3_INTEGER);
                        $stmt->execute();
                    }
                } else {
                    $stmt = $db->prepare("INSERT INTO fishing (username, points, max_size, max_fish, catch_time) VALUES (:name, 0, :size, :fish, :time)");
                    $stmt->bindValue(":name", $name, SQLITE3_TEXT);
                    $stmt->bindValue(":size", $size, SQLITE3_FLOAT);
                    $stmt->bindValue(":fish", $fishName, SQLITE3_TEXT);
                    $stmt->bindValue(":time", $time, SQLITE3_INTEGER);
                    $stmt->execute();
                }
                break;

            case "get_top":
                $res = $db->query("SELECT * FROM fishing ORDER BY max_size DESC LIMIT 10");
                $list = [];
                if ($res) {
                    while ($row = $res->fetchArray(SQLITE3_ASSOC)) {
                        $list[] = $row;
                    }
                }
                $result = $list;
                break;
        }

        $db->close();
        $this->setResult($result);
    }

    public function onCompletion() : void {
        $plugin = Main::getInstance();
        if ($plugin !== null && $plugin->isEnabled()) {
            $params = unserialize($this->serializedParams);
            $plugin->db->handleCompletion($this->action, $params, $this->getResult());
        }
    }
}
