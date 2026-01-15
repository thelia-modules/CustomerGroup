# CustomerGroup

This module adds customer groups, in which you can put customers.

## Installation

### Manually

* Copy the module into the ```<thelia_root>/local/modules/``` directory and make sure that the name of the module is CustomerGroup
* Activate it in your Thelia administration panel

### Composer

Add it in your main Thelia composer.json file

```
composer require thelia/customer-group-module:~0.2
```

## Configuration

Modules that use customer groups must define them in the `customer-group.xml` file in the module configuration directory.
The groups will be created when the module is activated.

One the groups can be defined as the default group. All new customers will be automatically added to this group. 

```XML
<?xml version="1.0" encoding="UTF-8" ?>
<customergroups xmlns="urn:thelia:module:customer-group">
    <customergroup code="customer">
        <descriptive locale="en_US">
            <title>Customer</title>
            <description>Basic customer<description>
        </descriptive>
        <descriptive locale="fr_FR">
            <title>Client</title>
            <description>Client de base<description>
        </descriptive>
    </customergroup>

    <customergroup code="vip">
        <descriptive locale="en_US">
            <title>VIP</title>
            <description>VIP customer !<description>
        </descriptive>
        <descriptive locale="fr_FR">
            <title>VIP</title>
            <description>Client VIP !<description>
        </descriptive>
    </customergroup>

    <default>customer</default>
</customergroups>
```

## Events

Events should be used to perform actions related to customer groups.

The `CustomerGroup\Event\CustomerGroupEvents` class contains event name constants for this module.
Event classes are also in the `CustomerGroup\Event` namespace.

### Add a customer to a group

```PHP
$event = new AddCustomerToCustomerGroupEvent();
$event->setCustomerId($myCustomer->getId());
$event->setCustomerGroupId($myGroup->getId());

$dispatcher->dispatch(
    CustomerGroupEvents::ADD_CUSTOMER_TO_CUSTOMER_GROUP,
    $event
);
```

## Handler

The `customer_group.handler` service provide functions to check if a customer belongs to a group.
See the `CustomerGroupHandler` class for available methods.

### Get handler (service)

```PHP
$groupHandler = $container->get("customer_group.handler");
```

### Get session customer's customerGroup info

```PHP
// get customerGroup of the current customer (session)
$groupHandler->getGroup();
// get customerGroup code of the current customer (session)
$groupHandler->checkGroupCode();
```

### Check if a customer belongs to a group

```PHP
// check a customer
$groupHandler->checkCustomerHasGroup($myCustomer, "vip");

// check the customer currently logged-in
$groupHandler->checkGroup("vip");
```

## Loops

### customergroup

This loop list customer groups.

#### Input arguments

|Argument      |Description                            |
|--------------|---------------------------------------|
|**id**        | Id or list of customer group ids.     |
|**is_default**| List only the default group.          |
|**code**      | Code or list of customer group codes. |
|**order**     | Order of the results.                 |
|**lang**      | Locale of the results.                |

The **order** can be one of these:

- `position` (default)
- `position-reverse`
- `id`
- `id-reverse`
- `code`
- `code-reverse`
- `title`
- `title-reverse`
- `is_default`
- `is_default-reverse`

#### Output variables

|Variable          |Description                                     |
|------------------|------------------------------------------------|
|$CUSTOMER_GROUP_ID| Group id.                                      |
|$CODE             | Group code.                                    |
|$TITLE            | Group title in the selected locale.            |
|$DESCRIPTION      | Group description in the selected locale.      |
|$IS_DEFAULT       | Whether the group is the default group or not. |
|$POSITION         | Group position.                                |
|$LOCALE           | Locale of the results.                         |

### customercustomergroup

This group lists the associations between customers and customer groups.

#### Input arguments

|Argument          |Description                        |
|------------------|-----------------------------------|
|**customer**      | Id or list of customer ids.       |
|**customer_group**| Id or list of customer group ids. |

#### Output variables

|Variable          |Description                               |
|------------------|------------------------------------------|
|$CUSTOMER_ID      | Id of the customer.                      |
|$CUSTOMER_GROUP_ID| Id of the group the customer belongs to. |

### customer

This module also adds group information to the customer loop and allows filtering the customers using groups.

#### Additional input arguments

|Argument                     |Description                                |
|-----------------------------|-------------------------------------------|
|**customer_group_id**        | Id or list of customer group ids.         |
|**customer_group_code**      | Code or list of customer group codes.     |
|**customer_group_is_default**| List only customers in the default group. |

### Additional output variables

|Variable               |Description                                         |
|-----------------------|----------------------------------------------------|
|$CUSTOMER_GROUP_ID     | Id of the customer's group.                        |
|$CUSTOMER_GROUP_CODE   | Code of the customer's group.                      |
|$CUSTOMER_GROUP_DEFAULT| Whether the customer's group is the default group. |

## Query

### CustomerQuery

This module provides a `CustomerQuery` class that extends the base Thelia query and provides methods to filter customers by group.

It can be used in replacement of the base CustomerQuery.

```PHP
use CustomerGroup\Model\CustomerQuery as CustomerGroupCustomerQuery;

$customers = CustomerGroupCustomerQuery::create()
    ->filterByCustomerGroup("myGroup")
    ->find();
```

Or mixed in your own query class by using the static methods.
These methods take a `ModelCriteria` query class and will act on it.

They assume that the customer table is already present in the query scope, so your query must for exemple extends `CustomerQuery` or make a join to the customer table.

```PHP
use CustomerGroup\Model\CustomerQuery as CustomerGroupCustomerQuery;

// MyQuery extends CustomerQuery
// or MyQuery has a join to the customer table somewhere
$myQuery = MyQuery::create();

CustomerGroupCustomerQuery::addCustomerGroupFilter($myQuery, "myGroup");
```
