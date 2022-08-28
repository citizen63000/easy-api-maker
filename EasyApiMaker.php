<?php

namespace EasyApiMaker;

use EasyApiMaker\DependencyInjection\Compiler\ConfigurationPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class EasyApiMaker extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        $container->addCompilerPass(new ConfigurationPass());

    }
}