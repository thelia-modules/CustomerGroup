<?php

namespace CustomerGroup\Handler;

use CustomerGroup\CustomerGroup;
use CustomerGroup\Model\CustomerCustomerGroupQuery;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\HttpFoundation\RequestStack;
use Thelia\Core\Security\SecurityContext;
use Thelia\Model\Customer;

/**
 * Handle checks on customer groups.
 */
class CustomerGroupHandler
{
    public function __construct(protected RequestStack $requestStack, protected SecurityContext $securityContext)
    {
    }

    /**
     * Get CustomerGroup of the current customer
     *
     * @return array|null
     */
    public function getGroup(): ?array
    {
        return $this->requestStack->getSession()->get(CustomerGroup::getModuleCode());
    }

    /**
     * Get CustomerGroup Code of the current customer
     *
     * @return string|null
     *
     * @uses getGroup()
     */
    public function getGroupCode(): ?string
    {
        $customerGroup = $this->getGroup();

        return (isset($customerGroup['code'])) ? $customerGroup['code'] : null;
    }

    /**
     * Check if the current customer is in the asked group
     *
     * @param string $groupCode Code for the group to check
     *
     * @return boolean
     *
     * @uses getGroupCode()
     */
    public function checkGroup(string $groupCode): bool
    {
        return $this->securityContext->hasCustomerUser() && $this->getGroupCode() === $groupCode;
    }

    /**
     * Check that a customer belongs to a group.
     *
     * @param Customer $customer
     * @param string $groupCode
     *
     * @return bool
     * @throws PropelException
     */
    public function checkCustomerHasGroup(Customer $customer, string $groupCode): bool
    {
        $group = CustomerCustomerGroupQuery::create()
            ->filterByCustomer($customer)
            ->useCustomerGroupQuery()
            ->filterByCode($groupCode)
            ->endUse()
            ->findOne();

        return $group !== null;
    }
}
