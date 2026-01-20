<?php

namespace CustomerGroup;

use CustomerGroup\Handler\ConfigurationFileHandler;
use CustomerGroup\Model\CustomerGroupQuery;
use Propel\Runtime\Connection\ConnectionInterface;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\DependencyInjection\Loader\Configurator\ServicesConfigurator;
use Thelia\Install\Database;
use Thelia\Model\Module;
use Thelia\Model\ModuleQuery;
use Thelia\Module\BaseModule;

/**
 * Class CustomerGroup
 */
class CustomerGroup extends BaseModule
{
    /**
     * @var string Translation domain name
     */
    const MESSAGE_DOMAIN = 'customergroup';

    /**
     * @throws PropelException
     */
    public function postActivation(ConnectionInterface $con = null): void
    {
        parent::postActivation($con);

        if (!self::getConfigValue('is_initialized',null)){
            $database = new Database($con);
            $database->insertSql(null, [__DIR__ . "/Config/TheliaMain.sql"]);
            self::setConfigValue('is_initialized', 1);
        }

        $configurationFileHandler = new ConfigurationFileHandler;

        $modules = ModuleQuery::create()->findByActivate(BaseModule::IS_ACTIVATED);
        /** @var Module $module */
        foreach ($modules as $module) {
            $configurationFileHandler->loadConfigurationFile($module);
        }
    }

    public static function configureServices(ServicesConfigurator $servicesConfigurator): void {
        $servicesConfigurator->load(self::getModuleCode().'\\', __DIR__)
            ->exclude([THELIA_MODULE_DIR . ucfirst(self::getModuleCode()). "/I18n/*"])
            ->autowire(true)
            ->autoconfigure(true);
    }
}
