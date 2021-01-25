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
        if ( ! $this->userConfirmation($input, $output)) {
            return Command::SUCCESS;
        }

        (new SymfonyStyle($input, $output))->success('Installation starting...');

        $methodsToCall = array_filter(get_class_methods($this), function ($method) {
            return substr($method, 0, 4) === 'step';
        });

        foreach ($methodsToCall as $method) {
            $this->{$method}($input, $output);
        }

        return Command::SUCCESS;
    }

    private function userConfirmation(InputInterface $input, OutputInterface $output): bool
    {
        $output->writeln([
            '',
            "Your root directory is called '".$input->getArgument('app')."'?",
            "If so that would mean this installation would create '".$input->getArgument('app').".test'",
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
            '<info>Running: valet link '.$input->getArgument('app').'</>',
            '',
        ]);

        (new Process([
            'valet',
            'link',
            $input->getArgument('app'),
        ]))
        ->setTimeout(null)
        ->setIdleTimeout(null)
        ->run();
    }

    private function stepTwo(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            '<info>Running: valet secure '.$input->getArgument('app').'</>',
            '',
        ]);

        (new Process([
            'valet',
            'secure',
            $input->getArgument('app'),
        ]))
        ->setTimeout(null)
        ->setIdleTimeout(null)
        ->run();
    }

    private function stepThree(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            '<info>Running: composer install within '.getcwd().'/web/app/themes/lark'.'</>',
            '',
        ]);

        (new Process([
            'composer',
            'install',
        ]))
        ->setTimeout(null)
        ->setIdleTimeout(null)
        ->setWorkingDirectory(getcwd().'/web/app/themes/lark')
        ->run();
    }

    private function stepFour(InputInterface $input, OutputInterface $output)
    {
        $output->writeln([
            '<info>Running: composer install within '.getcwd().'/web/app/themes/lark-child'.'</>',
            '',
        ]);

        (new Process([
            'composer',
            'install',
        ]))
        ->setTimeout(null)
        ->setIdleTimeout(null)
        ->setWorkingDirectory(getcwd().'/web/app/themes/lark-child')
        ->run();
    }

    private function stepFive(InputInterface $input, OutputInterface $output)
    {
        $baseAppUrl = $input->getArgument('app').'.test';

        $output->writeln([
            "<info>Replacing starter.test with {$baseAppUrl}</>",
            '',
        ]);

        $file = getcwd().'/web/app/themes/lark-child/webpack.mix.js';

        file_put_contents(
            $file,
            str_replace('starter.test', $baseAppUrl, file_get_contents($file))
        );

        $directory = getcwd().'/web/app/themes/lark-child';

        $output->writeln([
            "<info>Running: yarn install within {$directory}</>",
            '',
        ]);

        (new Process([
            'yarn',
            'install',
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
            "<info>Running: start within {$directory}</>",
            '',
        ]);

        (new Process([
            'yarn',
            'start',
        ]))
        ->setTimeout(null)
        ->setIdleTimeout(null)
        ->setWorkingDirectory($directory)
        ->run();
    }

    private function stepSeven(InputInterface $input, OutputInterface $output)
    {
        $directory = getcwd().'/web/app/themes/lark-child';

        $output->writeln([
            "<info>Running: yarn clean:views within {$directory}</>",
            '',
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
