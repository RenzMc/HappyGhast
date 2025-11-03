<?php

declare(strict_types=1);

namespace Renz\HappyGhast\entity;

use pocketmine\entity\EntitySizeInfo;
use pocketmine\entity\Living;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\entity\EntityIds;
use pocketmine\player\Player;

class HappyGhastEntity extends Living {

        private const MAX_PASSENGERS = 4;
        private array $passengers = [];

        public static function getNetworkTypeId(): string {
                return EntityIds::HAPPY_GHAST;
        }

        public function getName(): string {
                return "Happy Ghast";
        }

        protected function getInitialSizeInfo(): EntitySizeInfo {
                return new EntitySizeInfo(4.0, 4.0);
        }

        protected function initEntity(CompoundTag $nbt): void {
                parent::initEntity($nbt);
                $this->setMaxHealth(20);
                $this->setHealth(20);
                $this->setNameTag("§bHappy Ghast");
                $this->setNameTagAlwaysVisible(true);
                $this->setScale(1.0);
        }

        public function addPassenger(Player $player): bool {
                if (count($this->passengers) >= self::MAX_PASSENGERS) {
                        return false;
                }
                
                if (!in_array($player->getId(), $this->passengers, true)) {
                        $this->passengers[] = $player->getId();
                        return true;
                }
                
                return false;
        }

        public function removePassenger(Player $player): bool {
                $key = array_search($player->getId(), $this->passengers, true);
                if ($key !== false) {
                        unset($this->passengers[$key]);
                        $this->passengers = array_values($this->passengers);
                        return true;
                }
                return false;
        }

        public function getPassengerIds(): array {
                return $this->passengers;
        }

        public function getPassengerCount(): int {
                return count($this->passengers);
        }

        public function hasPassenger(Player $player): bool {
                return in_array($player->getId(), $this->passengers, true);
        }

        public function clearPassengers(): void {
                $this->passengers = [];
        }

        protected function entityBaseTick(int $tickDiff = 1): bool {
                $hasUpdate = parent::entityBaseTick($tickDiff);
                
                if ($this->getWorld()->getBlockAt(
                        (int) $this->location->x,
                        (int) ($this->location->y - 1),
                        (int) $this->location->z
                )->isSolid()) {
                        $this->motion = $this->motion->addVector(0, 0.05, 0);
                        $hasUpdate = true;
                }
                
                return $hasUpdate;
        }

        public function onUpdate(int $currentTick): bool {
                if ($this->closed) {
                        return false;
                }
                
                foreach ($this->passengers as $key => $passengerId) {
                        $passenger = $this->getWorld()->getEntity($passengerId);
                        if (!$passenger instanceof Player || !$passenger->isAlive()) {
                                unset($this->passengers[$key]);
                                $this->passengers = array_values($this->passengers);
                        }
                }
                
                return parent::onUpdate($currentTick);
        }
}
