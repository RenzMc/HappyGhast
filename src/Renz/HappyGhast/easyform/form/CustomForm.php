<?php

declare(strict_types=1);

namespace Renz\HappyGhast\easyform\form;

use pocketmine\form\Form;
use pocketmine\player\Player;
use Closure;

class CustomForm implements Form
{
        private string $title;
        private array $elements = [];
        private ?Closure $onSubmit = null;
        private ?Closure $onClose = null;

        public function __construct(string $title)
        {
                $this->title = $title;
        }

        public function label(string $text): self
        {
                $this->elements[] = ["type" => "label", "text" => $text];
                return $this;
        }

        public function input(string $text, string $placeholder = "", string $default = ""): self
        {
                $this->elements[] = [
                        "type" => "input",
                        "text" => $text,
                        "placeholder" => $placeholder,
                        "default" => $default
                ];
                return $this;
        }

        public function toggle(string $text, bool $default = false): self
        {
                $this->elements[] = [
                        "type" => "toggle",
                        "text" => $text,
                        "default" => $default
                ];
                return $this;
        }

        public function slider(string $text, int $min, int $max, int $step = 1, int $default = 0): self
        {
                $this->elements[] = [
                        "type" => "slider",
                        "text" => $text,
                        "min" => $min,
                        "max" => $max,
                        "step" => $step,
                        "default" => $default
                ];
                return $this;
        }

        public function dropdown(string $text, array $options, int $default = 0): self
        {
                $this->elements[] = [
                        "type" => "dropdown",
                        "text" => $text,
                        "options" => $options,
                        "default" => $default
                ];
                return $this;
        }

        public function stepSlider(string $text, array $steps, int $default = 0): self
        {
                $this->elements[] = [
                        "type" => "step_slider",
                        "text" => $text,
                        "steps" => $steps,
                        "default" => $default
                ];
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

                if ($this->onSubmit !== null) {
                        ($this->onSubmit)($player, $data);
                }
        }

        public function jsonSerialize(): array
        {
                return [
                        "type" => "custom_form",
                        "title" => $this->title,
                        "content" => $this->elements
                ];
        }
}