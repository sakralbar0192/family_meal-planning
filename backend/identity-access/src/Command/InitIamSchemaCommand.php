<?php

namespace App\Command;

use App\Iam\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:iam:init-schema', description: 'Create IAM PostgreSQL schema')]
final class InitIamSchemaCommand extends Command
{
    public function __construct(private readonly UserRepository $users)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->users->createSchema();
        $output->writeln('<info>IAM schema initialized.</info>');

        return Command::SUCCESS;
    }
}
