<?php

namespace CustomerGroup\Handler;

use CustomerGroup\Model\CustomerGroup;
use CustomerGroup\Model\CustomerGroupQuery;
use Propel\Runtime\Exception\PropelException;
use SimpleXMLElement;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\Util\XmlUtils;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Thelia\Model\Module;

/**
 * Handle group configuration files
 */
class ConfigurationFileHandler
{
    /**
     * Find, parse, and load customer group configuration file for the module
     *
     * @param Module $module A module object
     *
     * @throws InvalidConfigurationException|PropelException
     */
    public function loadConfigurationFile(Module $module): void
    {
        $finder = (new Finder)
            // TODO: Flip when yaml parsing will be up
//            ->name('#customer[-_\.]?group\.(?:xml|yml|yaml)#i')
            ->name('#customer[-_\.]?group\.xml#i')
            ->in($module->getAbsoluteConfigPath());
        $count = $finder->count();

        if ($count > 1) {
            throw new InvalidConfigurationException('Too many configuration file.');
        } else {
            foreach ($finder as $file) {
                if (strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'xml') {
                    $moduleConfig = $this->parseXml($file);
                } else {
                    $moduleConfig = $this->parseYml($file);
                }

                $this->applyConfig($moduleConfig);
            }
        }
    }

    /**
     * Get config from an XML file
     *
     * @param SplFileInfo $file XML file
     *
     * @return array Customer group module configuration
     */
    protected function parseXml(SplFileInfo $file): array
    {
        $dom = XmlUtils::loadFile($file, realpath(dirname(__DIR__) . DS . 'Schema' . DS . 'customer-group.xsd'));
        /** @var SimpleXMLElement $xml */
        $xml = simplexml_import_dom($dom, '\\Symfony\\Component\\DependencyInjection\\SimpleXMLElement');

        $parsedConfig = [];
        /** @var SimpleXMLElement $customerGroupDefinition */
        foreach ($xml->customergroup as $customerGroupDefinition) {
            $descriptive = [];
            /** @var SimpleXMLElement $descriptiveDefinition */
            foreach ($customerGroupDefinition->descriptive as $descriptiveDefinition) {
                $descriptive[] = [
                    'locale' => $descriptiveDefinition->locale,
                    'title' => (string)$descriptiveDefinition->title,
                    'description' => (string)$descriptiveDefinition->description
                ];
            }

            $parsedConfig['customer_group'][] = [
                'code' => $customerGroupDefinition->code,
                'descriptive' => $descriptive
            ];
        }

        $parsedConfig['default'] = (string)$xml->default;

        return $parsedConfig;
    }

    /**
     * Get config from the yml file
     *
     * @param SplFileInfo $file YAML file
     *
     * @return array Customer group module configuration
     */
    protected function parseYml(SplFileInfo $file): array
    {
        //@todo

        return [];
    }

    /**
     * Save a new customer group to a database
     *
     * @param array $moduleConfiguration Customer group module configuration
     * @throws PropelException
     */
    protected function applyConfig(array $moduleConfiguration): void
    {
        foreach ($moduleConfiguration['customer_group'] as $customerGroupData) {
            if (CustomerGroupQuery::create()->findOneByCode($customerGroupData['code']) === null) {
                $customerGroup = (new CustomerGroup)
                    ->setCode($customerGroupData['code']);

                foreach ($customerGroupData['descriptive'] as $descriptiveData) {
                    $customerGroup
                        ->setLocale($descriptiveData['locale'])
                        ->setTitle($descriptiveData['title'])
                        ->setDescription($descriptiveData['description']);
                }

                $customerGroup->save();
            }
        }

        if ($moduleConfiguration['default']) {
            $customerGroup = CustomerGroupQuery::create()->findOneByCode($moduleConfiguration['default']);
            if ($customerGroup !== null) {
                $this->resetDefault();

                $customerGroup
                    ->setIsDefault(true)
                    ->save();
            }
        }
    }

    /**
     * Remove is_default flag
     * @throws PropelException
     */
    protected function resetDefault(): void
    {
        $defaultGroups = CustomerGroupQuery::create()
            ->filterByIsDefault(true)
            ->find();

        foreach ($defaultGroups as $defaultGroup) {
            $defaultGroup
                ->setIsDefault(false)
                ->save();
        }

    }
}
