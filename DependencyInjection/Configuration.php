<?php

namespace EasyApiMaker\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This class contains the configuration information for the bundle.
 *
 * This information is solely responsible for how the different configuration
 * sections are normalized, and merged.
 *
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('easy_api_maker');
        $treeBuilder->getRootNode()
            ->children()
                ->arrayNode('inheritance')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('generator_skeleton_path')->defaultValue('@EasyApiMaker/skeleton/')->end()
                        ->scalarNode('entity')->defaultValue('EasyApiBundle\Entity\AbstractBaseEntity')->end()
                        ->scalarNode('entity_with_uuid')->defaultValue('EasyApiBundle\Entity\AbstractBaseUniqueEntity')->end()
                        ->scalarNode('entity_referential')->defaultValue('EasyApiBundle\Entity\AbstractBaseReferential')->end()
                        ->scalarNode('form')->defaultValue('EasyApiBundle\Form\Type\AbstractApiType')->end()
                        ->scalarNode('repository')->defaultValue('EasyApiBundle\Util\AbstractRepository')->end()
                        ->scalarNode('controller')->defaultValue('EasyApiBundle\Util\Controller\AbstractApiController')->end()
                        ->scalarNode('serialized_form')->defaultValue('EasyApiBundle\Util\Forms\SerializedForm')->end()
                    ->end()
                ->end()
            ->end()
            ;

        return $treeBuilder;
    }
}
