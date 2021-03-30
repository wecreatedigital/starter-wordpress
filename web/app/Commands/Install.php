<?php

namespace App\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Filesystem;
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

        $this->addOption(
            'stack',
            null,
            InputOption::VALUE_OPTIONAL,
            'What stack should be used? i.e. Tailwindcss/Bootstrap',
            false
        );

        $this->setName('lark:install');

        $this->setDescription('Command that sequentially runs lark installation commands from Terminal.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Bit of added security, you cannot run this installation script when .env exists
        if (file_exists(getcwd().'/.env')) {
            (new SymfonyStyle($input, $output))->error('Cannot run this command when env exists.');

            return Command::FAILURE;
        }

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
            '1. Create database and update .env',
            "2. Visit https://{$this->appName}.test/wp/wp-admin and follow installation process",
            "3. Switch over the theme to 'Lark Starter Child Theme'",
            "4. See that the installation has worked by running 'yarn start' within the child theme directory",
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

    private function IsWooCommerce(InputInterface $input, OutputInterface $output): bool
    {
        $output->writeln([
            '',
            "Is '{$this->appName}' a WooCommerce build?",
            '',
        ]);

        $question = new ConfirmationQuestion(
            '<question>Would you like to copy WooCommerce stubs?</question> (y/N)',
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
        $directory = getcwd().'/web/app/themes/lark-child';

        if ($this->IsWooCommerce($input, $output)) {
            $output->writeln([
                '<info>Updated composer.json with WooCommerce dependancies</>',
            ]);

            $filesystem = new Filesystem();
            $filesystem->copy($directory.'/stub/woocommerce/composer.json', $directory.'/composer.json');

            $output->writeln([
                '<info>Add SageWoocommerce to namespaces</>',
            ]);

            $filePath = $directory.'/config/view.php';
            file_put_contents(
                $filePath,
                str_replace(
                    "// 'MyPlugin' => WP_PLUGIN_DIR . '/my-plugin/resources/views',",
                    "'SageWoocommerce' => get_theme_file_path('/vendor/roots/sage-woocommerce/src/resources/views'),",
                    file_get_contents($filePath)
                )
            );

            $output->writeln([
                '<info>Uncomment WooCommerce SASS</>',
            ]);

            $filePath = $directory.'/resources/assets/styles/app.scss';
            file_put_contents(
                $filePath,
                str_replace(
                    '// @import "woocommerce";',
                    '@import "woocommerce";',
                    file_get_contents($filePath)
                )
            );

            $output->writeln([
                '<info>Uncomment WooCommerce library functions</>',
            ]);

            $filePath = $directory.'/functions.php';
            file_put_contents(
                $filePath,
                str_replace(
                    "// 'Library/woocommerce',",
                    "'Library/woocommerce',",
                    file_get_contents($filePath)
                )
            );
        }

        if ( ! is_null($input->getOption('stack'))
        && in_array($input->getOption('stack'), ['tailwindcss', 'tailwind'])) {
            $output->writeln([
                '<info>Copying TailwindCSS stubs</>',
            ]);

            file_put_contents(
                getcwd().'/web/app/themes/lark-child/tailwind.config.js',
                file_get_contents(getcwd().'/config/stubs/tailwindcss/tailwind.config.js')
            );

            file_put_contents(
                getcwd().'/web/app/themes/lark-child/package.json',
                file_get_contents(getcwd().'/config/stubs/tailwindcss/package.json')
            );

            file_put_contents(
                getcwd().'/web/app/themes/lark-child/webpack.mix.js',
                file_get_contents(getcwd().'/config/stubs/tailwindcss/webpack.mix.js')
            );

            $filesystem = new Filesystem();

            $filesystem->remove([
                getcwd().'/web/app/themes/lark-child/resources/assets/scripts',
                getcwd().'/web/app/themes/lark-child/resources/assets/styles',
            ]);

            $filesystem->mirror(
                getcwd().'/config/stubs/tailwindcss/assets/scripts',
                getcwd().'/web/app/themes/lark-child/resources/assets/scripts'
            );

            $filesystem->mirror(
                getcwd().'/config/stubs/tailwindcss/assets/styles',
                getcwd().'/web/app/themes/lark-child/resources/assets/styles'
            );
        }
    }

    private function stepFour(InputInterface $input, OutputInterface $output)
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

    private function stepFive(InputInterface $input, OutputInterface $output)
    {
        $directory = getcwd().'/web/app/themes/lark-child';

        // Storage::copy($directory.'/stub/woocommerce/composer.json', $directory.'composer.json');

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

    private function stepSix(InputInterface $input, OutputInterface $output)
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

    private function stepSeven(InputInterface $input, OutputInterface $output)
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

    private function stepEight(InputInterface $input, OutputInterface $output)
    {
        $baseAppUrl = "{$this->appName}";
        $directory = getcwd();

        $output->writeln([
            '<info>Creating env from sample</>',
        ]);

        $filePath = getcwd().'/.env.example';

        file_put_contents(
            $filePath,
            str_replace('starter', $baseAppUrl, file_get_contents($filePath))
        );

        file_put_contents(
            $filePath,
            str_replace('unique_', substr(str_shuffle(MD5(microtime())), 0, 6).'_', file_get_contents($filePath))
        );

        (new Process([
            'cp',
            '.env.example',
            '.env',
        ]))
        ->setTimeout(null)
        ->setIdleTimeout(null)
        ->setWorkingDirectory($directory)
        ->run();
    }
}
