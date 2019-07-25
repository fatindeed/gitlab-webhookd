#!/usr/bin/env php
<?php

/**
 * Gitlab webhookd
 *
 * @author  James Zhu <168262+fatindeed@users.noreply.github.com>
 * @license MIT https://mit-license.org/
 */

declare(strict_types=1);

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Config\FileLocator;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

$container = new ContainerBuilder();

$loader = new PhpFileLoader($container, new FileLocator(__DIR__ . '/config'));
$loader->load('services.php');

$application = new Application();
$application->setCommandLoader($container->get('console.command_loader'));
$application->run();
