<?php

namespace EasyApiMaker\Framework;

use EasyApiBundle\Util\Entity\EntityConfigLoader;
use EasyApiBundle\Util\StringUtils\CaseConverter;

class CrudGenerator extends AbstractGenerator
{
    /**
     * @param string|null $context
     * @param string $entityName
     * @param bool $dumpExistingFiles
     *
     * @return array paths to the generated files
     */
    public function generate(?string $context, string $entityName, bool $dumpExistingFiles = false): string
    {
        $this->config = $this->loadEntityConfig($entityName, $context);

        return $this->generateController($dumpExistingFiles);
    }

    /**
     * Generate Controller file.
     *
     * @param bool $dumpExistingFiles
     *
     * @return string
     */
    protected function generateController(bool $dumpExistingFiles): string
    {
        $fileContent = $this->getContainer()->get('twig')->render(
            $this->getTemplatePath('doctrine/crud_controller.php.twig'),
            $this->generateContent()
        );

        return $this->writeFile($this->getControllerDirectoryPath(), "{$this->config->getEntityName()}Controller.php", $fileContent, $dumpExistingFiles, true);
    }

    /**
     * @return string
     */
    protected function getControllerDirectoryPath(): string
    {
        $context = str_replace(['\\', 'App/'], ['/', ''], $this->config->getContextName());

        return "src/Controller/{$context}";
    }

    /**
     * @return string
     */
    protected function getRoutingDirectoryPath(): string
    {
        $context = str_replace('\\', '/', $this->config->getContextName());

        return "src/Resources/config/routing/{$context}";
    }

    /**
     * @return string
     */
    protected function getRouteNamePrefix(): string
    {
        $prefix = str_replace(['API', 'Bundle'], ['api_', ''], $this->config->getContextName());

        if(!empty($this->config->getContextName())) {
            return CaseConverter::convertToSnakeCase($prefix.'_'.str_replace(['\\', '/'], '_', $this->config->getContextName()));
        }

        return CaseConverter::convertToSnakeCase($prefix);
    }

    /**
     * @return array
     */
    protected function generateContent(): array
    {
        $transformedContext = str_replace('\\', '/', $this->config->getContextName());
        $context = str_replace('/', '\\', $this->config->getContextName());

        $uses = [
            $this->container->getParameter('easy_api_maker.inheritance.controller'),
            $this->container->getParameter('easy_api.traits.crud'),
            "App\\Entity\\".(!empty($context) ? "{$context}\\" : '').$this->config->getEntityName(),
            "App\\Form\Type\\".(!empty($context) ? "{$context}\\" : '')."{$this->config->getEntityName()}Type",
        ];

        return [
            'namespace' => "App\\Controller".(!empty($context) ? "\\{$context}" : ''),
            'parent' => EntityConfigLoader::getShortEntityType($this->container->getParameter('easy_api_maker.inheritance.controller')),
            'entity_name' => $this->config->getEntityName(),
            'routing_url' => "App/Resources/config/routing/".(!empty($context) ? "{$transformedContext}/" : '')."{$this->config->getEntityName()}.yml",
            'controller_url' => "App/Controller/".(!empty($context) ? "{$transformedContext}/" : '').$this->config->getEntityName().'Controller.php',
            'context_name' => $context,
            'route_name_prefix' => $this->getRouteNamePrefix(),
            'entity_route_name' => CaseConverter::convertToSnakeCase($this->config->getEntityName()),
            'entity_url_name' => str_replace('_', '-', CaseConverter::convertToSnakeCase($this->config->getEntityName())),
            'serialization_groups' => implode(', ', $this->getSerializerGroups()),
            'uses' => $uses,
            'routingControllerPath' => "App:".(!empty($context) ? "{$context}\\" : '').$this->config->getEntityName(),
            'nativeFieldsNames' => implode(', ', array_map(function($val) { return "'{$val}'"; }, $this->config->getNativeFieldsNames(false))),
        ];
    }

    /**
     * @return array
     */
    protected function getSerializerGroups(): array
    {
        $groups = ['\''.CaseConverter::convertToSnakeCase($this->config->getEntityName()).'_full\''];

        // parent serializer groups
        $parentConfig = $this->config->getParentEntity();
        if (null !== $parentConfig) {
            $groups[] = '\''.CaseConverter::convertToSnakeCase($parentConfig->getEntityName()).'_full\'';
            foreach ($parentConfig->getFields() as $field) {
                if ($field->isReferential() && !in_array('\'referential_short\'', $groups)) {
                    $groups[] = '\'referential_short\'';
                } elseif (!$field->isNativeType() && ('manyToOne' === $field->getRelationType() || 'oneToOne' === $field->getRelationType())) {
                    $groups[] = '\''.CaseConverter::convertToSnakeCase($field->getName()).'_id\'';
                }
            }
        }

        foreach ($this->config->getFields() as $field) {
            if ($field->isReferential()) {
                if(!in_array('\'referential_short\'', $groups)) {
                    $groups[] = '\'referential_short\'';
                }
            } elseif (!$field->isNativeType() && ('manyToOne' === $field->getRelationType() || 'oneToOne' === $field->getRelationType())) {
                $groups[] = '\''.CaseConverter::convertToSnakeCase($field->getName()).'_id\'';
            }
        }

        return $groups;
    }
}