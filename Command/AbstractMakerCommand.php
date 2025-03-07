<?php

namespace EasyApiMaker\Command;

use EasyApiCore\Command\AbstractCommand;
use EasyApiMaker\Framework\CrudGenerator;
use EasyApiMaker\Framework\FormGenerator;
use EasyApiMaker\Framework\RepositoryGenerator;
use EasyApiMaker\Framework\TiCrudGenerator;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Twig\Environment;

abstract class AbstractMakerCommand extends AbstractCommand
{
    protected static string $commandPrefix = 'api:make';

    protected ?Environment $twig;
    protected FormFactoryInterface $formFactory;

    public function __construct(string $name = null, ContainerInterface $container = null, Environment $twig = null, FormFactoryInterface $formFactory = null)
    {
        parent::__construct($name, $container);
        $this->twig = $twig;
        $this->formFactory = $formFactory;
    }

    protected function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    protected function getTwig(): Environment
    {
        return $this->twig;
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

    protected function generateRepository(OutputInterface $output, string $entityName, string $context = null, bool $dumpExistingFiles = false): array
    {
        $output->writeln("\n------------- Generate Entity class -------------");
        $generator = new RepositoryGenerator($this->getContainer(), $this->getTwig());
        $filePath = $generator->generate($entityName, $context, $dumpExistingFiles);
        $output->writeln("file://$filePath[0] created.");
        $output->writeln("file://$filePath[1] modified.");
        
        return $filePath;
    }


    protected function generateForm(OutputInterface $output, string $entityName, $parent = null, string $context = null, bool $dumpExistingFiles = false): string
    {
        $output->writeln('------------- Generate Form -------------');
        $generator = new FormGenerator($this->getContainer(), $this->getTwig());
        $filePath = $generator->generate($context, $entityName, $parent, $dumpExistingFiles);
        $output->writeln("file://{$filePath} created.\n");
        
        return $filePath;
    }

    protected function generateCrud(OutputInterface $output, string $entityName, string $parent = null, string $context = null, bool $dumpExistingFiles = false): string
    {
        $output->writeln("\n------------- Generate CRUD -------------");
        $generator = new CrudGenerator($this->getContainer(), $this->getTwig());
        $filePath = $generator->generate($context, $entityName, $dumpExistingFiles);
        $output->writeln("file://{$filePath} created.");
        
        return $filePath;
    }

    /**
     * @param OutputInterface $output
     * @param string|null $context string
     * @param $entityName string
     * @param $dumpExistingFiles boolean
     */
    protected function generateTI(OutputInterface $output, ?string $context, string $entityName, bool $dumpExistingFiles = false)
    {
        $output->writeln("\n------------- Generate TI -------------");
        $generator = new TiCrudGenerator($this->getContainer(), $this->getTwig(), $this->formFactory);
        $filesPath = $generator->generate($context, $entityName, $dumpExistingFiles);
        foreach ($filesPath as $type => $file) {
            $type = ucfirst($type);
            $output->writeln("file://{$file} created.");
        }
    }
}