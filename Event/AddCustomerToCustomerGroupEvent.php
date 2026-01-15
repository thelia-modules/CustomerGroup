<?php

namespace CustomerGroup\Event;


use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event to add a customer to a group.
 */
class AddCustomerToCustomerGroupEvent extends Event
{
    protected int $customer_id;
    protected int $customer_group_id;

    public function setCustomerGroupId($customer_group_id): static
    {
        $this->customer_group_id = $customer_group_id;

        return $this;
    }

    public function getCustomerGroupId(): int
    {
        return $this->customer_group_id;
    }

    public function setCustomerId(int $customer_id): static
    {
        $this->customer_id = $customer_id;

        return $this;
    }

    public function getCustomerId(): int
    {
        return $this->customer_id;
    }
}
