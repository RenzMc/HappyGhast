<?php

declare(strict_types=1);

namespace Renz\HappyGhast\command;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginOwned;
use Renz\HappyGhast\Main;

class GhastDismountCommand extends Command implements PluginOwned {

	private Main $plugin;

	public function __construct(Main $plugin) {
		parent::__construct("ghastdismount", "Dismount from Happy Ghast", "/ghastdismount");
		$this->setPermission("happyghast.dismount");
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

		$ridingManager = $this->plugin->getRidingManager();
		if (!$ridingManager->isRiding($sender)) {
			$sender->sendMessage("§cYou are not riding a Happy Ghast.");
			return;
		}

		$ridingManager->dismount($sender);
	}

	public function getOwningPlugin(): Plugin {
		return $this->plugin;
	}
}