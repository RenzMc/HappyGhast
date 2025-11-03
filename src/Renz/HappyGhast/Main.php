<?php

declare(strict_types=1);

namespace Renz\HappyGhast;

use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\world\World;
use Renz\HappyGhast\command\HappyGhastCommand;
use Renz\HappyGhast\entity\HappyGhastEntity;
use Renz\HappyGhast\manager\RidingManager;

class Main extends PluginBase implements Listener {

        private RidingManager $ridingManager;

        protected function onEnable(): void {
                $this->ridingManager = new RidingManager($this);
                $this->getServer()->getPluginManager()->registerEvents($this, $this);
                $this->getServer()->getPluginManager()->registerEvents($this->ridingManager, $this);
                
                $this->getServer()->getCommandMap()->register("happyghast", new HappyGhastCommand($this));
                
                EntityFactory::getInstance()->register(
                        HappyGhastEntity::class,
                        function(World $world, CompoundTag $nbt): HappyGhastEntity {
                                return new HappyGhastEntity(
                                        EntityDataHelper::parseLocation($nbt, $world),
                                        $nbt
                                );
                        },
                        ['HappyGhast']
                );
        }

        public function onEntityDamage(EntityDamageByEntityEvent $event): void {
                $entity = $event->getEntity();
                $damager = $event->getDamager();
                
                if ($entity instanceof HappyGhastEntity && $damager instanceof \pocketmine\player\Player) {
                        if ($this->ridingManager->isInDespawnMode($damager)) {
                                $this->ridingManager->removeAllPassengers($entity);
                                $entity->flagForDespawn();
                                $damager->sendMessage("§aHappy Ghast despawned!");
                                $event->cancel();
                        }
                }
        }

        public function getRidingManager(): RidingManager {
                return $this->ridingManager;
        }
}
