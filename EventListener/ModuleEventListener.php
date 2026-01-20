<?php

namespace CustomerGroup\EventListener;

use CustomerGroup\Handler\ConfigurationFileHandler;
use Propel\Runtime\Exception\PropelException;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Thelia\Core\Event\Module\ModuleToggleActivationEvent;
use Thelia\Core\Event\TheliaEvents;
use Thelia\Model\ModuleQuery;
use Thelia\Module\BaseModule;

/**
 * Listen to other module loading events to load the group configuration.
 */
class ModuleEventListener implements EventSubscriberInterface
{
    /**
     * @var ConfigurationFileHandler Configuration file handler service
     */
    protected ConfigurationFileHandler $configurationFileHandler;

    /**
     * Class constructor
     *
     * @param ConfigurationFileHandler $configurationFileHandler Configuration file handler service
     */
    public function __construct(ConfigurationFileHandler $configurationFileHandler)
    {
        $this->configurationFileHandler = $configurationFileHandler;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TheliaEvents::MODULE_TOGGLE_ACTIVATION => ['loadCustomerGroupConfigFile', 192]
        ];
    }

    /**
     * Load customer group definitions
     *
     * @param ModuleToggleActivationEvent $event A module toggle activation event
     * @throws PropelException
     */
    public function loadCustomerGroupConfigFile(ModuleToggleActivationEvent $event): void
    {
        $event->setModule(ModuleQuery::create()->findPk($event->getModuleId()));

        if ($event->getModule()->getActivate() === BaseModule::IS_NOT_ACTIVATED) {
            $this->configurationFileHandler->loadConfigurationFile($event->getModule());
        }
    }
}
