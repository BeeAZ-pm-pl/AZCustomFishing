# AZFishing

A comprehensive custom fishing plugin for PocketMine-MP 5 (PMMP) servers.

## Features

* 🎣 **Tiered Fishing Rods**: Supports multiple fishing rod tiers (e.g. wooden, iron, diamond) with customizable line snapping chances, maximum fish sizes, and wait times.
* 🐟 **Randomized Fish System**: Manage detailed fish lists in `fish.json` (fish name, random length, base price).
* 🏆 **Leaderboards**: Store and display the top 10 anglers with the largest caught fish using asynchronous SQLite queries to prevent server lag.
* 💰 **Multi-Economy Integration**:
  * AZEconomy
  * SimpleEconomy
  * EconomyAPI
  * BedrockEconomy
* ⏰ **Periodic Fishing Events**: Automate competitive fishing tournaments for players to compete and win rewards.
* 🖥️ **Friendly UI Menus**: Clean UI forms (`pmforms`) allowing players to buy fishing rods, sell caught fish, and view leaderboards dynamically.

## Commands & Permissions

| Command | Description | Default Role | Permission Node |
|---------|-------------|--------------|-----------------|
| `/fishing` | Opens the main fishing UI menu (Sell fish, Buy rod, Top list) | Everyone (`true`) | `azfishing.command.user` |
| `/givefishing <player> <tier>` | Gives a specific tier fishing rod to a player | OP (`op`) | `azfishing.command.admin` |

*Note: The `/fishing` command has `/fishingrod` registered as an alias.*

## Configuration

The main configuration is located at `plugin_data/AZFishing/config.yml`. You can configure:

* **economy**: Select your preferred economy provider (`SimpleEconomy`, `AZEconomy`, `EconomyAPI`, or `BedrockEconomy`).
* **settings.night_multiplier**: Adjust the bite time multiplier for fishing during the night.
* **event**: Toggle, set intervals, and configure durations for automatic fishing events.
* **rods**: Customize the name, price, wait ticks, snap chance, and max fish size for each tier.
* **messages**: Easily translate and modify all messages sent to players (supports Minecraft color formatting).

## Developer Info

* **Author**: BeeAZ
* **API Version**: PocketMine-MP API 5.0.0
