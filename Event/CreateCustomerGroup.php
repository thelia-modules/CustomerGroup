<?php

namespace CustomerGroup\Event;


use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event for customer group creation.
 */
class CreateCustomerGroup extends Event
{
    protected string $code;
    protected bool $is_default;
    protected string $title;
    protected string $description;

    public function setCode($code): static
    {
        $this->code = $code;

        return $this;
    }

    public function getCode(): string
    {
        return $this->code;
    }

    public function setIsDefault($is_default): static
    {
        $this->is_default = $is_default;

        return $this;
    }

    public function getIsDefault(): bool
    {
        return $this->is_default;
    }
}
