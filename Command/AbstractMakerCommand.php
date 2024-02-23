<?php

namespace EasyApiMaker\Command;

use EasyApiBundle\Util\AbstractCommand;
use EasyApiMaker\Framework\CrudGenerator;
use EasyApiMaker\Framework\FormGenerator;
use EasyApiMaker\Framework\RepositoryGenerator;
use EasyApiMaker\Framework\TiCrudGenerator;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

abstract class AbstractMakerCommand extends AbstractCommand
{
    protected static $commandPrefix = 'api:make';
    
    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @param string $name
     */
    protected function validateEntityName(string $name)
    {
        if (preg_match('/[_]+/', $name)) {
            throw new InvalidArgumentException(sprintf('Entity name "%s" is invalid.', $name));
        }
    }

    protected function generateRepository(OutputInterface $output, string $entityName, string $context = null, bool $dumpExistingFiles = false)
    {
        $output->writeln("\n------------- Generate Entity class -------------");
        $generator = new RepositoryGenerator($this->getContainer());
        $filePath = $generator->generate($entityName, $context, $dumpExistingFiles);
        $output->writeln("file://$filePath[0] created.");
        $output->writeln("file://$filePath[1] modified.");
    }


    protected function generateForm(OutputInterface $output, string $entityName, $parent = null, string $context = null, bool $dumpExistingFiles = false): string
    {
        $output->writeln('------------- Generate Form -------------');
        $generator = new FormGenerator($this->getContainer());
        $filePath = $generator->generate($context, $entityName, $parent, $dumpExistingFiles);
        $output->writeln("file://{$filePath} created.\n");
        
        return $filePath;
    }

    protected function generateCrud(OutputInterface $output, string $entityName, string $parent = null, string $context = null, bool $dumpExistingFiles = false)
    {
        $output->writeln("\n------------- Generate CRUD -------------");
        $generator = new CrudGenerator($this->getContainer());
        $filesPath = $generator->generate($context, $entityName, $dumpExistingFiles);
        foreach ($filesPath as $type => $file) {
            $type = ucfirst($type);
            $output->writeln("file://{$file} created.");
        }
    }

    /**
     * @param OutputInterface $output
     * @param string|null $bundle string
     * @param string|null $context string
     * @param $entityName string
     * @param $dumpExistingFiles boolean
     */
    protected function generateTI(OutputInterface $output, ?string $bundle, ?string $context, string $entityName, bool $dumpExistingFiles = false)
    {
        $output->writeln("\n------------- Generate TI -------------");
        $generator = new TiCrudGenerator($this->getContainer());
        $filesPath = $generator->generate($bundle, $context, $entityName, $dumpExistingFiles);
        foreach ($filesPath as $type => $file) {
            $type = ucfirst($type);
            $output->writeln("file://{$file} created.");
        }
    }
}