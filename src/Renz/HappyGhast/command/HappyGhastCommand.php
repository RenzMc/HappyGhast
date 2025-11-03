<?php

declare(strict_types=1);

namespace Renz\HappyGhast\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use Renz\HappyGhast\Main;
use Renz\HappyGhast\easyform\EasyForm;
use Renz\HappyGhast\entity\HappyGhastEntity;

class HappyGhastCommand extends Command implements PluginOwned {

	private Main $plugin;

	public function __construct(Main $plugin) {
		parent::__construct(
			"happyghast",
			"Happy Ghast management menu",
			"/happyghast"
		);
		$this->setPermission("happyghast.command");
		$this->setAliases(["hg"]);
		$this->plugin = $plugin;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args): void {
		if (!$this->testPermission($sender)) {
			return;
		}

		if (!$sender instanceof Player) {
			$sender->sendMessage("§cThis command can only be used in-game!");
			return;
		}

		$form = EasyForm::simple("§bHappy Ghast Manager")
			->button("§aSpawn Happy Ghast\n§7Tap to spawn", null, function($player) {
				$entity = new HappyGhastEntity($player->getLocation());
				$entity->spawnToAll();
				$player->sendMessage("§aHappy Ghast spawned!");
			})
			->button("§c" . ($this->plugin->getRidingManager()->isInDespawnMode($sender) ? "Disable" : "Enable") . " Despawn Mode\n§7" . ($this->plugin->getRidingManager()->isInDespawnMode($sender) ? "Currently: ON" : "Currently: OFF"), null, function($player) {
				$current = $this->plugin->getRidingManager()->isInDespawnMode($player);
				$this->plugin->getRidingManager()->setDespawnMode($player, !$current);
				$player->sendMessage($current ? "§cDespawn mode disabled" : "§aDespawn mode enabled - Attack a Happy Ghast to despawn it");
			});

		EasyForm::send($sender, $form);
	}

	public function getOwningPlugin(): Plugin {
		return $this->plugin;
	}
}
