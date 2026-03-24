<?php

namespace App\Command;

use App\Shopping\ShoppingListRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:shopping:init-schema', description: 'Create shopping-list PostgreSQL schema')]
final class InitShoppingSchemaCommand extends Command
{
    public function __construct(private readonly ShoppingListRepository $lists)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->lists->createSchema();
        $output->writeln('<info>Shopping schema initialized.</info>');

        return Command::SUCCESS;
    }
}
