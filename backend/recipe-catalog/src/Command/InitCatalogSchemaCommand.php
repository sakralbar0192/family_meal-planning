<?php

namespace App\Command;

use App\Catalog\RecipeRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:catalog:init-schema', description: 'Create recipe catalog PostgreSQL schema')]
final class InitCatalogSchemaCommand extends Command
{
    public function __construct(private readonly RecipeRepository $recipes)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->recipes->createSchema();
        $output->writeln('<info>Catalog schema initialized.</info>');

        return Command::SUCCESS;
    }
}
