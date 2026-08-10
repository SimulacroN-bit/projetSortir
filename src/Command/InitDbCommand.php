<?php

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

#[AsCommand(
    name: 'app:init-db',
    description: 'Crée la base de données, exécute les migrations et charge les fixtures.'
)]
class InitDbCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $commands = [
            ['php', 'bin/console', 'doctrine:database:create', '--if-not-exists'],
            ['php', 'bin/console', 'doctrine:migrations:migrate', '-n'],
            ['php', 'bin/console', 'doctrine:fixtures:load', '-n'],
        ];
         foreach ($commands as $command) {
            $io->section('Exécution : ' . implode(' ', $command));
            $process = new Process($command);
            $process->setTimeout(null);
            $process->run(function ($type, $buffer) use ($output) {
                $output->write($buffer);
            });
            if (!$process->isSuccessful()) {
                $io->error('Une erreur est survenue.');
                return Command::FAILURE;
            }
        }
        $io->success('Base de données initialisée avec succès !');
        return Command::SUCCESS;
    }
}