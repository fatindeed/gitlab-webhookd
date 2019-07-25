<?php

/**
 * Service config
 *
 * @see https://symfony.com/doc/current/service_container.html
 * @see https://symfony.com/doc/current/service_container/autowiring.html
 */

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\DependencyInjection\AddConsoleCommandPass;
use App\EventSubject;
use App\Service\Logger;
use App\Service\Webhookd;
use App\Command\WebhookdCommand;

$container->autowire(Logger::class)->setPublic(false);
$container->setAlias(LoggerInterface::class, Logger::class);
$container->autowire(EventSubject::class)->setPublic(false);
$container->autowire(Webhookd::class)->setPublic(false);
$container->autowire(WebhookdCommand::class)->setPublic(false)
    ->addTag('console.command', ['command' => 'webhookd']);

$container->addCompilerPass(new AddConsoleCommandPass());
$container->compile();
