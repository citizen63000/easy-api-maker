<?php

namespace EasyApiMaker\Command;

use EasyApiMaker\Util\StringUtils\CaseConverter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class MakeCrudCommand extends AbstractMakerCommand
{
    protected function configure()
    {
        $this
            ->setName(self::$commandPrefix.':crud')
            ->setDescription('Generate crud for entity')
            ->addArgument(
                'entity_name',
                InputArgument::REQUIRED,
                'Entity name.'
            )
            ->addOption(
                'context',
                'co',
                InputOption::VALUE_OPTIONAL,
                'The context.'
            )
            ->addOption(
                'no-dump',
                'nd',
                InputOption::VALUE_NONE,
                'Ex --no-dump'
            )
        ;
    }

    /**
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $entityName = $input->getArgument('entity_name');
        $this->validateEntityName($entityName);
        $context = $input->getOption('context');
        $dumpOption = $input->getOption('no-dump');

        // generate controller
        $filePath = $this->generateCrud($output, $entityName, null, $context, !$dumpOption);

        $output->writeln('------------- Execute CS Fixer -------------');
        $localFilePath = str_replace($this->container->getParameter('kernel.project_dir').'/', '', $filePath);
        exec("vendor/bin/php-cs-fixer fix $localFilePath");

        return Command::SUCCESS;
    }
}