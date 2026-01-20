<?php

namespace CustomerGroup\Loop;

use CustomerGroup\Model\Map\CustomerCustomerGroupTableMap;
use CustomerGroup\Model\Map\CustomerGroupTableMap;
use PDO;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\Join;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Exception\PropelException;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Core\Template\Loop\Customer;
use Thelia\Model\Customer as CustomerModel;
use Thelia\Model\CustomerQuery;
use Thelia\Model\Map\CustomerTableMap;
use Thelia\Type\BooleanOrBothType;

/**
 * Extension of the Thelia customer loop. Adds group information.
 */
class CustomerGroupCustomerLoop extends Customer
{
    public function buildModelCriteria(): CustomerQuery|ModelCriteria|null
    {
        $customerQuery = parent::buildModelCriteria();

        $customerQuery->addJoinObject(
            new Join(
                CustomerTableMap::COL_ID,
                CustomerCustomerGroupTableMap::COL_CUSTOMER_ID,
                Criteria::LEFT_JOIN
            ),
            "customer_customer_group_join"
        );
        $customerQuery->addJoinObject(
            new Join(
                CustomerCustomerGroupTableMap::COL_CUSTOMER_GROUP_ID,
                CustomerGroupTableMap::COL_ID,
                Criteria::LEFT_JOIN
            ),
            "customer_group_join"
        );

        if (null !== $customerGroupId = $this->getArgValue("customer_group_id")) {
            $customerQuery->where(
                '`customer_group`.`ID` IN (?)',
                implode(",", $customerGroupId),
                PDO::PARAM_INT
            );
        }

        $customerGroupIsDefault = $this->getArgValue("customer_group_is_default");
        if ($customerGroupIsDefault !== BooleanOrBothType::ANY) {
            if ($customerGroupIsDefault === true) {
                $customerQuery->where(
                    '`customer_group`.`IS_DEFAULT` = ?',
                    1,
                    PDO::PARAM_INT
                );
            } elseif ($customerGroupIsDefault === false) {
                $customerQuery->where(
                    '`customer_group`.`IS_DEFAULT` = ?',
                    0,
                    PDO::PARAM_INT
                );
            }
        }

        if (null !== $customerGroupCode = $this->getArgValue("customer_group_code")) {
            $customerQuery->where(
                '`customer_group`.`CODE` LIKE ?',
                '%' . $customerGroupCode . '%',
                PDO::PARAM_STR
            );
        }

        $customerQuery->withColumn('customer_group.code', 'CUSTOMER_GROUP_CODE');
        $customerQuery->withColumn('customer_group.id', 'CUSTOMER_GROUP_ID');
        $customerQuery->withColumn('customer_group.is_default', 'CUSTOMER_GROUP_DEFAULT');

        return $customerQuery;
    }

    protected function getArgDefinitions(): ArgumentCollection
    {
        $argumentCollection = parent::getArgDefinitions();

        $argumentCollection->addArgument(
            Argument::createIntListTypeArgument('customer_group_id')
        );

        $argumentCollection->addArgument(
            Argument::createAnyTypeArgument('customer_group_code')
        );

        $argumentCollection->addArgument(
            Argument::createBooleanOrBothTypeArgument('customer_group_is_default', "*")
        );

        return $argumentCollection;
    }

    /**
     * @throws PropelException
     */
    public function parseResults(LoopResult $loopResult): LoopResult
    {
        $loopResult = parent::parseResults($loopResult);

        foreach ($loopResult as $loopResultRow) {
            /** @var CustomerModel $customer */
            $customer = $loopResultRow->model;
            $loopResultRow
                ->set("CUSTOMER_GROUP_CODE", $customer->getVirtualColumn("CUSTOMER_GROUP_CODE"))
                ->set("CUSTOMER_GROUP_ID", $customer->getVirtualColumn("CUSTOMER_GROUP_ID"))
                ->set("CUSTOMER_GROUP_DEFAULT", $customer->getVirtualColumn("CUSTOMER_GROUP_DEFAULT"));
        }

        return $loopResult;
    }
}
