<?php

namespace EasyApiMaker\Command;

use EasyApiMaker\Util\StringUtils\CaseConverter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class MakeTICommand extends AbstractMakerCommand
{
    protected function configure()
    {
        $this
            ->setName(self::$commandPrefix.':ti')
            ->setDescription('Generate TI for crud')
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
        $context = $input->getOption('context');
        $this->validateEntityName($entityName);
        $dumpOption = $input->getOption('no-dump');
        $dumpExistingFiles = !$dumpOption;

        // generate repository
        $this->generateTi($output, $context, $entityName,  $dumpExistingFiles);

        return Command::SUCCESS;
    }
}