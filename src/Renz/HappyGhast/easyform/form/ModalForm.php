<?php

declare(strict_types=1);

namespace Renz\HappyGhast\easyform\form;

use pocketmine\form\Form;
use pocketmine\player\Player;
use Closure;

class ModalForm implements Form
{
        private string $title;
        private string $content;
        private string $button1;
        private string $button2;
        private ?Closure $onYes = null;
        private ?Closure $onNo = null;
        private ?Closure $onClose = null;

        public function __construct(string $title, string $content, string $button1 = "Yes", string $button2 = "No")
        {
                $this->title = $title;
                $this->content = $content;
                $this->button1 = $button1;
                $this->button2 = $button2;
        }

        public function onYes(Closure $callback): self
        {
                $this->onYes = $callback;
                return $this;
        }

        public function onNo(Closure $callback): self
        {
                $this->onNo = $callback;
                return $this;
        }

        public function onClose(Closure $callback): self
        {
                $this->onClose = $callback;
                return $this;
        }

        public function handleResponse(Player $player, $data): void
        {
                if ($data === null) {
                        if ($this->onClose !== null) {
                                ($this->onClose)($player);
                        }
                        return;
                }

                if ($data === true && $this->onYes !== null) {
                        ($this->onYes)($player);
                } elseif ($data === false && $this->onNo !== null) {
                        ($this->onNo)($player);
                }
        }

        public function jsonSerialize(): array
        {
                return [
                        "type" => "modal",
                        "title" => $this->title,
                        "content" => $this->content,
                        "button1" => $this->button1,
                        "button2" => $this->button2
                ];
        }
}