<?php

namespace CustomerGroup\EventListener;

use CustomerGroup\CustomerGroup;
use CustomerGroup\Event\AddCustomerToCustomerGroupEvent;
use CustomerGroup\Event\CustomerGroupEvents;
use CustomerGroup\Model\CustomerCustomerGroupQuery;
use CustomerGroup\Model\CustomerGroupQuery;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\Customer\CustomerCreateOrUpdateEvent;
use Thelia\Core\Event\Customer\CustomerLoginEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Model\Event\CustomerEvent;

/**
 * Performs actions on customer groups.
 */
class CustomerCustomerGroup implements EventSubscriberInterface
{
    protected Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CustomerGroupEvents::ADD_CUSTOMER_TO_CUSTOMER_GROUP => ["addCustomer", 128],
            TheliaEvents::CUSTOMER_CREATEACCOUNT => ["addDefaultCustomerGroupToCustomer", 100],
            TheliaEvents::CUSTOMER_LOGIN => ["addCustomerGroupToSession", 100],
        ];
    }

    /**
     * Add a customer to a customer group.
     * @param AddCustomerToCustomerGroupEvent $event
     * @throws PropelException
     */
    public function addCustomer(AddCustomerToCustomerGroupEvent $event): void
    {
        (new CustomerCustomerGroupQuery())
            ->filterByCustomerId($event->getCustomerId())
            ->filterByCustomerGroupId($event->getCustomerGroupId())
            ->findOneOrCreate()
            ->save();
    }

    /**
     * Add the customer to the default customer group (if there is one).
     * @param CustomerCreateOrUpdateEvent $event
     * @throws PropelException
     * @todo Only if there is no customer group in the event !
     */
    public function addDefaultCustomerGroupToCustomer(CustomerCreateOrUpdateEvent $event): void
    {
        $defaultCustomerGroup = CustomerGroupQuery::create()->findOneByIsDefault(true);
        if (null === $defaultCustomerGroup) {
            return;
        }

        (new CustomerCustomerGroupQuery())
            ->filterByCustomerId($event->getCustomer()->getId())
            ->filterByCustomerGroupId($defaultCustomerGroup->getId())
            ->findOneOrCreate()
            ->save();
    }

    /**
     * Add customer group information for the customer in the session.
     * Only information on the first group is added.
     * Group information is added to the session attributes with this module code as a key.
     * Structure:
     *     "id" => group id
     *     "code" => group code
     *     "default" => whether the group is the default group
     *
     * @param CustomerLoginEvent $event
     *
     * @todo Clarify if a customer can have multiple groups.
     */
    public function addCustomerGroupToSession(CustomerLoginEvent $event): void
    {
        $customerGroup = CustomerGroupQuery::create()
            ->useCustomerCustomerGroupQuery()
            ->filterByCustomerId($event->getCustomer()->getId())
            ->endUse()
            ->findOne();

        if ($customerGroup === null) {
            return;
        }

        $this->request->getSession()->set(
            CustomerGroup::getModuleCode(),
            [
                "id" => $customerGroup->getId(),
                "code" => $customerGroup->getCode(),
                "default" => $customerGroup->getIsDefault(),
            ]
        );
    }
}
