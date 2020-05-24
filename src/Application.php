<?php


namespace Pff\DatabaseConfig;

use Exception;
use Pff\DatabaseConfig\Support\Arr;
use Pff\DatabaseConfig\Support\Str;
use ReflectionClass;
use ReflectionException;
use Symfony\Component\Console\Application as SymfonyApplication;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Finder\Finder;

class Application
{
    /**
     * @var SymfonyApplication
     */
    protected $app;

    /**
     * @throws Exception
     */
    public function run()
    {
        if (is_null($this->app)) {
            $this->app = new Console\Application("0.1");
            $this->load(__DIR__ . '/Command');
        }

        $output = new SymfonyStyle($input = new ArgvInput, new ConsoleOutput);
        $this->app->run($input, $output);
    }

    /**
     * Register all of the commands in the given directory.
     *
     * @param array|string $paths
     * @return void
     * @throws ReflectionException
     */
    public function load($paths)
    {
        $paths = array_unique(Arr::wrap($paths));

        $paths = array_filter($paths, function ($path) {
            return is_dir($path);
        });

        if (empty($paths)) {
            return;
        }

        $namespace = $this->getNamespace();

        foreach ((new Finder)->in($paths)->files() as $command) {
            $command = $namespace.str_replace(
                    ['/', '.php'],
                    ['\\', ''],
                    Str::after($command->getPathname(), __DIR__)
                );

            if (is_subclass_of($command, Command::class) &&
                ! (new ReflectionClass($command))->isAbstract()) {
                $this->app->add(new $command);
//                Artisan::starting(function ($artisan) use ($command) {
//                    $artisan->resolve($command);
//                });
            }
        }
    }

    public function getNamespace()
    {
        return __NAMESPACE__;
    }
}