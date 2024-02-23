<?php

namespace EasyApiMaker\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;

class EasyApiMakerExtension extends Extension
{
    /**
     * @inheritDoc
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        // Load configuration
        $config = (new Processor())->processConfiguration(new Configuration(), $configs);

        // Convert config as parameters
        $this->loadParametersFromConfiguration($config, $container);
        
        // Load services
        $loader = new YamlFileLoader($container, new FileLocator(__DIR__ . '/../config'));
        $loader->load('services.yml');
    }

    protected function loadParametersFromConfiguration(array $loadedConfig, ContainerBuilder $container, string $parentKey = 'easy_api_maker')
    {
        foreach ($loadedConfig as $parameter => $value) {
            if (is_array($value)) {
                $this->loadParametersFromConfiguration($value, $container, "{$parentKey}.{$parameter}");
            } else {
                $container->setParameter("{$parentKey}.{$parameter}", $value);
            }
        }
    }
}