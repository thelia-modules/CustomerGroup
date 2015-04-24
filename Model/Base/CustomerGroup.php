<?php

namespace CustomerGroup\Model\Base;

use \DateTime;
use \Exception;
use \PDO;
use CustomerGroup\Model\CustomerCustomerGroup as ChildCustomerCustomerGroup;
use CustomerGroup\Model\CustomerCustomerGroupQuery as ChildCustomerCustomerGroupQuery;
use CustomerGroup\Model\CustomerGroup as ChildCustomerGroup;
use CustomerGroup\Model\CustomerGroupI18n as ChildCustomerGroupI18n;
use CustomerGroup\Model\CustomerGroupI18nQuery as ChildCustomerGroupI18nQuery;
use CustomerGroup\Model\CustomerGroupQuery as ChildCustomerGroupQuery;
use CustomerGroup\Model\Map\CustomerGroupTableMap;
use Propel\Runtime\Propel;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Collection\Collection;
use Propel\Runtime\Collection\ObjectCollection;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\BadMethodCallException;
use Propel\Runtime\Exception\PropelException;
use Propel\Runtime\Map\TableMap;
use Propel\Runtime\Parser\AbstractParser;
use Propel\Runtime\Util\PropelDateTime;
use Thelia\Model\Customer as ChildCustomer;
use Thelia\Model\CustomerQuery;

abstract class CustomerGroup implements ActiveRecordInterface
{
    /**
     * TableMap class name
     */
    const TABLE_MAP = '\\CustomerGroup\\Model\\Map\\CustomerGroupTableMap';


    /**
     * attribute to determine if this object has previously been saved.
     * @var boolean
     */
    protected $new = true;

    /**
     * attribute to determine whether this object has been deleted.
     * @var boolean
     */
    protected $deleted = false;

    /**
     * The columns that have been modified in current object.
     * Tracking modified columns allows us to only update modified columns.
     * @var array
     */
    protected $modifiedColumns = array();

    /**
     * The (virtual) columns that are added at runtime
     * The formatters can add supplementary columns based on a resultset
     * @var array
     */
    protected $virtualColumns = array();

    /**
     * The value for the code field.
     * @var        string
     */
    protected $code;

    /**
     * The value for the is_default field.
     * Note: this column has a database default value of: false
     * @var        boolean
     */
    protected $is_default;

    /**
     * The value for the id field.
     * @var        int
     */
    protected $id;

    /**
     * The value for the created_at field.
     * @var        string
     */
    protected $created_at;

    /**
     * The value for the updated_at field.
     * @var        string
     */
    protected $updated_at;

    /**
     * The value for the position field.
     * @var        int
     */
    protected $position;

    /**
     * @var        ObjectCollection|ChildCustomerCustomerGroup[] Collection to store aggregation of ChildCustomerCustomerGroup objects.
     */
    protected $collCustomerCustomerGroups;
    protected $collCustomerCustomerGroupsPartial;

    /**
     * @var        ObjectCollection|ChildCustomerGroupI18n[] Collection to store aggregation of ChildCustomerGroupI18n objects.
     */
    protected $collCustomerGroupI18ns;
    protected $collCustomerGroupI18nsPartial;

    /**
     * @var        ChildCustomer[] Collection to store aggregation of ChildCustomer objects.
     */
    protected $collCustomers;

    /**
     * Flag to prevent endless save loop, if this object is referenced
     * by another object which falls in this transaction.
     *
     * @var boolean
     */
    protected $alreadyInSave = false;

    // i18n behavior

    /**
     * Current locale
     * @var        string
     */
    protected $currentLocale = 'en_US';

    /**
     * Current translation objects
     * @var        array[ChildCustomerGroupI18n]
     */
    protected $currentTranslations;

    // sortable behavior

    /**
     * Queries to be executed in the save transaction
     * @var        array
     */
    protected $sortableQueries = array();

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection
     */
    protected $customersScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection
     */
    protected $customerCustomerGroupsScheduledForDeletion = null;

    /**
     * An array of objects scheduled for deletion.
     * @var ObjectCollection
     */
    protected $customerGroupI18nsScheduledForDeletion = null;

    /**
     * Applies default values to this object.
     * This method should be called from the object's constructor (or
     * equivalent initialization method).
     * @see __construct()
     */
    public function applyDefaultValues()
    {
        $this->is_default = false;
    }

    /**
     * Initializes internal state of CustomerGroup\Model\Base\CustomerGroup object.
     * @see applyDefaults()
     */
    public function __construct()
    {
        $this->applyDefaultValues();
    }

    /**
     * Returns whether the object has been modified.
     *
     * @return boolean True if the object has been modified.
     */
    public function isModified()
    {
        return !!$this->modifiedColumns;
    }

    /**
     * Has specified column been modified?
     *
     * @param  string  $col column fully qualified name (TableMap::TYPE_COLNAME), e.g. Book::AUTHOR_ID
     * @return boolean True if $col has been modified.
     */
    public function isColumnModified($col)
    {
        return $this->modifiedColumns && isset($this->modifiedColumns[$col]);
    }

    /**
     * Get the columns that have been modified in this object.
     * @return array A unique list of the modified column names for this object.
     */
    public function getModifiedColumns()
    {
        return $this->modifiedColumns ? array_keys($this->modifiedColumns) : [];
    }

    /**
     * Returns whether the object has ever been saved.  This will
     * be false, if the object was retrieved from storage or was created
     * and then saved.
     *
     * @return boolean true, if the object has never been persisted.
     */
    public function isNew()
    {
        return $this->new;
    }

    /**
     * Setter for the isNew attribute.  This method will be called
     * by Propel-generated children and objects.
     *
     * @param boolean $b the state of the object.
     */
    public function setNew($b)
    {
        $this->new = (Boolean) $b;
    }

    /**
     * Whether this object has been deleted.
     * @return boolean The deleted state of this object.
     */
    public function isDeleted()
    {
        return $this->deleted;
    }

    /**
     * Specify whether this object has been deleted.
     * @param  boolean $b The deleted state of this object.
     * @return void
     */
    public function setDeleted($b)
    {
        $this->deleted = (Boolean) $b;
    }

    /**
     * Sets the modified state for the object to be false.
     * @param  string $col If supplied, only the specified column is reset.
     * @return void
     */
    public function resetModified($col = null)
    {
        if (null !== $col) {
            if (isset($this->modifiedColumns[$col])) {
                unset($this->modifiedColumns[$col]);
            }
        } else {
            $this->modifiedColumns = array();
        }
    }

    /**
     * Compares this with another <code>CustomerGroup</code> instance.  If
     * <code>obj</code> is an instance of <code>CustomerGroup</code>, delegates to
     * <code>equals(CustomerGroup)</code>.  Otherwise, returns <code>false</code>.
     *
     * @param  mixed   $obj The object to compare to.
     * @return boolean Whether equal to the object specified.
     */
    public function equals($obj)
    {
        $thisclazz = get_class($this);
        if (!is_object($obj) || !($obj instanceof $thisclazz)) {
            return false;
        }

        if ($this === $obj) {
            return true;
        }

        if (null === $this->getPrimaryKey()
            || null === $obj->getPrimaryKey())  {
            return false;
        }

        return $this->getPrimaryKey() === $obj->getPrimaryKey();
    }

    /**
     * If the primary key is not null, return the hashcode of the
     * primary key. Otherwise, return the hash code of the object.
     *
     * @return int Hashcode
     */
    public function hashCode()
    {
        if (null !== $this->getPrimaryKey()) {
            return crc32(serialize($this->getPrimaryKey()));
        }

        return crc32(serialize(clone $this));
    }

    /**
     * Get the associative array of the virtual columns in this object
     *
     * @return array
     */
    public function getVirtualColumns()
    {
        return $this->virtualColumns;
    }

    /**
     * Checks the existence of a virtual column in this object
     *
     * @param  string  $name The virtual column name
     * @return boolean
     */
    public function hasVirtualColumn($name)
    {
        return array_key_exists($name, $this->virtualColumns);
    }

    /**
     * Get the value of a virtual column in this object
     *
     * @param  string $name The virtual column name
     * @return mixed
     *
     * @throws PropelException
     */
    public function getVirtualColumn($name)
    {
        if (!$this->hasVirtualColumn($name)) {
            throw new PropelException(sprintf('Cannot get value of inexistent virtual column %s.', $name));
        }

        return $this->virtualColumns[$name];
    }

    /**
     * Set the value of a virtual column in this object
     *
     * @param string $name  The virtual column name
     * @param mixed  $value The value to give to the virtual column
     *
     * @return CustomerGroup The current object, for fluid interface
     */
    public function setVirtualColumn($name, $value)
    {
        $this->virtualColumns[$name] = $value;

        return $this;
    }

    /**
     * Logs a message using Propel::log().
     *
     * @param  string  $msg
     * @param  int     $priority One of the Propel::LOG_* logging levels
     * @return boolean
     */
    protected function log($msg, $priority = Propel::LOG_INFO)
    {
        return Propel::log(get_class($this) . ': ' . $msg, $priority);
    }

    /**
     * Populate the current object from a string, using a given parser format
     * <code>
     * $book = new Book();
     * $book->importFrom('JSON', '{"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}');
     * </code>
     *
     * @param mixed $parser A AbstractParser instance,
     *                       or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param string $data The source data to import from
     *
     * @return CustomerGroup The current object, for fluid interface
     */
    public function importFrom($parser, $data)
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        $this->fromArray($parser->toArray($data), TableMap::TYPE_PHPNAME);

        return $this;
    }

    /**
     * Export the current object properties to a string, using a given parser format
     * <code>
     * $book = BookQuery::create()->findPk(9012);
     * echo $book->exportTo('JSON');
     *  => {"Id":9012,"Title":"Don Juan","ISBN":"0140422161","Price":12.99,"PublisherId":1234,"AuthorId":5678}');
     * </code>
     *
     * @param  mixed   $parser                 A AbstractParser instance, or a format name ('XML', 'YAML', 'JSON', 'CSV')
     * @param  boolean $includeLazyLoadColumns (optional) Whether to include lazy load(ed) columns. Defaults to TRUE.
     * @return string  The exported data
     */
    public function exportTo($parser, $includeLazyLoadColumns = true)
    {
        if (!$parser instanceof AbstractParser) {
            $parser = AbstractParser::getParser($parser);
        }

        return $parser->fromArray($this->toArray(TableMap::TYPE_PHPNAME, $includeLazyLoadColumns, array(), true));
    }

    /**
     * Clean up internal collections prior to serializing
     * Avoids recursive loops that turn into segmentation faults when serializing
     */
    public function __sleep()
    {
        $this->clearAllReferences();

        return array_keys(get_object_vars($this));
    }

    /**
     * Get the [code] column value.
     *
     * @return   string
     */
    public function getCode()
    {

        return $this->code;
    }

    /**
     * Get the [is_default] column value.
     *
     * @return   boolean
     */
    public function getIsDefault()
    {

        return $this->is_default;
    }

    /**
     * Get the [id] column value.
     *
     * @return   int
     */
    public function getId()
    {

        return $this->id;
    }

    /**
     * Get the [optionally formatted] temporal [created_at] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw \DateTime object will be returned.
     *
     * @return mixed Formatted date/time value as string or \DateTime object (if format is NULL), NULL if column is NULL, and 0 if column value is 0000-00-00 00:00:00
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getCreatedAt($format = NULL)
    {
        if ($format === null) {
            return $this->created_at;
        } else {
            return $this->created_at instanceof \DateTime ? $this->created_at->format($format) : null;
        }
    }

    /**
     * Get the [optionally formatted] temporal [updated_at] column value.
     *
     *
     * @param      string $format The date/time format string (either date()-style or strftime()-style).
     *                            If format is NULL, then the raw \DateTime object will be returned.
     *
     * @return mixed Formatted date/time value as string or \DateTime object (if format is NULL), NULL if column is NULL, and 0 if column value is 0000-00-00 00:00:00
     *
     * @throws PropelException - if unable to parse/validate the date/time value.
     */
    public function getUpdatedAt($format = NULL)
    {
        if ($format === null) {
            return $this->updated_at;
        } else {
            return $this->updated_at instanceof \DateTime ? $this->updated_at->format($format) : null;
        }
    }

    /**
     * Get the [position] column value.
     *
     * @return   int
     */
    public function getPosition()
    {

        return $this->position;
    }

    /**
     * Set the value of [code] column.
     *
     * @param      string $v new value
     * @return   \CustomerGroup\Model\CustomerGroup The current object (for fluent API support)
     */
    public function setCode($v)
    {
        if ($v !== null) {
            $v = (string) $v;
        }

        if ($this->code !== $v) {
            $this->code = $v;
            $this->modifiedColumns[CustomerGroupTableMap::CODE] = true;
        }


        return $this;
    } // setCode()

    /**
     * Sets the value of the [is_default] column.
     * Non-boolean arguments are converted using the following rules:
     *   * 1, '1', 'true',  'on',  and 'yes' are converted to boolean true
     *   * 0, '0', 'false', 'off', and 'no'  are converted to boolean false
     * Check on string values is case insensitive (so 'FaLsE' is seen as 'false').
     *
     * @param      boolean|integer|string $v The new value
     * @return   \CustomerGroup\Model\CustomerGroup The current object (for fluent API support)
     */
    public function setIsDefault($v)
    {
        if ($v !== null) {
            if (is_string($v)) {
                $v = in_array(strtolower($v), array('false', 'off', '-', 'no', 'n', '0', '')) ? false : true;
            } else {
                $v = (boolean) $v;
            }
        }

        if ($this->is_default !== $v) {
            $this->is_default = $v;
            $this->modifiedColumns[CustomerGroupTableMap::IS_DEFAULT] = true;
        }


        return $this;
    } // setIsDefault()

    /**
     * Set the value of [id] column.
     *
     * @param      int $v new value
     * @return   \CustomerGroup\Model\CustomerGroup The current object (for fluent API support)
     */
    public function setId($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->id !== $v) {
            $this->id = $v;
            $this->modifiedColumns[CustomerGroupTableMap::ID] = true;
        }


        return $this;
    } // setId()

    /**
     * Sets the value of [created_at] column to a normalized version of the date/time value specified.
     *
     * @param      mixed $v string, integer (timestamp), or \DateTime value.
     *               Empty strings are treated as NULL.
     * @return   \CustomerGroup\Model\CustomerGroup The current object (for fluent API support)
     */
    public function setCreatedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, '\DateTime');
        if ($this->created_at !== null || $dt !== null) {
            if ($dt !== $this->created_at) {
                $this->created_at = $dt;
                $this->modifiedColumns[CustomerGroupTableMap::CREATED_AT] = true;
            }
        } // if either are not null


        return $this;
    } // setCreatedAt()

    /**
     * Sets the value of [updated_at] column to a normalized version of the date/time value specified.
     *
     * @param      mixed $v string, integer (timestamp), or \DateTime value.
     *               Empty strings are treated as NULL.
     * @return   \CustomerGroup\Model\CustomerGroup The current object (for fluent API support)
     */
    public function setUpdatedAt($v)
    {
        $dt = PropelDateTime::newInstance($v, null, '\DateTime');
        if ($this->updated_at !== null || $dt !== null) {
            if ($dt !== $this->updated_at) {
                $this->updated_at = $dt;
                $this->modifiedColumns[CustomerGroupTableMap::UPDATED_AT] = true;
            }
        } // if either are not null


        return $this;
    } // setUpdatedAt()

    /**
     * Set the value of [position] column.
     *
     * @param      int $v new value
     * @return   \CustomerGroup\Model\CustomerGroup The current object (for fluent API support)
     */
    public function setPosition($v)
    {
        if ($v !== null) {
            $v = (int) $v;
        }

        if ($this->position !== $v) {
            $this->position = $v;
            $this->modifiedColumns[CustomerGroupTableMap::POSITION] = true;
        }


        return $this;
    } // setPosition()

    /**
     * Indicates whether the columns in this object are only set to default values.
     *
     * This method can be used in conjunction with isModified() to indicate whether an object is both
     * modified _and_ has some values set which are non-default.
     *
     * @return boolean Whether the columns in this object are only been set with default values.
     */
    public function hasOnlyDefaultValues()
    {
            if ($this->is_default !== false) {
                return false;
            }

        // otherwise, everything was equal, so return TRUE
        return true;
    } // hasOnlyDefaultValues()

    /**
     * Hydrates (populates) the object variables with values from the database resultset.
     *
     * An offset (0-based "start column") is specified so that objects can be hydrated
     * with a subset of the columns in the resultset rows.  This is needed, for example,
     * for results of JOIN queries where the resultset row includes columns from two or
     * more tables.
     *
     * @param array   $row       The row returned by DataFetcher->fetch().
     * @param int     $startcol  0-based offset column which indicates which restultset column to start with.
     * @param boolean $rehydrate Whether this object is being re-hydrated from the database.
     * @param string  $indexType The index type of $row. Mostly DataFetcher->getIndexType().
                                  One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                            TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *
     * @return int             next starting column
     * @throws PropelException - Any caught Exception will be rewrapped as a PropelException.
     */
    public function hydrate($row, $startcol = 0, $rehydrate = false, $indexType = TableMap::TYPE_NUM)
    {
        try {


            $col = $row[TableMap::TYPE_NUM == $indexType ? 0 + $startcol : CustomerGroupTableMap::translateFieldName('Code', TableMap::TYPE_PHPNAME, $indexType)];
            $this->code = (null !== $col) ? (string) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 1 + $startcol : CustomerGroupTableMap::translateFieldName('IsDefault', TableMap::TYPE_PHPNAME, $indexType)];
            $this->is_default = (null !== $col) ? (boolean) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 2 + $startcol : CustomerGroupTableMap::translateFieldName('Id', TableMap::TYPE_PHPNAME, $indexType)];
            $this->id = (null !== $col) ? (int) $col : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 3 + $startcol : CustomerGroupTableMap::translateFieldName('CreatedAt', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00 00:00:00') {
                $col = null;
            }
            $this->created_at = (null !== $col) ? PropelDateTime::newInstance($col, null, '\DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 4 + $startcol : CustomerGroupTableMap::translateFieldName('UpdatedAt', TableMap::TYPE_PHPNAME, $indexType)];
            if ($col === '0000-00-00 00:00:00') {
                $col = null;
            }
            $this->updated_at = (null !== $col) ? PropelDateTime::newInstance($col, null, '\DateTime') : null;

            $col = $row[TableMap::TYPE_NUM == $indexType ? 5 + $startcol : CustomerGroupTableMap::translateFieldName('Position', TableMap::TYPE_PHPNAME, $indexType)];
            $this->position = (null !== $col) ? (int) $col : null;
            $this->resetModified();

            $this->setNew(false);

            if ($rehydrate) {
                $this->ensureConsistency();
            }

            return $startcol + 6; // 6 = CustomerGroupTableMap::NUM_HYDRATE_COLUMNS.

        } catch (Exception $e) {
            throw new PropelException("Error populating \CustomerGroup\Model\CustomerGroup object", 0, $e);
        }
    }

    /**
     * Checks and repairs the internal consistency of the object.
     *
     * This method is executed after an already-instantiated object is re-hydrated
     * from the database.  It exists to check any foreign keys to make sure that
     * the objects related to the current object are correct based on foreign key.
     *
     * You can override this method in the stub class, but you should always invoke
     * the base method from the overridden method (i.e. parent::ensureConsistency()),
     * in case your model changes.
     *
     * @throws PropelException
     */
    public function ensureConsistency()
    {
    } // ensureConsistency

    /**
     * Reloads this object from datastore based on primary key and (optionally) resets all associated objects.
     *
     * This will only work if the object has been saved and has a valid primary key set.
     *
     * @param      boolean $deep (optional) Whether to also de-associated any related objects.
     * @param      ConnectionInterface $con (optional) The ConnectionInterface connection to use.
     * @return void
     * @throws PropelException - if this object is deleted, unsaved or doesn't have pk match in db
     */
    public function reload($deep = false, ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("Cannot reload a deleted object.");
        }

        if ($this->isNew()) {
            throw new PropelException("Cannot reload an unsaved object.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getReadConnection(CustomerGroupTableMap::DATABASE_NAME);
        }

        // We don't need to alter the object instance pool; we're just modifying this instance
        // already in the pool.

        $dataFetcher = ChildCustomerGroupQuery::create(null, $this->buildPkeyCriteria())->setFormatter(ModelCriteria::FORMAT_STATEMENT)->find($con);
        $row = $dataFetcher->fetch();
        $dataFetcher->close();
        if (!$row) {
            throw new PropelException('Cannot find matching row in the database to reload object values.');
        }
        $this->hydrate($row, 0, true, $dataFetcher->getIndexType()); // rehydrate

        if ($deep) {  // also de-associate any related objects?

            $this->collCustomerCustomerGroups = null;

            $this->collCustomerGroupI18ns = null;

            $this->collCustomers = null;
        } // if (deep)
    }

    /**
     * Removes this object from datastore and sets delete attribute.
     *
     * @param      ConnectionInterface $con
     * @return void
     * @throws PropelException
     * @see CustomerGroup::setDeleted()
     * @see CustomerGroup::isDeleted()
     */
    public function delete(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("This object has already been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(CustomerGroupTableMap::DATABASE_NAME);
        }

        $con->beginTransaction();
        try {
            $deleteQuery = ChildCustomerGroupQuery::create()
                ->filterByPrimaryKey($this->getPrimaryKey());
            $ret = $this->preDelete($con);
            // sortable behavior

            ChildCustomerGroupQuery::sortableShiftRank(-1, $this->getPosition() + 1, null, $con);
            CustomerGroupTableMap::clearInstancePool();

            if ($ret) {
                $deleteQuery->delete($con);
                $this->postDelete($con);
                $con->commit();
                $this->setDeleted(true);
            } else {
                $con->commit();
            }
        } catch (Exception $e) {
            $con->rollBack();
            throw $e;
        }
    }

    /**
     * Persists this object to the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All modified related objects will also be persisted in the doSave()
     * method.  This method wraps all precipitate database operations in a
     * single transaction.
     *
     * @param      ConnectionInterface $con
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see doSave()
     */
    public function save(ConnectionInterface $con = null)
    {
        if ($this->isDeleted()) {
            throw new PropelException("You cannot save an object that has been deleted.");
        }

        if ($con === null) {
            $con = Propel::getServiceContainer()->getWriteConnection(CustomerGroupTableMap::DATABASE_NAME);
        }

        $con->beginTransaction();
        $isInsert = $this->isNew();
        try {
            $ret = $this->preSave($con);
            // sortable behavior
            $this->processSortableQueries($con);
            if ($isInsert) {
                $ret = $ret && $this->preInsert($con);
                // timestampable behavior
                if (!$this->isColumnModified(CustomerGroupTableMap::CREATED_AT)) {
                    $this->setCreatedAt(time());
                }
                if (!$this->isColumnModified(CustomerGroupTableMap::UPDATED_AT)) {
                    $this->setUpdatedAt(time());
                }
                // sortable behavior
                if (!$this->isColumnModified(CustomerGroupTableMap::RANK_COL)) {
                    $this->setPosition(ChildCustomerGroupQuery::create()->getMaxRankArray($con) + 1);
                }

            } else {
                $ret = $ret && $this->preUpdate($con);
                // timestampable behavior
                if ($this->isModified() && !$this->isColumnModified(CustomerGroupTableMap::UPDATED_AT)) {
                    $this->setUpdatedAt(time());
                }
            }
            if ($ret) {
                $affectedRows = $this->doSave($con);
                if ($isInsert) {
                    $this->postInsert($con);
                } else {
                    $this->postUpdate($con);
                }
                $this->postSave($con);
                CustomerGroupTableMap::addInstanceToPool($this);
            } else {
                $affectedRows = 0;
            }
            $con->commit();

            return $affectedRows;
        } catch (Exception $e) {
            $con->rollBack();
            throw $e;
        }
    }

    /**
     * Performs the work of inserting or updating the row in the database.
     *
     * If the object is new, it inserts it; otherwise an update is performed.
     * All related objects are also updated in this method.
     *
     * @param      ConnectionInterface $con
     * @return int             The number of rows affected by this insert/update and any referring fk objects' save() operations.
     * @throws PropelException
     * @see save()
     */
    protected function doSave(ConnectionInterface $con)
    {
        $affectedRows = 0; // initialize var to track total num of affected rows
        if (!$this->alreadyInSave) {
            $this->alreadyInSave = true;

            if ($this->isNew() || $this->isModified()) {
                // persist changes
                if ($this->isNew()) {
                    $this->doInsert($con);
                } else {
                    $this->doUpdate($con);
                }
                $affectedRows += 1;
                $this->resetModified();
            }

            if ($this->customersScheduledForDeletion !== null) {
                if (!$this->customersScheduledForDeletion->isEmpty()) {
                    $pks = array();
                    $pk  = $this->getPrimaryKey();
                    foreach ($this->customersScheduledForDeletion->getPrimaryKeys(false) as $remotePk) {
                        $pks[] = array($remotePk, $pk);
                    }

                    CustomerCustomerGroupQuery::create()
                        ->filterByPrimaryKeys($pks)
                        ->delete($con);
                    $this->customersScheduledForDeletion = null;
                }

                foreach ($this->getCustomers() as $customer) {
                    if ($customer->isModified()) {
                        $customer->save($con);
                    }
                }
            } elseif ($this->collCustomers) {
                foreach ($this->collCustomers as $customer) {
                    if ($customer->isModified()) {
                        $customer->save($con);
                    }
                }
            }

            if ($this->customerCustomerGroupsScheduledForDeletion !== null) {
                if (!$this->customerCustomerGroupsScheduledForDeletion->isEmpty()) {
                    \CustomerGroup\Model\CustomerCustomerGroupQuery::create()
                        ->filterByPrimaryKeys($this->customerCustomerGroupsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->customerCustomerGroupsScheduledForDeletion = null;
                }
            }

                if ($this->collCustomerCustomerGroups !== null) {
            foreach ($this->collCustomerCustomerGroups as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            if ($this->customerGroupI18nsScheduledForDeletion !== null) {
                if (!$this->customerGroupI18nsScheduledForDeletion->isEmpty()) {
                    \CustomerGroup\Model\CustomerGroupI18nQuery::create()
                        ->filterByPrimaryKeys($this->customerGroupI18nsScheduledForDeletion->getPrimaryKeys(false))
                        ->delete($con);
                    $this->customerGroupI18nsScheduledForDeletion = null;
                }
            }

                if ($this->collCustomerGroupI18ns !== null) {
            foreach ($this->collCustomerGroupI18ns as $referrerFK) {
                    if (!$referrerFK->isDeleted() && ($referrerFK->isNew() || $referrerFK->isModified())) {
                        $affectedRows += $referrerFK->save($con);
                    }
                }
            }

            $this->alreadyInSave = false;

        }

        return $affectedRows;
    } // doSave()

    /**
     * Insert the row in the database.
     *
     * @param      ConnectionInterface $con
     *
     * @throws PropelException
     * @see doSave()
     */
    protected function doInsert(ConnectionInterface $con)
    {
        $modifiedColumns = array();
        $index = 0;

        $this->modifiedColumns[CustomerGroupTableMap::ID] = true;
        if (null !== $this->id) {
            throw new PropelException('Cannot insert a value for auto-increment primary key (' . CustomerGroupTableMap::ID . ')');
        }

         // check the columns in natural order for more readable SQL queries
        if ($this->isColumnModified(CustomerGroupTableMap::CODE)) {
            $modifiedColumns[':p' . $index++]  = 'CODE';
        }
        if ($this->isColumnModified(CustomerGroupTableMap::IS_DEFAULT)) {
            $modifiedColumns[':p' . $index++]  = 'IS_DEFAULT';
        }
        if ($this->isColumnModified(CustomerGroupTableMap::ID)) {
            $modifiedColumns[':p' . $index++]  = 'ID';
        }
        if ($this->isColumnModified(CustomerGroupTableMap::CREATED_AT)) {
            $modifiedColumns[':p' . $index++]  = 'CREATED_AT';
        }
        if ($this->isColumnModified(CustomerGroupTableMap::UPDATED_AT)) {
            $modifiedColumns[':p' . $index++]  = 'UPDATED_AT';
        }
        if ($this->isColumnModified(CustomerGroupTableMap::POSITION)) {
            $modifiedColumns[':p' . $index++]  = 'POSITION';
        }

        $sql = sprintf(
            'INSERT INTO customer_group (%s) VALUES (%s)',
            implode(', ', $modifiedColumns),
            implode(', ', array_keys($modifiedColumns))
        );

        try {
            $stmt = $con->prepare($sql);
            foreach ($modifiedColumns as $identifier => $columnName) {
                switch ($columnName) {
                    case 'CODE':
                        $stmt->bindValue($identifier, $this->code, PDO::PARAM_STR);
                        break;
                    case 'IS_DEFAULT':
                        $stmt->bindValue($identifier, (int) $this->is_default, PDO::PARAM_INT);
                        break;
                    case 'ID':
                        $stmt->bindValue($identifier, $this->id, PDO::PARAM_INT);
                        break;
                    case 'CREATED_AT':
                        $stmt->bindValue($identifier, $this->created_at ? $this->created_at->format("Y-m-d H:i:s") : null, PDO::PARAM_STR);
                        break;
                    case 'UPDATED_AT':
                        $stmt->bindValue($identifier, $this->updated_at ? $this->updated_at->format("Y-m-d H:i:s") : null, PDO::PARAM_STR);
                        break;
                    case 'POSITION':
                        $stmt->bindValue($identifier, $this->position, PDO::PARAM_INT);
                        break;
                }
            }
            $stmt->execute();
        } catch (Exception $e) {
            Propel::log($e->getMessage(), Propel::LOG_ERR);
            throw new PropelException(sprintf('Unable to execute INSERT statement [%s]', $sql), 0, $e);
        }

        try {
            $pk = $con->lastInsertId();
        } catch (Exception $e) {
            throw new PropelException('Unable to get autoincrement id.', 0, $e);
        }
        $this->setId($pk);

        $this->setNew(false);
    }

    /**
     * Update the row in the database.
     *
     * @param      ConnectionInterface $con
     *
     * @return Integer Number of updated rows
     * @see doSave()
     */
    protected function doUpdate(ConnectionInterface $con)
    {
        $selectCriteria = $this->buildPkeyCriteria();
        $valuesCriteria = $this->buildCriteria();

        return $selectCriteria->doUpdate($valuesCriteria, $con);
    }

    /**
     * Retrieves a field from the object by name passed in as a string.
     *
     * @param      string $name name
     * @param      string $type The type of fieldname the $name is of:
     *                     one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                     TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                     Defaults to TableMap::TYPE_PHPNAME.
     * @return mixed Value of field.
     */
    public function getByName($name, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = CustomerGroupTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);
        $field = $this->getByPosition($pos);

        return $field;
    }

    /**
     * Retrieves a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param      int $pos position in xml schema
     * @return mixed Value of field at $pos
     */
    public function getByPosition($pos)
    {
        switch ($pos) {
            case 0:
                return $this->getCode();
                break;
            case 1:
                return $this->getIsDefault();
                break;
            case 2:
                return $this->getId();
                break;
            case 3:
                return $this->getCreatedAt();
                break;
            case 4:
                return $this->getUpdatedAt();
                break;
            case 5:
                return $this->getPosition();
                break;
            default:
                return null;
                break;
        } // switch()
    }

    /**
     * Exports the object as an array.
     *
     * You can specify the key type of the array by passing one of the class
     * type constants.
     *
     * @param     string  $keyType (optional) One of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME,
     *                    TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                    Defaults to TableMap::TYPE_PHPNAME.
     * @param     boolean $includeLazyLoadColumns (optional) Whether to include lazy loaded columns. Defaults to TRUE.
     * @param     array $alreadyDumpedObjects List of objects to skip to avoid recursion
     * @param     boolean $includeForeignObjects (optional) Whether to include hydrated related objects. Default to FALSE.
     *
     * @return array an associative array containing the field names (as keys) and field values
     */
    public function toArray($keyType = TableMap::TYPE_PHPNAME, $includeLazyLoadColumns = true, $alreadyDumpedObjects = array(), $includeForeignObjects = false)
    {
        if (isset($alreadyDumpedObjects['CustomerGroup'][$this->getPrimaryKey()])) {
            return '*RECURSION*';
        }
        $alreadyDumpedObjects['CustomerGroup'][$this->getPrimaryKey()] = true;
        $keys = CustomerGroupTableMap::getFieldNames($keyType);
        $result = array(
            $keys[0] => $this->getCode(),
            $keys[1] => $this->getIsDefault(),
            $keys[2] => $this->getId(),
            $keys[3] => $this->getCreatedAt(),
            $keys[4] => $this->getUpdatedAt(),
            $keys[5] => $this->getPosition(),
        );
        $virtualColumns = $this->virtualColumns;
        foreach ($virtualColumns as $key => $virtualColumn) {
            $result[$key] = $virtualColumn;
        }

        if ($includeForeignObjects) {
            if (null !== $this->collCustomerCustomerGroups) {
                $result['CustomerCustomerGroups'] = $this->collCustomerCustomerGroups->toArray(null, true, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
            if (null !== $this->collCustomerGroupI18ns) {
                $result['CustomerGroupI18ns'] = $this->collCustomerGroupI18ns->toArray(null, true, $keyType, $includeLazyLoadColumns, $alreadyDumpedObjects);
            }
        }

        return $result;
    }

    /**
     * Sets a field from the object by name passed in as a string.
     *
     * @param      string $name
     * @param      mixed  $value field value
     * @param      string $type The type of fieldname the $name is of:
     *                     one of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME
     *                     TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     *                     Defaults to TableMap::TYPE_PHPNAME.
     * @return void
     */
    public function setByName($name, $value, $type = TableMap::TYPE_PHPNAME)
    {
        $pos = CustomerGroupTableMap::translateFieldName($name, $type, TableMap::TYPE_NUM);

        return $this->setByPosition($pos, $value);
    }

    /**
     * Sets a field from the object by Position as specified in the xml schema.
     * Zero-based.
     *
     * @param      int $pos position in xml schema
     * @param      mixed $value field value
     * @return void
     */
    public function setByPosition($pos, $value)
    {
        switch ($pos) {
            case 0:
                $this->setCode($value);
                break;
            case 1:
                $this->setIsDefault($value);
                break;
            case 2:
                $this->setId($value);
                break;
            case 3:
                $this->setCreatedAt($value);
                break;
            case 4:
                $this->setUpdatedAt($value);
                break;
            case 5:
                $this->setPosition($value);
                break;
        } // switch()
    }

    /**
     * Populates the object using an array.
     *
     * This is particularly useful when populating an object from one of the
     * request arrays (e.g. $_POST).  This method goes through the column
     * names, checking to see whether a matching key exists in populated
     * array. If so the setByName() method is called for that column.
     *
     * You can specify the key type of the array by additionally passing one
     * of the class type constants TableMap::TYPE_PHPNAME, TableMap::TYPE_STUDLYPHPNAME,
     * TableMap::TYPE_COLNAME, TableMap::TYPE_FIELDNAME, TableMap::TYPE_NUM.
     * The default key type is the column's TableMap::TYPE_PHPNAME.
     *
     * @param      array  $arr     An array to populate the object from.
     * @param      string $keyType The type of keys the array uses.
     * @return void
     */
    public function fromArray($arr, $keyType = TableMap::TYPE_PHPNAME)
    {
        $keys = CustomerGroupTableMap::getFieldNames($keyType);

        if (array_key_exists($keys[0], $arr)) $this->setCode($arr[$keys[0]]);
        if (array_key_exists($keys[1], $arr)) $this->setIsDefault($arr[$keys[1]]);
        if (array_key_exists($keys[2], $arr)) $this->setId($arr[$keys[2]]);
        if (array_key_exists($keys[3], $arr)) $this->setCreatedAt($arr[$keys[3]]);
        if (array_key_exists($keys[4], $arr)) $this->setUpdatedAt($arr[$keys[4]]);
        if (array_key_exists($keys[5], $arr)) $this->setPosition($arr[$keys[5]]);
    }

    /**
     * Build a Criteria object containing the values of all modified columns in this object.
     *
     * @return Criteria The Criteria object containing all modified values.
     */
    public function buildCriteria()
    {
        $criteria = new Criteria(CustomerGroupTableMap::DATABASE_NAME);

        if ($this->isColumnModified(CustomerGroupTableMap::CODE)) $criteria->add(CustomerGroupTableMap::CODE, $this->code);
        if ($this->isColumnModified(CustomerGroupTableMap::IS_DEFAULT)) $criteria->add(CustomerGroupTableMap::IS_DEFAULT, $this->is_default);
        if ($this->isColumnModified(CustomerGroupTableMap::ID)) $criteria->add(CustomerGroupTableMap::ID, $this->id);
        if ($this->isColumnModified(CustomerGroupTableMap::CREATED_AT)) $criteria->add(CustomerGroupTableMap::CREATED_AT, $this->created_at);
        if ($this->isColumnModified(CustomerGroupTableMap::UPDATED_AT)) $criteria->add(CustomerGroupTableMap::UPDATED_AT, $this->updated_at);
        if ($this->isColumnModified(CustomerGroupTableMap::POSITION)) $criteria->add(CustomerGroupTableMap::POSITION, $this->position);

        return $criteria;
    }

    /**
     * Builds a Criteria object containing the primary key for this object.
     *
     * Unlike buildCriteria() this method includes the primary key values regardless
     * of whether or not they have been modified.
     *
     * @return Criteria The Criteria object containing value(s) for primary key(s).
     */
    public function buildPkeyCriteria()
    {
        $criteria = new Criteria(CustomerGroupTableMap::DATABASE_NAME);
        $criteria->add(CustomerGroupTableMap::ID, $this->id);

        return $criteria;
    }

    /**
     * Returns the primary key for this object (row).
     * @return   int
     */
    public function getPrimaryKey()
    {
        return $this->getId();
    }

    /**
     * Generic method to set the primary key (id column).
     *
     * @param       int $key Primary key.
     * @return void
     */
    public function setPrimaryKey($key)
    {
        $this->setId($key);
    }

    /**
     * Returns true if the primary key for this object is null.
     * @return boolean
     */
    public function isPrimaryKeyNull()
    {

        return null === $this->getId();
    }

    /**
     * Sets contents of passed object to values from current object.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      object $copyObj An object of \CustomerGroup\Model\CustomerGroup (or compatible) type.
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @param      boolean $makeNew Whether to reset autoincrement PKs and make the object new.
     * @throws PropelException
     */
    public function copyInto($copyObj, $deepCopy = false, $makeNew = true)
    {
        $copyObj->setCode($this->getCode());
        $copyObj->setIsDefault($this->getIsDefault());
        $copyObj->setCreatedAt($this->getCreatedAt());
        $copyObj->setUpdatedAt($this->getUpdatedAt());
        $copyObj->setPosition($this->getPosition());

        if ($deepCopy) {
            // important: temporarily setNew(false) because this affects the behavior of
            // the getter/setter methods for fkey referrer objects.
            $copyObj->setNew(false);

            foreach ($this->getCustomerCustomerGroups() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addCustomerCustomerGroup($relObj->copy($deepCopy));
                }
            }

            foreach ($this->getCustomerGroupI18ns() as $relObj) {
                if ($relObj !== $this) {  // ensure that we don't try to copy a reference to ourselves
                    $copyObj->addCustomerGroupI18n($relObj->copy($deepCopy));
                }
            }

        } // if ($deepCopy)

        if ($makeNew) {
            $copyObj->setNew(true);
            $copyObj->setId(NULL); // this is a auto-increment column, so set to default value
        }
    }

    /**
     * Makes a copy of this object that will be inserted as a new row in table when saved.
     * It creates a new object filling in the simple attributes, but skipping any primary
     * keys that are defined for the table.
     *
     * If desired, this method can also make copies of all associated (fkey referrers)
     * objects.
     *
     * @param      boolean $deepCopy Whether to also copy all rows that refer (by fkey) to the current row.
     * @return                 \CustomerGroup\Model\CustomerGroup Clone of current object.
     * @throws PropelException
     */
    public function copy($deepCopy = false)
    {
        // we use get_class(), because this might be a subclass
        $clazz = get_class($this);
        $copyObj = new $clazz();
        $this->copyInto($copyObj, $deepCopy);

        return $copyObj;
    }


    /**
     * Initializes a collection based on the name of a relation.
     * Avoids crafting an 'init[$relationName]s' method name
     * that wouldn't work when StandardEnglishPluralizer is used.
     *
     * @param      string $relationName The name of the relation to initialize
     * @return void
     */
    public function initRelation($relationName)
    {
        if ('CustomerCustomerGroup' == $relationName) {
            return $this->initCustomerCustomerGroups();
        }
        if ('CustomerGroupI18n' == $relationName) {
            return $this->initCustomerGroupI18ns();
        }
    }

    /**
     * Clears out the collCustomerCustomerGroups collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addCustomerCustomerGroups()
     */
    public function clearCustomerCustomerGroups()
    {
        $this->collCustomerCustomerGroups = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collCustomerCustomerGroups collection loaded partially.
     */
    public function resetPartialCustomerCustomerGroups($v = true)
    {
        $this->collCustomerCustomerGroupsPartial = $v;
    }

    /**
     * Initializes the collCustomerCustomerGroups collection.
     *
     * By default this just sets the collCustomerCustomerGroups collection to an empty array (like clearcollCustomerCustomerGroups());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initCustomerCustomerGroups($overrideExisting = true)
    {
        if (null !== $this->collCustomerCustomerGroups && !$overrideExisting) {
            return;
        }
        $this->collCustomerCustomerGroups = new ObjectCollection();
        $this->collCustomerCustomerGroups->setModel('\CustomerGroup\Model\CustomerCustomerGroup');
    }

    /**
     * Gets an array of ChildCustomerCustomerGroup objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildCustomerGroup is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return Collection|ChildCustomerCustomerGroup[] List of ChildCustomerCustomerGroup objects
     * @throws PropelException
     */
    public function getCustomerCustomerGroups($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collCustomerCustomerGroupsPartial && !$this->isNew();
        if (null === $this->collCustomerCustomerGroups || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collCustomerCustomerGroups) {
                // return empty collection
                $this->initCustomerCustomerGroups();
            } else {
                $collCustomerCustomerGroups = ChildCustomerCustomerGroupQuery::create(null, $criteria)
                    ->filterByCustomerGroup($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collCustomerCustomerGroupsPartial && count($collCustomerCustomerGroups)) {
                        $this->initCustomerCustomerGroups(false);

                        foreach ($collCustomerCustomerGroups as $obj) {
                            if (false == $this->collCustomerCustomerGroups->contains($obj)) {
                                $this->collCustomerCustomerGroups->append($obj);
                            }
                        }

                        $this->collCustomerCustomerGroupsPartial = true;
                    }

                    reset($collCustomerCustomerGroups);

                    return $collCustomerCustomerGroups;
                }

                if ($partial && $this->collCustomerCustomerGroups) {
                    foreach ($this->collCustomerCustomerGroups as $obj) {
                        if ($obj->isNew()) {
                            $collCustomerCustomerGroups[] = $obj;
                        }
                    }
                }

                $this->collCustomerCustomerGroups = $collCustomerCustomerGroups;
                $this->collCustomerCustomerGroupsPartial = false;
            }
        }

        return $this->collCustomerCustomerGroups;
    }

    /**
     * Sets a collection of CustomerCustomerGroup objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $customerCustomerGroups A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return   ChildCustomerGroup The current object (for fluent API support)
     */
    public function setCustomerCustomerGroups(Collection $customerCustomerGroups, ConnectionInterface $con = null)
    {
        $customerCustomerGroupsToDelete = $this->getCustomerCustomerGroups(new Criteria(), $con)->diff($customerCustomerGroups);


        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->customerCustomerGroupsScheduledForDeletion = clone $customerCustomerGroupsToDelete;

        foreach ($customerCustomerGroupsToDelete as $customerCustomerGroupRemoved) {
            $customerCustomerGroupRemoved->setCustomerGroup(null);
        }

        $this->collCustomerCustomerGroups = null;
        foreach ($customerCustomerGroups as $customerCustomerGroup) {
            $this->addCustomerCustomerGroup($customerCustomerGroup);
        }

        $this->collCustomerCustomerGroups = $customerCustomerGroups;
        $this->collCustomerCustomerGroupsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related CustomerCustomerGroup objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related CustomerCustomerGroup objects.
     * @throws PropelException
     */
    public function countCustomerCustomerGroups(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collCustomerCustomerGroupsPartial && !$this->isNew();
        if (null === $this->collCustomerCustomerGroups || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collCustomerCustomerGroups) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getCustomerCustomerGroups());
            }

            $query = ChildCustomerCustomerGroupQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByCustomerGroup($this)
                ->count($con);
        }

        return count($this->collCustomerCustomerGroups);
    }

    /**
     * Method called to associate a ChildCustomerCustomerGroup object to this object
     * through the ChildCustomerCustomerGroup foreign key attribute.
     *
     * @param    ChildCustomerCustomerGroup $l ChildCustomerCustomerGroup
     * @return   \CustomerGroup\Model\CustomerGroup The current object (for fluent API support)
     */
    public function addCustomerCustomerGroup(ChildCustomerCustomerGroup $l)
    {
        if ($this->collCustomerCustomerGroups === null) {
            $this->initCustomerCustomerGroups();
            $this->collCustomerCustomerGroupsPartial = true;
        }

        if (!in_array($l, $this->collCustomerCustomerGroups->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->doAddCustomerCustomerGroup($l);
        }

        return $this;
    }

    /**
     * @param CustomerCustomerGroup $customerCustomerGroup The customerCustomerGroup object to add.
     */
    protected function doAddCustomerCustomerGroup($customerCustomerGroup)
    {
        $this->collCustomerCustomerGroups[]= $customerCustomerGroup;
        $customerCustomerGroup->setCustomerGroup($this);
    }

    /**
     * @param  CustomerCustomerGroup $customerCustomerGroup The customerCustomerGroup object to remove.
     * @return ChildCustomerGroup The current object (for fluent API support)
     */
    public function removeCustomerCustomerGroup($customerCustomerGroup)
    {
        if ($this->getCustomerCustomerGroups()->contains($customerCustomerGroup)) {
            $this->collCustomerCustomerGroups->remove($this->collCustomerCustomerGroups->search($customerCustomerGroup));
            if (null === $this->customerCustomerGroupsScheduledForDeletion) {
                $this->customerCustomerGroupsScheduledForDeletion = clone $this->collCustomerCustomerGroups;
                $this->customerCustomerGroupsScheduledForDeletion->clear();
            }
            $this->customerCustomerGroupsScheduledForDeletion[]= clone $customerCustomerGroup;
            $customerCustomerGroup->setCustomerGroup(null);
        }

        return $this;
    }


    /**
     * If this collection has already been initialized with
     * an identical criteria, it returns the collection.
     * Otherwise if this CustomerGroup is new, it will return
     * an empty collection; or if this CustomerGroup has previously
     * been saved, it will retrieve related CustomerCustomerGroups from storage.
     *
     * This method is protected by default in order to keep the public
     * api reasonable.  You can provide public methods for those you
     * actually need in CustomerGroup.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @param      string $joinBehavior optional join type to use (defaults to Criteria::LEFT_JOIN)
     * @return Collection|ChildCustomerCustomerGroup[] List of ChildCustomerCustomerGroup objects
     */
    public function getCustomerCustomerGroupsJoinCustomer($criteria = null, $con = null, $joinBehavior = Criteria::LEFT_JOIN)
    {
        $query = ChildCustomerCustomerGroupQuery::create(null, $criteria);
        $query->joinWith('Customer', $joinBehavior);

        return $this->getCustomerCustomerGroups($query, $con);
    }

    /**
     * Clears out the collCustomerGroupI18ns collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addCustomerGroupI18ns()
     */
    public function clearCustomerGroupI18ns()
    {
        $this->collCustomerGroupI18ns = null; // important to set this to NULL since that means it is uninitialized
    }

    /**
     * Reset is the collCustomerGroupI18ns collection loaded partially.
     */
    public function resetPartialCustomerGroupI18ns($v = true)
    {
        $this->collCustomerGroupI18nsPartial = $v;
    }

    /**
     * Initializes the collCustomerGroupI18ns collection.
     *
     * By default this just sets the collCustomerGroupI18ns collection to an empty array (like clearcollCustomerGroupI18ns());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @param      boolean $overrideExisting If set to true, the method call initializes
     *                                        the collection even if it is not empty
     *
     * @return void
     */
    public function initCustomerGroupI18ns($overrideExisting = true)
    {
        if (null !== $this->collCustomerGroupI18ns && !$overrideExisting) {
            return;
        }
        $this->collCustomerGroupI18ns = new ObjectCollection();
        $this->collCustomerGroupI18ns->setModel('\CustomerGroup\Model\CustomerGroupI18n');
    }

    /**
     * Gets an array of ChildCustomerGroupI18n objects which contain a foreign key that references this object.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildCustomerGroup is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria optional Criteria object to narrow the query
     * @param      ConnectionInterface $con optional connection object
     * @return Collection|ChildCustomerGroupI18n[] List of ChildCustomerGroupI18n objects
     * @throws PropelException
     */
    public function getCustomerGroupI18ns($criteria = null, ConnectionInterface $con = null)
    {
        $partial = $this->collCustomerGroupI18nsPartial && !$this->isNew();
        if (null === $this->collCustomerGroupI18ns || null !== $criteria  || $partial) {
            if ($this->isNew() && null === $this->collCustomerGroupI18ns) {
                // return empty collection
                $this->initCustomerGroupI18ns();
            } else {
                $collCustomerGroupI18ns = ChildCustomerGroupI18nQuery::create(null, $criteria)
                    ->filterByCustomerGroup($this)
                    ->find($con);

                if (null !== $criteria) {
                    if (false !== $this->collCustomerGroupI18nsPartial && count($collCustomerGroupI18ns)) {
                        $this->initCustomerGroupI18ns(false);

                        foreach ($collCustomerGroupI18ns as $obj) {
                            if (false == $this->collCustomerGroupI18ns->contains($obj)) {
                                $this->collCustomerGroupI18ns->append($obj);
                            }
                        }

                        $this->collCustomerGroupI18nsPartial = true;
                    }

                    reset($collCustomerGroupI18ns);

                    return $collCustomerGroupI18ns;
                }

                if ($partial && $this->collCustomerGroupI18ns) {
                    foreach ($this->collCustomerGroupI18ns as $obj) {
                        if ($obj->isNew()) {
                            $collCustomerGroupI18ns[] = $obj;
                        }
                    }
                }

                $this->collCustomerGroupI18ns = $collCustomerGroupI18ns;
                $this->collCustomerGroupI18nsPartial = false;
            }
        }

        return $this->collCustomerGroupI18ns;
    }

    /**
     * Sets a collection of CustomerGroupI18n objects related by a one-to-many relationship
     * to the current object.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param      Collection $customerGroupI18ns A Propel collection.
     * @param      ConnectionInterface $con Optional connection object
     * @return   ChildCustomerGroup The current object (for fluent API support)
     */
    public function setCustomerGroupI18ns(Collection $customerGroupI18ns, ConnectionInterface $con = null)
    {
        $customerGroupI18nsToDelete = $this->getCustomerGroupI18ns(new Criteria(), $con)->diff($customerGroupI18ns);


        //since at least one column in the foreign key is at the same time a PK
        //we can not just set a PK to NULL in the lines below. We have to store
        //a backup of all values, so we are able to manipulate these items based on the onDelete value later.
        $this->customerGroupI18nsScheduledForDeletion = clone $customerGroupI18nsToDelete;

        foreach ($customerGroupI18nsToDelete as $customerGroupI18nRemoved) {
            $customerGroupI18nRemoved->setCustomerGroup(null);
        }

        $this->collCustomerGroupI18ns = null;
        foreach ($customerGroupI18ns as $customerGroupI18n) {
            $this->addCustomerGroupI18n($customerGroupI18n);
        }

        $this->collCustomerGroupI18ns = $customerGroupI18ns;
        $this->collCustomerGroupI18nsPartial = false;

        return $this;
    }

    /**
     * Returns the number of related CustomerGroupI18n objects.
     *
     * @param      Criteria $criteria
     * @param      boolean $distinct
     * @param      ConnectionInterface $con
     * @return int             Count of related CustomerGroupI18n objects.
     * @throws PropelException
     */
    public function countCustomerGroupI18ns(Criteria $criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        $partial = $this->collCustomerGroupI18nsPartial && !$this->isNew();
        if (null === $this->collCustomerGroupI18ns || null !== $criteria || $partial) {
            if ($this->isNew() && null === $this->collCustomerGroupI18ns) {
                return 0;
            }

            if ($partial && !$criteria) {
                return count($this->getCustomerGroupI18ns());
            }

            $query = ChildCustomerGroupI18nQuery::create(null, $criteria);
            if ($distinct) {
                $query->distinct();
            }

            return $query
                ->filterByCustomerGroup($this)
                ->count($con);
        }

        return count($this->collCustomerGroupI18ns);
    }

    /**
     * Method called to associate a ChildCustomerGroupI18n object to this object
     * through the ChildCustomerGroupI18n foreign key attribute.
     *
     * @param    ChildCustomerGroupI18n $l ChildCustomerGroupI18n
     * @return   \CustomerGroup\Model\CustomerGroup The current object (for fluent API support)
     */
    public function addCustomerGroupI18n(ChildCustomerGroupI18n $l)
    {
        if ($l && $locale = $l->getLocale()) {
            $this->setLocale($locale);
            $this->currentTranslations[$locale] = $l;
        }
        if ($this->collCustomerGroupI18ns === null) {
            $this->initCustomerGroupI18ns();
            $this->collCustomerGroupI18nsPartial = true;
        }

        if (!in_array($l, $this->collCustomerGroupI18ns->getArrayCopy(), true)) { // only add it if the **same** object is not already associated
            $this->doAddCustomerGroupI18n($l);
        }

        return $this;
    }

    /**
     * @param CustomerGroupI18n $customerGroupI18n The customerGroupI18n object to add.
     */
    protected function doAddCustomerGroupI18n($customerGroupI18n)
    {
        $this->collCustomerGroupI18ns[]= $customerGroupI18n;
        $customerGroupI18n->setCustomerGroup($this);
    }

    /**
     * @param  CustomerGroupI18n $customerGroupI18n The customerGroupI18n object to remove.
     * @return ChildCustomerGroup The current object (for fluent API support)
     */
    public function removeCustomerGroupI18n($customerGroupI18n)
    {
        if ($this->getCustomerGroupI18ns()->contains($customerGroupI18n)) {
            $this->collCustomerGroupI18ns->remove($this->collCustomerGroupI18ns->search($customerGroupI18n));
            if (null === $this->customerGroupI18nsScheduledForDeletion) {
                $this->customerGroupI18nsScheduledForDeletion = clone $this->collCustomerGroupI18ns;
                $this->customerGroupI18nsScheduledForDeletion->clear();
            }
            $this->customerGroupI18nsScheduledForDeletion[]= clone $customerGroupI18n;
            $customerGroupI18n->setCustomerGroup(null);
        }

        return $this;
    }

    /**
     * Clears out the collCustomers collection
     *
     * This does not modify the database; however, it will remove any associated objects, causing
     * them to be refetched by subsequent calls to accessor method.
     *
     * @return void
     * @see        addCustomers()
     */
    public function clearCustomers()
    {
        $this->collCustomers = null; // important to set this to NULL since that means it is uninitialized
        $this->collCustomersPartial = null;
    }

    /**
     * Initializes the collCustomers collection.
     *
     * By default this just sets the collCustomers collection to an empty collection (like clearCustomers());
     * however, you may wish to override this method in your stub class to provide setting appropriate
     * to your application -- for example, setting the initial array to the values stored in database.
     *
     * @return void
     */
    public function initCustomers()
    {
        $this->collCustomers = new ObjectCollection();
        $this->collCustomers->setModel('\Thelia\Model\Customer');
    }

    /**
     * Gets a collection of ChildCustomer objects related by a many-to-many relationship
     * to the current object by way of the customer_customer_group cross-reference table.
     *
     * If the $criteria is not null, it is used to always fetch the results from the database.
     * Otherwise the results are fetched from the database the first time, then cached.
     * Next time the same method is called without $criteria, the cached collection is returned.
     * If this ChildCustomerGroup is new, it will return
     * an empty collection or the current collection; the criteria is ignored on a new object.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return ObjectCollection|ChildCustomer[] List of ChildCustomer objects
     */
    public function getCustomers($criteria = null, ConnectionInterface $con = null)
    {
        if (null === $this->collCustomers || null !== $criteria) {
            if ($this->isNew() && null === $this->collCustomers) {
                // return empty collection
                $this->initCustomers();
            } else {
                $collCustomers = CustomerQuery::create(null, $criteria)
                    ->filterByCustomerGroup($this)
                    ->find($con);
                if (null !== $criteria) {
                    return $collCustomers;
                }
                $this->collCustomers = $collCustomers;
            }
        }

        return $this->collCustomers;
    }

    /**
     * Sets a collection of Customer objects related by a many-to-many relationship
     * to the current object by way of the customer_customer_group cross-reference table.
     * It will also schedule objects for deletion based on a diff between old objects (aka persisted)
     * and new objects from the given Propel collection.
     *
     * @param  Collection $customers A Propel collection.
     * @param  ConnectionInterface $con Optional connection object
     * @return ChildCustomerGroup The current object (for fluent API support)
     */
    public function setCustomers(Collection $customers, ConnectionInterface $con = null)
    {
        $this->clearCustomers();
        $currentCustomers = $this->getCustomers();

        $this->customersScheduledForDeletion = $currentCustomers->diff($customers);

        foreach ($customers as $customer) {
            if (!$currentCustomers->contains($customer)) {
                $this->doAddCustomer($customer);
            }
        }

        $this->collCustomers = $customers;

        return $this;
    }

    /**
     * Gets the number of ChildCustomer objects related by a many-to-many relationship
     * to the current object by way of the customer_customer_group cross-reference table.
     *
     * @param      Criteria $criteria Optional query object to filter the query
     * @param      boolean $distinct Set to true to force count distinct
     * @param      ConnectionInterface $con Optional connection object
     *
     * @return int the number of related ChildCustomer objects
     */
    public function countCustomers($criteria = null, $distinct = false, ConnectionInterface $con = null)
    {
        if (null === $this->collCustomers || null !== $criteria) {
            if ($this->isNew() && null === $this->collCustomers) {
                return 0;
            } else {
                $query = CustomerQuery::create(null, $criteria);
                if ($distinct) {
                    $query->distinct();
                }

                return $query
                    ->filterByCustomerGroup($this)
                    ->count($con);
            }
        } else {
            return count($this->collCustomers);
        }
    }

    /**
     * Associate a ChildCustomer object to this object
     * through the customer_customer_group cross reference table.
     *
     * @param  ChildCustomer $customer The ChildCustomerCustomerGroup object to relate
     * @return ChildCustomerGroup The current object (for fluent API support)
     */
    public function addCustomer(ChildCustomer $customer)
    {
        if ($this->collCustomers === null) {
            $this->initCustomers();
        }

        if (!$this->collCustomers->contains($customer)) { // only add it if the **same** object is not already associated
            $this->doAddCustomer($customer);
            $this->collCustomers[] = $customer;
        }

        return $this;
    }

    /**
     * @param    Customer $customer The customer object to add.
     */
    protected function doAddCustomer($customer)
    {
        $customerCustomerGroup = new ChildCustomerCustomerGroup();
        $customerCustomerGroup->setCustomer($customer);
        $this->addCustomerCustomerGroup($customerCustomerGroup);
        // set the back reference to this object directly as using provided method either results
        // in endless loop or in multiple relations
        if (!$customer->getCustomerGroups()->contains($this)) {
            $foreignCollection   = $customer->getCustomerGroups();
            $foreignCollection[] = $this;
        }
    }

    /**
     * Remove a ChildCustomer object to this object
     * through the customer_customer_group cross reference table.
     *
     * @param ChildCustomer $customer The ChildCustomerCustomerGroup object to relate
     * @return ChildCustomerGroup The current object (for fluent API support)
     */
    public function removeCustomer(ChildCustomer $customer)
    {
        if ($this->getCustomers()->contains($customer)) {
            $this->collCustomers->remove($this->collCustomers->search($customer));

            if (null === $this->customersScheduledForDeletion) {
                $this->customersScheduledForDeletion = clone $this->collCustomers;
                $this->customersScheduledForDeletion->clear();
            }

            $this->customersScheduledForDeletion[] = $customer;
        }

        return $this;
    }

    /**
     * Clears the current object and sets all attributes to their default values
     */
    public function clear()
    {
        $this->code = null;
        $this->is_default = null;
        $this->id = null;
        $this->created_at = null;
        $this->updated_at = null;
        $this->position = null;
        $this->alreadyInSave = false;
        $this->clearAllReferences();
        $this->applyDefaultValues();
        $this->resetModified();
        $this->setNew(true);
        $this->setDeleted(false);
    }

    /**
     * Resets all references to other model objects or collections of model objects.
     *
     * This method is a user-space workaround for PHP's inability to garbage collect
     * objects with circular references (even in PHP 5.3). This is currently necessary
     * when using Propel in certain daemon or large-volume/high-memory operations.
     *
     * @param      boolean $deep Whether to also clear the references on all referrer objects.
     */
    public function clearAllReferences($deep = false)
    {
        if ($deep) {
            if ($this->collCustomerCustomerGroups) {
                foreach ($this->collCustomerCustomerGroups as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collCustomerGroupI18ns) {
                foreach ($this->collCustomerGroupI18ns as $o) {
                    $o->clearAllReferences($deep);
                }
            }
            if ($this->collCustomers) {
                foreach ($this->collCustomers as $o) {
                    $o->clearAllReferences($deep);
                }
            }
        } // if ($deep)

        // i18n behavior
        $this->currentLocale = 'en_US';
        $this->currentTranslations = null;

        $this->collCustomerCustomerGroups = null;
        $this->collCustomerGroupI18ns = null;
        $this->collCustomers = null;
    }

    /**
     * Return the string representation of this object
     *
     * @return string
     */
    public function __toString()
    {
        return (string) $this->exportTo(CustomerGroupTableMap::DEFAULT_STRING_FORMAT);
    }

    // timestampable behavior

    /**
     * Mark the current object so that the update date doesn't get updated during next save
     *
     * @return     ChildCustomerGroup The current object (for fluent API support)
     */
    public function keepUpdateDateUnchanged()
    {
        $this->modifiedColumns[CustomerGroupTableMap::UPDATED_AT] = true;

        return $this;
    }

    // i18n behavior

    /**
     * Sets the locale for translations
     *
     * @param     string $locale Locale to use for the translation, e.g. 'fr_FR'
     *
     * @return    ChildCustomerGroup The current object (for fluent API support)
     */
    public function setLocale($locale = 'en_US')
    {
        $this->currentLocale = $locale;

        return $this;
    }

    /**
     * Gets the locale for translations
     *
     * @return    string $locale Locale to use for the translation, e.g. 'fr_FR'
     */
    public function getLocale()
    {
        return $this->currentLocale;
    }

    /**
     * Returns the current translation for a given locale
     *
     * @param     string $locale Locale to use for the translation, e.g. 'fr_FR'
     * @param     ConnectionInterface $con an optional connection object
     *
     * @return ChildCustomerGroupI18n */
    public function getTranslation($locale = 'en_US', ConnectionInterface $con = null)
    {
        if (!isset($this->currentTranslations[$locale])) {
            if (null !== $this->collCustomerGroupI18ns) {
                foreach ($this->collCustomerGroupI18ns as $translation) {
                    if ($translation->getLocale() == $locale) {
                        $this->currentTranslations[$locale] = $translation;

                        return $translation;
                    }
                }
            }
            if ($this->isNew()) {
                $translation = new ChildCustomerGroupI18n();
                $translation->setLocale($locale);
            } else {
                $translation = ChildCustomerGroupI18nQuery::create()
                    ->filterByPrimaryKey(array($this->getPrimaryKey(), $locale))
                    ->findOneOrCreate($con);
                $this->currentTranslations[$locale] = $translation;
            }
            $this->addCustomerGroupI18n($translation);
        }

        return $this->currentTranslations[$locale];
    }

    /**
     * Remove the translation for a given locale
     *
     * @param     string $locale Locale to use for the translation, e.g. 'fr_FR'
     * @param     ConnectionInterface $con an optional connection object
     *
     * @return    ChildCustomerGroup The current object (for fluent API support)
     */
    public function removeTranslation($locale = 'en_US', ConnectionInterface $con = null)
    {
        if (!$this->isNew()) {
            ChildCustomerGroupI18nQuery::create()
                ->filterByPrimaryKey(array($this->getPrimaryKey(), $locale))
                ->delete($con);
        }
        if (isset($this->currentTranslations[$locale])) {
            unset($this->currentTranslations[$locale]);
        }
        foreach ($this->collCustomerGroupI18ns as $key => $translation) {
            if ($translation->getLocale() == $locale) {
                unset($this->collCustomerGroupI18ns[$key]);
                break;
            }
        }

        return $this;
    }

    /**
     * Returns the current translation
     *
     * @param     ConnectionInterface $con an optional connection object
     *
     * @return ChildCustomerGroupI18n */
    public function getCurrentTranslation(ConnectionInterface $con = null)
    {
        return $this->getTranslation($this->getLocale(), $con);
    }


        /**
         * Get the [title] column value.
         *
         * @return   string
         */
        public function getTitle()
        {
        return $this->getCurrentTranslation()->getTitle();
    }


        /**
         * Set the value of [title] column.
         *
         * @param      string $v new value
         * @return   \CustomerGroup\Model\CustomerGroupI18n The current object (for fluent API support)
         */
        public function setTitle($v)
        {    $this->getCurrentTranslation()->setTitle($v);

        return $this;
    }


        /**
         * Get the [description] column value.
         *
         * @return   string
         */
        public function getDescription()
        {
        return $this->getCurrentTranslation()->getDescription();
    }


        /**
         * Set the value of [description] column.
         *
         * @param      string $v new value
         * @return   \CustomerGroup\Model\CustomerGroupI18n The current object (for fluent API support)
         */
        public function setDescription($v)
        {    $this->getCurrentTranslation()->setDescription($v);

        return $this;
    }

    // sortable behavior

    /**
     * Wrap the getter for rank value
     *
     * @return    int
     */
    public function getRank()
    {
        return $this->position;
    }

    /**
     * Wrap the setter for rank value
     *
     * @param     int
     * @return    ChildCustomerGroup
     */
    public function setRank($v)
    {
        return $this->setPosition($v);
    }

    /**
     * Check if the object is first in the list, i.e. if it has 1 for rank
     *
     * @return    boolean
     */
    public function isFirst()
    {
        return $this->getPosition() == 1;
    }

    /**
     * Check if the object is last in the list, i.e. if its rank is the highest rank
     *
     * @param     ConnectionInterface  $con      optional connection
     *
     * @return    boolean
     */
    public function isLast(ConnectionInterface $con = null)
    {
        return $this->getPosition() == ChildCustomerGroupQuery::create()->getMaxRankArray($con);
    }

    /**
     * Get the next item in the list, i.e. the one for which rank is immediately higher
     *
     * @param     ConnectionInterface  $con      optional connection
     *
     * @return    ChildCustomerGroup
     */
    public function getNext(ConnectionInterface $con = null)
    {

        $query = ChildCustomerGroupQuery::create();

        $query->filterByRank($this->getPosition() + 1);


        return $query->findOne($con);
    }

    /**
     * Get the previous item in the list, i.e. the one for which rank is immediately lower
     *
     * @param     ConnectionInterface  $con      optional connection
     *
     * @return    ChildCustomerGroup
     */
    public function getPrevious(ConnectionInterface $con = null)
    {

        $query = ChildCustomerGroupQuery::create();

        $query->filterByRank($this->getPosition() - 1);


        return $query->findOne($con);
    }

    /**
     * Insert at specified rank
     * The modifications are not persisted until the object is saved.
     *
     * @param     integer    $rank rank value
     * @param     ConnectionInterface  $con      optional connection
     *
     * @return    ChildCustomerGroup the current object
     *
     * @throws    PropelException
     */
    public function insertAtRank($rank, ConnectionInterface $con = null)
    {
        $maxRank = ChildCustomerGroupQuery::create()->getMaxRankArray($con);
        if ($rank < 1 || $rank > $maxRank + 1) {
            throw new PropelException('Invalid rank ' . $rank);
        }
        // move the object in the list, at the given rank
        $this->setPosition($rank);
        if ($rank != $maxRank + 1) {
            // Keep the list modification query for the save() transaction
            $this->sortableQueries []= array(
                'callable'  => array('\CustomerGroup\Model\CustomerGroupQuery', 'sortableShiftRank'),
                'arguments' => array(1, $rank, null, )
            );
        }

        return $this;
    }

    /**
     * Insert in the last rank
     * The modifications are not persisted until the object is saved.
     *
     * @param ConnectionInterface $con optional connection
     *
     * @return    ChildCustomerGroup the current object
     *
     * @throws    PropelException
     */
    public function insertAtBottom(ConnectionInterface $con = null)
    {
        $this->setPosition(ChildCustomerGroupQuery::create()->getMaxRankArray($con) + 1);

        return $this;
    }

    /**
     * Insert in the first rank
     * The modifications are not persisted until the object is saved.
     *
     * @return    ChildCustomerGroup the current object
     */
    public function insertAtTop()
    {
        return $this->insertAtRank(1);
    }

    /**
     * Move the object to a new rank, and shifts the rank
     * Of the objects inbetween the old and new rank accordingly
     *
     * @param     integer   $newRank rank value
     * @param     ConnectionInterface $con optional connection
     *
     * @return    ChildCustomerGroup the current object
     *
     * @throws    PropelException
     */
    public function moveToRank($newRank, ConnectionInterface $con = null)
    {
        if ($this->isNew()) {
            throw new PropelException('New objects cannot be moved. Please use insertAtRank() instead');
        }
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(CustomerGroupTableMap::DATABASE_NAME);
        }
        if ($newRank < 1 || $newRank > ChildCustomerGroupQuery::create()->getMaxRankArray($con)) {
            throw new PropelException('Invalid rank ' . $newRank);
        }

        $oldRank = $this->getPosition();
        if ($oldRank == $newRank) {
            return $this;
        }

        $con->beginTransaction();
        try {
            // shift the objects between the old and the new rank
            $delta = ($oldRank < $newRank) ? -1 : 1;
            ChildCustomerGroupQuery::sortableShiftRank($delta, min($oldRank, $newRank), max($oldRank, $newRank), $con);

            // move the object to its new rank
            $this->setPosition($newRank);
            $this->save($con);

            $con->commit();

            return $this;
        } catch (Exception $e) {
            $con->rollback();
            throw $e;
        }
    }

    /**
     * Exchange the rank of the object with the one passed as argument, and saves both objects
     *
     * @param     ChildCustomerGroup $object
     * @param     ConnectionInterface $con optional connection
     *
     * @return    ChildCustomerGroup the current object
     *
     * @throws Exception if the database cannot execute the two updates
     */
    public function swapWith($object, ConnectionInterface $con = null)
    {
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(CustomerGroupTableMap::DATABASE_NAME);
        }
        $con->beginTransaction();
        try {
            $oldRank = $this->getPosition();
            $newRank = $object->getPosition();

            $this->setPosition($newRank);
            $object->setPosition($oldRank);

            $this->save($con);
            $object->save($con);
            $con->commit();

            return $this;
        } catch (Exception $e) {
            $con->rollback();
            throw $e;
        }
    }

    /**
     * Move the object higher in the list, i.e. exchanges its rank with the one of the previous object
     *
     * @param     ConnectionInterface $con optional connection
     *
     * @return    ChildCustomerGroup the current object
     */
    public function moveUp(ConnectionInterface $con = null)
    {
        if ($this->isFirst()) {
            return $this;
        }
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(CustomerGroupTableMap::DATABASE_NAME);
        }
        $con->beginTransaction();
        try {
            $prev = $this->getPrevious($con);
            $this->swapWith($prev, $con);
            $con->commit();

            return $this;
        } catch (Exception $e) {
            $con->rollback();
            throw $e;
        }
    }

    /**
     * Move the object higher in the list, i.e. exchanges its rank with the one of the next object
     *
     * @param     ConnectionInterface $con optional connection
     *
     * @return    ChildCustomerGroup the current object
     */
    public function moveDown(ConnectionInterface $con = null)
    {
        if ($this->isLast($con)) {
            return $this;
        }
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(CustomerGroupTableMap::DATABASE_NAME);
        }
        $con->beginTransaction();
        try {
            $next = $this->getNext($con);
            $this->swapWith($next, $con);
            $con->commit();

            return $this;
        } catch (Exception $e) {
            $con->rollback();
            throw $e;
        }
    }

    /**
     * Move the object to the top of the list
     *
     * @param     ConnectionInterface $con optional connection
     *
     * @return    ChildCustomerGroup the current object
     */
    public function moveToTop(ConnectionInterface $con = null)
    {
        if ($this->isFirst()) {
            return $this;
        }

        return $this->moveToRank(1, $con);
    }

    /**
     * Move the object to the bottom of the list
     *
     * @param     ConnectionInterface $con optional connection
     *
     * @return integer the old object's rank
     */
    public function moveToBottom(ConnectionInterface $con = null)
    {
        if ($this->isLast($con)) {
            return false;
        }
        if (null === $con) {
            $con = Propel::getServiceContainer()->getWriteConnection(CustomerGroupTableMap::DATABASE_NAME);
        }
        $con->beginTransaction();
        try {
            $bottom = ChildCustomerGroupQuery::create()->getMaxRankArray($con);
            $res = $this->moveToRank($bottom, $con);
            $con->commit();

            return $res;
        } catch (Exception $e) {
            $con->rollback();
            throw $e;
        }
    }

    /**
     * Removes the current object from the list.
     * The modifications are not persisted until the object is saved.
     *
     * @return    ChildCustomerGroup the current object
     */
    public function removeFromList()
    {
        // Keep the list modification query for the save() transaction
        $this->sortableQueries[] = array(
            'callable'  => array('\CustomerGroup\Model\CustomerGroupQuery', 'sortableShiftRank'),
            'arguments' => array(-1, $this->getPosition() + 1, null)
        );
        // remove the object from the list
        $this->setPosition(null);


        return $this;
    }

    /**
     * Execute queries that were saved to be run inside the save transaction
     */
    protected function processSortableQueries($con)
    {
        foreach ($this->sortableQueries as $query) {
            $query['arguments'][]= $con;
            call_user_func_array($query['callable'], $query['arguments']);
        }
        $this->sortableQueries = array();
    }

    /**
     * Code to be run before persisting the object
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preSave(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after persisting the object
     * @param ConnectionInterface $con
     */
    public function postSave(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before inserting to database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preInsert(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after inserting to database
     * @param ConnectionInterface $con
     */
    public function postInsert(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before updating the object in database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preUpdate(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after updating the object in database
     * @param ConnectionInterface $con
     */
    public function postUpdate(ConnectionInterface $con = null)
    {

    }

    /**
     * Code to be run before deleting the object in database
     * @param  ConnectionInterface $con
     * @return boolean
     */
    public function preDelete(ConnectionInterface $con = null)
    {
        return true;
    }

    /**
     * Code to be run after deleting the object in database
     * @param ConnectionInterface $con
     */
    public function postDelete(ConnectionInterface $con = null)
    {

    }


    /**
     * Derived method to catches calls to undefined methods.
     *
     * Provides magic import/export method support (fromXML()/toXML(), fromYAML()/toYAML(), etc.).
     * Allows to define default __call() behavior if you overwrite __call()
     *
     * @param string $name
     * @param mixed  $params
     *
     * @return array|string
     */
    public function __call($name, $params)
    {
        if (0 === strpos($name, 'get')) {
            $virtualColumn = substr($name, 3);
            if ($this->hasVirtualColumn($virtualColumn)) {
                return $this->getVirtualColumn($virtualColumn);
            }

            $virtualColumn = lcfirst($virtualColumn);
            if ($this->hasVirtualColumn($virtualColumn)) {
                return $this->getVirtualColumn($virtualColumn);
            }
        }

        if (0 === strpos($name, 'from')) {
            $format = substr($name, 4);

            return $this->importFrom($format, reset($params));
        }

        if (0 === strpos($name, 'to')) {
            $format = substr($name, 2);
            $includeLazyLoadColumns = isset($params[0]) ? $params[0] : true;

            return $this->exportTo($format, $includeLazyLoadColumns);
        }

        throw new BadMethodCallException(sprintf('Call to undefined method: %s.', $name));
    }

}
