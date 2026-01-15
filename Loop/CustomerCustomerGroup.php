<?php

namespace CustomerGroup\Loop;

use CustomerGroup\Model\CustomerCustomerGroup as CustomerGroupModel;
use CustomerGroup\Model\CustomerCustomerGroupQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Thelia\Core\Template\Element\BaseLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;

/**
 * Loop on customer and customer group associations.
 */
class CustomerCustomerGroup extends BaseLoop implements PropelSearchLoopInterface
{
    protected function getArgDefinitions(): ArgumentCollection
    {
        return new ArgumentCollection(
            Argument::createIntListTypeArgument("customer"),
            Argument::createIntListTypeArgument("customer_group")
        );
    }

    public function buildModelCriteria(): CustomerCustomerGroupQuery|ModelCriteria
    {
        $search = CustomerCustomerGroupQuery::create();

        if (null !== $customerId = $this->getArgValue("customer")) {
            $search->filterByCustomerId($customerId, Criteria::IN);
        }

        if (null !== $customerGroupId = $this->getArgValue("customer_group")) {
            $search->filterByCustomerGroupId($customerGroupId, Criteria::IN);
        }

        return $search;
    }

    public function parseResults(LoopResult $loopResult): LoopResult
    {
        /** @var CustomerGroupModel $customerCustomerGroup */
        foreach ($loopResult->getResultDataCollection() as $customerCustomerGroup) {
            $loopResultRow = new LoopResultRow($customerCustomerGroup);
            $loopResultRow
                ->set("CUSTOMER_GROUP_ID", $customerCustomerGroup->getCustomerGroupId())
                ->set("CUSTOMER_ID", $customerCustomerGroup->getCustomerId());

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}
