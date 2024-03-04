<?php

namespace EasyApiMaker\Command;

use EasyApiMaker\Util\StringUtils\CaseConverter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class MakeRepositoryCommand extends AbstractMakerCommand
{
    protected function configure()
    {
        $this
            ->setName(self::$commandPrefix.':repository')
            ->setDescription('Generate repository for entity')
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
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|void|null
     *
     * @throws \Exception
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $entityName = $input->getArgument('entity_name');
        $this->validateEntityName($entityName);
        $context = $input->getOption('context');
        $dumpOption = $input->getOption('no-dump');
        $dumpExistingFiles = !$dumpOption;

        // generate repository
        $filePath = $this->generateRepository($output, $entityName, $context, $dumpExistingFiles);

        $output->writeln('------------- Execute CS Fixer -------------');
        $localFilePath = str_replace($this->container->getParameter('kernel.project_dir').'/', '', $filePath[0]);
        exec("vendor/bin/php-cs-fixer fix $localFilePath");
        $localFilePath = str_replace($this->container->getParameter('kernel.project_dir').'/', '', $filePath[1]);
        exec("vendor/bin/php-cs-fixer fix $localFilePath");
        
        return Command::SUCCESS;
    }
}
