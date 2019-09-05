<?php

/**
 * Service config
 *
 * @see https://symfony.com/doc/current/service_container.html
 * @see https://symfony.com/doc/current/service_container/autowiring.html
 */

use Symfony\Component\Console\DependencyInjection\AddConsoleCommandPass;

$container->autowire(App\Service\Logger::class)->setPublic(false);
$container->setAlias(Psr\Log\LoggerInterface::class, App\Service\Logger::class);
$container->autowire(App\EventSubject::class)->setPublic(false);
$container->autowire(App\Service\Webhookd::class)->setPublic(false);
$container->autowire(App\Command\WebhookdCommand::class)->setPublic(false)
    ->addTag('console.command', ['command' => 'webhookd']);

$container->addCompilerPass(new AddConsoleCommandPass());
$container->compile();
