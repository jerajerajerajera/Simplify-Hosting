<?php

// Stands in for Simplify's add-on classes when Simplify is not installed in the test panel.

namespace Simplify\Core\Addons;

abstract class Addon
{
    abstract public function id(): string;

    abstract public function name(): string;
}

final class Button
{
    public function __construct(public string $label, public string $url, public ?string $icon = null, public bool $primary = false) {}
}
