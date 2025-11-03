<?php

declare(strict_types=1);

namespace Renz\HappyGhast\manager;

use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\player\PlayerEntityInteractEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\PlayerAuthInputPacket;
use pocketmine\network\mcpe\protocol\types\PlayerAuthInputFlags;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityLink;
use pocketmine\player\Player;
use pocketmine\math\Vector3;
use Renz\HappyGhast\entity\HappyGhastEntity;
use Renz\HappyGhast\Main;

class RidingManager implements Listener {

    private Main $plugin;
    private array $despawnMode = [];
    private array $riding = [];

    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
    }

    public function isInDespawnMode(Player $player): bool {
        return isset($this->despawnMode[$player->getName()]);
    }

    public function setDespawnMode(Player $player, bool $enabled): void {
        if ($enabled) {
            $this->despawnMode[$player->getName()] = true;
        } else {
            unset($this->despawnMode[$player->getName()]);
        }
    }

    public function onPlayerEntityInteract(PlayerEntityInteractEvent $event): void {
        $player = $event->getPlayer();
        $entity = $event->getEntity();
        if (!$entity instanceof HappyGhastEntity) {
            return;
        }
        if ($this->isRiding($player)) {
            $this->dismount($player);
            return;
        }
        if ($entity->getPassengerCount() >= 4) {
            $player->sendMessage("§cThis Happy Ghast is full! (4/4 passengers)");
            return;
        }
        if ($this->mount($player, $entity)) {
            $position = $entity->getPassengerCount();
            $player->sendMessage("§aYou are now riding the Happy Ghast! (Position: $position/4)");
        }
    }

    private function mount(Player $player, HappyGhastEntity $entity): bool {
        if (!$entity->addPassenger($player)) {
            return false;
        }
        $this->riding[$player->getName()] = $entity->getId();
        $link = new EntityLink(
            $entity->getId(),
            $player->getId(),
            EntityLink::TYPE_RIDER,
            true,
            false,
            0.0
        );
        $packet = SetActorLinkPacket::create($link);
        foreach ($entity->getWorld()->getPlayers() as $viewer) {
            $viewer->getNetworkSession()->sendDataPacket($packet);
        }
        return true;
    }

    public function dismount(Player $player): void {
        if (!isset($this->riding[$player->getName()])) {
            return;
        }
        $entityId = $this->riding[$player->getName()];
        $entity = $player->getWorld()->getEntity($entityId);
        if ($entity instanceof HappyGhastEntity) {
            $entity->removePassenger($player);
            $link = new EntityLink(
                $entity->getId(),
                $player->getId(),
                EntityLink::TYPE_REMOVE,
                true,
                false,
                0.0
            );
            $packet = SetActorLinkPacket::create($link);
            foreach ($entity->getWorld()->getPlayers() as $viewer) {
                $viewer->getNetworkSession()->sendDataPacket($packet);
            }
        }
        unset($this->riding[$player->getName()]);
        $player->sendMessage("§aYou dismounted from the Happy Ghast!");
    }

    public function onPlayerToggleSneak(PlayerToggleSneakEvent $event): void {
        $player = $event->getPlayer();
        if (!$this->isRiding($player)) {
            return;
        }
        return;
    }

    public function onDataPacketReceive(DataPacketReceiveEvent $event): void {
        $packet = $event->getPacket();
        if (!$packet instanceof PlayerAuthInputPacket) {
            return;
        }
        $origin = $event->getOrigin();
        $player = $origin->getPlayer();
        if (!$player instanceof Player) {
            return;
        }
        if (!$this->isRiding($player)) {
            return;
        }
        $entityId = $this->riding[$player->getName()] ?? null;
        if ($entityId === null) {
            return;
        }
        $entity = $player->getWorld()->getEntity($entityId);
        if (!$entity instanceof HappyGhastEntity) {
            $this->dismount($player);
            return;
        }
        $moveX = $packet->getMoveVecX();
        $moveZ = $packet->getMoveVecZ();
        $flags = $packet->getInputFlags();
        $isStartJump = $flags->get(PlayerAuthInputFlags::START_JUMPING) || $flags->get(PlayerAuthInputFlags::NORTH_JUMP);
        $wantsDown = $flags->get(PlayerAuthInputFlags::WANT_DOWN) || $flags->get(PlayerAuthInputFlags::SNEAK_DOWN) || $flags->get(PlayerAuthInputFlags::SNEAKING);
        $passengers = $entity->getPassengerIds();
        $isDriver = isset($passengers[0]) && $passengers[0] === $player->getId();
        $entityHeight = $entity->getSize()->getHeight();
        $index = array_search($player->getId(), $passengers, true);
        $index = ($index === false) ? 0 : (int)$index;
        $passengerStackOffset = $index * 0.3;
        $topMargin = 0.6;
        $yOffset = $entityHeight + $topMargin + $passengerStackOffset;
        if (!$isDriver) {
            $targetPos = $entity->getLocation()->add(0, $yOffset, 0);
            if ($player->getLocation()->distance($targetPos) > 0.6) {
                $player->teleport($targetPos);
            }
            return;
        }
        $analog = sqrt($moveX * $moveX + $moveZ * $moveZ);
        $directionVector = $player->getDirectionVector();
        $maxSpeed = 0.6;
        $horizontal = $directionVector->multiply($maxSpeed * $analog);
        if ($analog < 0.05) {
            $horizontal = new Vector3(0.0, 0.0, 0.0);
        }
        $vertical = 0.0;
        if ($isStartJump) {
            $vertical = 0.35;
        } elseif ($wantsDown) {
            $vertical = -0.45;
        } else {
            $vertical = 0.0;
        }
        $newMotion = $horizontal->addVector(new Vector3(0.0, $vertical, 0.0));
        $entity->setMotion($newMotion);
        $targetPos = $entity->getLocation()->add(0, $yOffset, 0);
        if ($player->getLocation()->distance($targetPos) > 0.6) {
            $player->teleport($targetPos);
        }
    }

    public function onPlayerQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        if ($this->isRiding($player)) {
            $this->dismount($player);
        }
        unset($this->despawnMode[$player->getName()]);
    }

    public function onEntityDamage(EntityDamageEvent $event): void {
        $entity = $event->getEntity();
        if ($entity instanceof Player && $this->isRiding($entity)) {
            $event->cancel();
        }
    }

    public function onEntityTeleport(EntityTeleportEvent $event): void {
        $entity = $event->getEntity();
        if ($entity instanceof HappyGhastEntity) {
            foreach ($entity->getPassengerIds() as $passengerId) {
                $passenger = $entity->getWorld()->getEntity($passengerId);
                if ($passenger instanceof Player) {
                    $link = new EntityLink(
                        $entity->getId(),
                        $passenger->getId(),
                        EntityLink::TYPE_RIDER,
                        true,
                        false,
                        0.0
                    );
                    $packet = SetActorLinkPacket::create($link);
                    foreach ($entity->getWorld()->getPlayers() as $viewer) {
                        $viewer->getNetworkSession()->sendDataPacket($packet);
                    }
                }
            }
        }
    }

    public function isRiding(Player $player): bool {
        return isset($this->riding[$player->getName()]);
    }

    public function removeAllPassengers(HappyGhastEntity $entity): void {
        foreach ($entity->getPassengerIds() as $passengerId) {
            $passenger = $entity->getWorld()->getEntity($passengerId);
            if ($passenger instanceof Player) {
                $link = new EntityLink(
                    $entity->getId(),
                    $passenger->getId(),
                    EntityLink::TYPE_REMOVE,
                    true,
                    false,
                    0.0
                );
                $packet = SetActorLinkPacket::create($link);
                foreach ($entity->getWorld()->getPlayers() as $viewer) {
                    $viewer->getNetworkSession()->sendDataPacket($packet);
                }
                if (isset($this->riding[$passenger->getName()])) {
                    unset($this->riding[$passenger->getName()]);
                }
            }
        }
        $entity->clearPassengers();
    }
}