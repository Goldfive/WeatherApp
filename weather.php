#!/usr/bin/env php
<?php
require __DIR__.'/vendor/autoload.php';

use App\Command\WeatherCommand;
use Symfony\Component\Console\Application;
use App\Kernel;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

$input = new \Symfony\Component\Console\Input\ArgvInput();
if (null !== $env = $input->getParameterOption(['--env', '-e'], null, true)) {
    putenv('APP_ENV='.$_SERVER['APP_ENV'] = $_ENV['APP_ENV'] = $env);
}

if ($input->hasParameterOption('--no-debug', true)) {
    putenv('APP_DEBUG='.$_SERVER['APP_DEBUG'] = $_ENV['APP_DEBUG'] = '0');
}

require dirname(__DIR__).'/weatherapp/config/bootstrap.php';

if ($_SERVER['APP_DEBUG']) {
    umask(0000);

    if (class_exists(\Symfony\Component\Debug\Debug::class)) {
        \Symfony\Component\Debug\Debug::enable();
    }
}

$kernel = new Kernel($_SERVER['APP_ENV'], false);
$kernel->boot();

$application = new Application('WeatherCommand', '1.0.0');
$command = new WeatherCommand($kernel->getContainer());

$application->add($command);

$application->setDefaultCommand($command->getName(), true);
$application->run($input);
