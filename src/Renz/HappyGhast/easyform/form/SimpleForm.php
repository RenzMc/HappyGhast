<?php

declare(strict_types=1);

namespace Renz\HappyGhast\easyform\form;

use pocketmine\form\Form;
use pocketmine\player\Player;
use Closure;

class SimpleForm implements Form
{
        private string $title;
        private string $content;
        private array $buttons = [];
        private ?Closure $onSubmit = null;
        private ?Closure $onClose = null;

        public function __construct(string $title, string $content = "")
        {
                $this->title = $title;
                $this->content = $content;
        }

        public function button(string $text, ?string $icon = null, ?Closure $onClick = null): self
        {
                $button = ["text" => $text];
                if ($icon !== null) {
                        $type = str_starts_with($icon, "http") ? "url" : "path";
                        $button["image"] = ["type" => $type, "data" => $icon];
                }
                $this->buttons[] = ["data" => $button, "onClick" => $onClick];
                return $this;
        }

        public function onSubmit(Closure $callback): self
        {
                $this->onSubmit = $callback;
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

                if (isset($this->buttons[$data]["onClick"])) {
                        $callback = $this->buttons[$data]["onClick"];
                        if ($callback !== null) {
                                $callback($player);
                        }
                }

                if ($this->onSubmit !== null) {
                        ($this->onSubmit)($player, $data);
                }
        }

        public function jsonSerialize(): array
        {
                return [
                        "type" => "form",
                        "title" => $this->title,
                        "content" => $this->content,
                        "buttons" => array_column($this->buttons, "data")
                ];
        }
}