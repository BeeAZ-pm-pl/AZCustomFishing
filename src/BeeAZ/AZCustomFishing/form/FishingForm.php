<?php

declare(strict_types=1);

namespace BeeAZ\AZCustomFishing\form;

use dktapps\pmforms\MenuForm;
use dktapps\pmforms\MenuOption;
use dktapps\pmforms\FormIcon;
use pocketmine\player\Player;
use BeeAZ\AZCustomFishing\Main;
use pocketmine\item\VanillaItems;
use BeeAZ\AZCustomFishing\utils\EconomyManager;

class FishingForm {
    public static function sendMenu(Player $player): void {
        $cfg = Main::getInstance()->getConfig();
        $options = [];
        $actions = [];
        if ($cfg->getNested("ui.main.btn_sell.enabled", true)) {
            $options[] = new MenuOption($cfg->getNested("ui.main.btn_sell.text"), new FormIcon($cfg->getNested("ui.main.btn_sell.icon"), FormIcon::IMAGE_TYPE_PATH));
            $actions[] = "sell";
        }
        if ($cfg->getNested("ui.main.btn_buy.enabled", true)) {
            $options[] = new MenuOption($cfg->getNested("ui.main.btn_buy.text"), new FormIcon($cfg->getNested("ui.main.btn_buy.icon"), FormIcon::IMAGE_TYPE_PATH));
            $actions[] = "buy";
        }
        if ($cfg->getNested("ui.main.btn_stats.enabled", true)) {
            $options[] = new MenuOption($cfg->getNested("ui.main.btn_stats.text"), new FormIcon($cfg->getNested("ui.main.btn_stats.icon"), FormIcon::IMAGE_TYPE_PATH));
            $actions[] = "stats";
        }

        $form = new MenuForm(
            $cfg->getNested("ui.main.title"),
            $cfg->getNested("ui.main.content"),
            $options,
            function (Player $submitter, int $selected) use ($actions): void {
                $act = $actions[$selected] ?? null;
                if ($act === "sell") self::sellFish($submitter);
                elseif ($act === "buy") self::buyRodMenu($submitter);
                elseif ($act === "stats") self::statsMenu($submitter);
            }
        );
        $player->sendForm($form);
    }

    private static function sellFish(Player $player): void {
        $plugin = Main::getInstance();
        $inv = $player->getInventory();
        $total = 0;
        $itemsToRemove = [];

        foreach ($inv->getContents() as $slot => $item) {
            $nbt = $item->getNamedTag();
            if ($nbt->getTag("fish_price") !== null) {
                $price = $nbt->getInt("fish_price");
                $total += ($price * $item->getCount());
                $itemsToRemove[] = $item;
            }
        }

        if (count($itemsToRemove) === 0) {
            $player->sendMessage($plugin->getMessage("sell_empty"));
            return;
        }

        $inv->removeItem(...$itemsToRemove);
        EconomyManager::addMoney($player, $total, function(bool $success) use ($player, $plugin, $total) {
            if ($success) $player->sendMessage($plugin->getMessage("sell_success", ["money" => number_format($total)]));
        });
    }

    private static function buyRodMenu(Player $player): void {
        $plugin = Main::getInstance();
        $rods = $plugin->getConfig()->get("rods", []);
        $options = [];
        $tiers = [];
        $format = $plugin->getConfig()->getNested("ui.shop.btn_format", "§0⚡ §l{name} §r§0⚡\n§8Price: {price}$");

        foreach ($rods as $tier => $data) {
            $iconType = isset($data['icon']['type']) && $data['icon']['type'] === 'url' ? FormIcon::IMAGE_TYPE_URL : FormIcon::IMAGE_TYPE_PATH;
            $icon = new FormIcon($data['icon']['data'] ?? "textures/items/fishing_rod", $iconType);
            $text = str_replace(["{name}", "{price}"], [$data['name'], number_format($data['price'])], $format);
            $options[] = new MenuOption($text, $icon);
            $tiers[] = $tier;
        }

        $form = new MenuForm(
            $plugin->getConfig()->getNested("ui.shop.title"),
            $plugin->getConfig()->getNested("ui.shop.content"),
            $options,
            function (Player $submitter, int $selected) use ($tiers): void {
                self::previewRod($submitter, $tiers[$selected]);
            }
        );
        $player->sendForm($form);
    }

    private static function previewRod(Player $player, int $tier): void {
        $plugin = Main::getInstance();
        $data = $plugin->getConfig()->get("rods", [])[$tier];
        $cfg = $plugin->getConfig();
        
        $content = $cfg->getNested("ui.preview.content");
        $content = str_replace(
            ["{name}", "{price}", "{tier}", "{wait_min}", "{wait_max}", "{break_chance}", "{max_size}"],
            [(string)$data['name'], number_format($data['price']), (string)$tier, (string)($data['wait_min']/20), (string)($data['wait_max']/20), (string)$data['break_chance'], (string)$data['max_size']],
            $content
        );

        $options = [
            new MenuOption($cfg->getNested("ui.preview.btn_buy.text"), new FormIcon($cfg->getNested("ui.preview.btn_buy.icon"), FormIcon::IMAGE_TYPE_PATH)),
            new MenuOption($cfg->getNested("ui.preview.btn_back.text"), new FormIcon($cfg->getNested("ui.preview.btn_back.icon"), FormIcon::IMAGE_TYPE_PATH))
        ];

        $form = new MenuForm(
            $cfg->getNested("ui.preview.title"),
            $content,
            $options,
            function (Player $submitter, int $selected) use ($tier, $data, $plugin): void {
                if ($selected === 0) {
                    EconomyManager::getMoney($submitter, function(float|int $balance) use ($submitter, $tier, $data, $plugin) {
                        if ($balance >= $data['price']) {
                            EconomyManager::reduceMoney($submitter, $data['price'], function(bool $success) use ($submitter, $tier, $data, $plugin) {
                                if ($success) {
                                    $rod = VanillaItems::FISHING_ROD();
                                    $rod->setCustomName($data['name']);
                                    
                                    $loreConfig = $plugin->getConfig()->getNested("items.rod_lore", []);
                                    $lore = [];
                                    foreach ($loreConfig as $line) {
                                        $lore[] = str_replace(["{tier}", "{max_size}"], [(string)$tier, (string)$data['max_size']], $line);
                                    }
                                    $rod->setLore($lore);
                                    
                                    $nbt = $rod->getNamedTag();
                                    $nbt->setInt("tier", $tier);
                                    $rod->setNamedTag($nbt);
                                    if ($submitter->getInventory()->canAddItem($rod)) $submitter->getInventory()->addItem($rod);
                                    else $submitter->getWorld()->dropItem($submitter->getPosition(), $rod);
                                    $submitter->sendMessage($plugin->getMessage("buy_success", ["item" => $data['name'], "price" => number_format($data['price'])]));
                                } else {
                                    $submitter->sendMessage($plugin->getMessage("buy_fail"));
                                }
                            });
                        } else {
                            $submitter->sendMessage($plugin->getMessage("buy_fail"));
                        }
                    });
                } elseif ($selected === 1) {
                    self::buyRodMenu($submitter);
                }
            }
        );
        $player->sendForm($form);
    }

    private static function statsMenu(Player $player): void {
        $plugin = Main::getInstance();
        $cfg = $plugin->getConfig();
        $dateFormat = $cfg->getNested("settings.date_format", "d/m/Y H:i:s");
        
        $plugin->db->getTop10(function(array $topData) use ($player, $plugin, $cfg, $dateFormat) {
            if (!$player->isOnline()) return;

            $listStr = "";
            $rank = 1;
            $format = $cfg->getNested("ui.top.line_format");
            
            foreach ($topData as $row) {
                if ($row['max_size'] <= 0) continue;
                $timeStr = $row['catch_time'] > 0 ? date($dateFormat, $row['catch_time']) : "Unknown";
                $line = str_replace(
                    ["{top}", "{player}", "{fish}", "{size}", "{time}"],
                    [(string)$rank, (string)$row['username'], (string)$row['max_fish'], (string)$row['max_size'], (string)$timeStr],
                    $format
                );
                $listStr .= $line . "\n\n";
                $rank++;
            }
            
            if ($listStr === "") $listStr = $cfg->getNested("ui.top.empty");
            
            $content = str_replace("{list}", $listStr, $cfg->getNested("ui.top.content"));
            
            $form = new MenuForm(
                $cfg->getNested("ui.top.title"),
                $content,
                [new MenuOption($cfg->getNested("ui.top.btn_close.text"), new FormIcon($cfg->getNested("ui.top.btn_close.icon"), FormIcon::IMAGE_TYPE_PATH))],
                function (Player $submitter, int $selected): void {}
            );
            $submitter->sendForm($form);
        });
    }
}
