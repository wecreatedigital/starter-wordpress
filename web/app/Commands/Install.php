<?php

namespace App\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;

class Install extends Command
{
    private $appName = '';

    protected function configure()
    {
        $this->addArgument(
            'app',
            InputArgument::REQUIRED,
            'Please enter your application name.'
        );

        $this->setName('lark:install');

        $this->setDescription('Command that sequentially runs lark installation commands from Terminal.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->appName = $input->getArgument('app');

        if ( ! $this->userConfirmation($input, $output)) {
            return Command::SUCCESS;
        }

        (new SymfonyStyle($input, $output))->success('Installation starting.');

        $methodsToCall = array_filter(get_class_methods($this), function ($method) {
            return substr($method, 0, 4) === 'step';
        });

        foreach ($methodsToCall as $method) {
            $this->{$method}($input, $output);
        }

        (new SymfonyStyle($input, $output))->success('Installation finished.');

        $output->writeln([
            '<info>Next steps...</>',
            '',
            "1. Please create the db using WordPress at '{$this->appName}.test/wp-admin'",
            "2. Switch over the theme to 'Lark Starter Child Theme'",
            "3. Activate the 'Advanced Custom Fields PRO' plugin",
            "3. See that the installation has worked by running 'yarn start' within the child theme directory",
            "   'cd ".getcwd().'/web/app/themes/lark-child'."'",
        ]);

        return Command::SUCCESS;
    }

    private function userConfirmation(InputInterface $input, OutputInterface $output): bool
    {
        $output->writeln([
            '',
            "Your root directory is called '{$this->appName}'?",
            "If so that would mean this installation would create '{$this->appName}.test'",
            '',
        ]);

        $question = new ConfirmationQuestion(
            '<question>Are you sure you want to proceed ?</question> (y/N)',
            false
        );

        $question->setMaxAttempts(2);
        $helper = $this->getHelper('question');

        if ( ! $helper->ask($input, $output, $question)) {
            $output->writeln('<info>Installation halted. Nothing has been done.</info>');

            return false;
        }

        return true;
    }

    private function stepOne(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            "<info>Running: 'valet link {$this->appName}'</>",
        ]);

        (new Process([
            'valet',
            'link',
            $this->appName,
        ]))
        ->setTimeout(null)
        ->setIdleTimeout(null)
        ->run();
    }

    private function stepTwo(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            "<info>Running: 'valet secure {$this->appName}'</>",
        ]);

        (new Process([
            'valet',
            'secure',
            $this->appName,
        ]))
        ->setTimeout(null)
        ->setIdleTimeout(null)
        ->run();
    }

    private function stepThree(InputInterface $input, OutputInterface $output)
    {
        $directory = getcwd().'/web/app/themes/lark';

        $output->writeln([
            "<info>Running: 'composer install' in parent theme</>",
        ]);

        (new Process([
            'composer',
            'install',
        ]))
        ->setTimeout(null)
        ->setIdleTimeout(null)
        ->setWorkingDirectory($directory)
        ->run();
    }

    private function stepFour(InputInterface $input, OutputInterface $output)
    {
        $directory = getcwd().'/web/app/themes/lark-child';

        $output->writeln([
            "<info>Running: 'composer install' in child theme</>",
        ]);

        (new Process([
            'composer',
            'install',
        ]))
        ->setTimeout(null)
        ->setIdleTimeout(null)
        ->setWorkingDirectory($directory)
        ->run();
    }

    private function stepFive(InputInterface $input, OutputInterface $output)
    {
        $baseAppUrl = "{$this->appName}.test";

        $output->writeln([
            "<info>Replacing starter.test with {$baseAppUrl}</>",
        ]);

        $filePath = getcwd().'/web/app/themes/lark-child/webpack.mix.js';

        file_put_contents(
            $filePath,
            str_replace('starter.test', $baseAppUrl, file_get_contents($filePath))
        );

        $directory = getcwd().'/web/app/themes/lark-child';

        $output->writeln([
            "<info>Running: 'yarn install --ignore-engines' in child theme</>",
        ]);

        (new Process([
            'yarn',
            'install',
            '--ignore-engines',
        ]))
        ->setTimeout(null)
        ->setIdleTimeout(null)
        ->setWorkingDirectory($directory)
        ->run();
    }

    private function stepSix(InputInterface $input, OutputInterface $output)
    {
        $directory = getcwd().'/web/app/themes/lark-child';

        $output->writeln([
            "<info>Running: 'yarn clean:views' in child theme</>",
        ]);

        (new Process([
            'yarn',
            'clean:views',
        ]))
        ->setTimeout(null)
        ->setIdleTimeout(null)
        ->setWorkingDirectory($directory)
        ->run();
    }
}
