<?php

namespace CustomerGroup\Loop;

use CustomerGroup\Model\CustomerGroup as CustomerGroupModel;
use CustomerGroup\Model\CustomerGroupQuery;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Exception\PropelException;
use Thelia\Core\Template\Element\BaseI18nLoop;
use Thelia\Core\Template\Element\LoopResult;
use Thelia\Core\Template\Element\LoopResultRow;
use Thelia\Core\Template\Element\PropelSearchLoopInterface;
use Thelia\Core\Template\Loop\Argument\Argument;
use Thelia\Core\Template\Loop\Argument\ArgumentCollection;
use Thelia\Type\AlphaNumStringListType;
use Thelia\Type\TypeCollection;

/**
 * Loop on customer groups.
 */
class CustomerGroup extends BaseI18nLoop implements PropelSearchLoopInterface
{
    protected function getArgDefinitions(): ArgumentCollection
    {
        return new ArgumentCollection(
            Argument::createIntListTypeArgument("id"),
            Argument::createBooleanOrBothTypeArgument("is_default"),
            new Argument(
                "code",
                new TypeCollection(
                    new AlphaNumStringListType()
                )
            ),
            Argument::createEnumListTypeArgument(
                "order",
                [
                    "id",
                    "id-reverse",
                    "code",
                    "code-reverse",
                    "title",
                    "title-reverse",
                    "is_default",
                    "is_default-reverse",
                    "position",
                    "position-reverse",
                ],
                "position"
            )
        );
    }

    public function buildModelCriteria(): CustomerGroupQuery|ModelCriteria
    {
        $search = CustomerGroupQuery::create();

        // manage translations
        $this->configureI18nProcessing(
            $search,
            [
                'TITLE',
                'DESCRIPTION'
            ]
        );

        if (null !== $id = $this->getArgValue("id")) {
            $search->filterById($id, Criteria::IN);
        }

        if ($this->getArgValue("is_default")) {
            $search->filterByIsDefault(1, Criteria::EQUAL);
        } elseif (!$this->getArgValue("is_default")) {
            $search->filterByIsDefault(0, Criteria::EQUAL);
        }

        if (null !== $code = $this->getArgValue("code")) {
            $search->filterByCode($code, Criteria::IN);
        }

        foreach ($this->getArgValue("order") as $order) {
            switch ($order) {
                case "id":
                    $search->orderById(Criteria::ASC);
                    break;

                case "id-reverse":
                    $search->orderById(Criteria::DESC);
                    break;

                case "code":
                    $search->orderByCode(Criteria::ASC);
                    break;

                case "code-reverse":
                    $search->orderByCode(Criteria::DESC);
                    break;

                case "title":
                    $search->addAscendingOrderByColumn("i18n_TITLE");
                    break;

                case "title-reverse":
                    $search->addDescendingOrderByColumn("i18n_TITLE");
                    break;

                case "is_default":
                    $search->orderByIsDefault(Criteria::ASC);
                    break;

                case "is_default-reverse":
                    $search->orderByIsDefault(Criteria::DESC);
                    break;

                case "position-reverse":
                    $search->orderByPosition(Criteria::DESC);
                    break;

                case "position":
                default:
                    $search->orderByPosition(Criteria::ASC);
                    break;
            }
        }

        return $search;
    }

    /**
     * @throws PropelException
     */
    public function parseResults(LoopResult $loopResult): LoopResult
    {
        /** @var CustomerGroupModel $customerGroup */
        foreach ($loopResult->getResultDataCollection() as $customerGroup) {
            $loopResultRow = new LoopResultRow($customerGroup);
            $loopResultRow
                ->set("CUSTOMER_GROUP_ID", $customerGroup->getId())
                ->set("CODE", $customerGroup->getCode())
                ->set("TITLE", $customerGroup->getVirtualColumn('i18n_TITLE'))
                ->set("DESCRIPTION", $customerGroup->getVirtualColumn('i18n_DESCRIPTION'))
                ->set("IS_DEFAULT", $customerGroup->getIsDefault())
                ->set("POSITION", $customerGroup->getPosition())
                ->set("LOCALE", $customerGroup->getLocale());

            $loopResult->addRow($loopResultRow);
        }

        return $loopResult;
    }
}
