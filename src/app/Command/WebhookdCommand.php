<?php

namespace App\Command;

use App\Config;
use App\EventSubject;
use App\EventObserver;
use App\Service\Logger;
use Swoole\Http\Server;
use App\Service\Webhookd;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Webhookd command
 *
 * @see https://symfony.com/doc/current/components/console.html
 */
class WebhookdCommand extends Command
{
    /**
     * Webhookd instance
     *
     * @var \App\Service\Webhookd
     */
    private $_webhookd;

    /**
     * Event subject instance
     *
     * @var \App\EventSubject
     */
    private $_subject;

    /**
     * Logger instance
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * Construct a new webhookd command
     *
     * @param \App\Service\Webhookd    $webhookd Webhookd instance
     * @param \App\EventSubject        $subject  Event subject instance
     * @param \Psr\Log\LoggerInterface $logger   Logger instance
     */
    public function __construct(Webhookd $webhookd, EventSubject $subject, LoggerInterface $logger)
    {
        $this->_webhookd = $webhookd;
        $this->_subject = $subject;
        $this->_logger = $logger;
        parent::__construct();
    }

    /**
     * Configures the current command.
     *
     * @return void
     */
    protected function configure()
    {
        $this->setDescription('Start a http server to handle gitlab webhook events.');
    }

    /**
     * Executes the current command.
     *
     * @param \Symfony\Component\Console\Input\InputInterface   $input  Input instance
     * @param \Symfony\Component\Console\Output\OutputInterface $output Output instance
     *
     * @return int|null null or 0 if everything went fine, or an error code
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        if ($this->_logger instanceof Logger) {
            $this->_logger->setLogger(new ConsoleLogger($output));
        }
        $config = new Config();
        $config->loadYaml('config.yaml');
        foreach ($config['events'] as $event) {
            $observer = new EventObserver($event);
            $this->_subject->attach($observer);
        }
        $server = new Server('0.0.0.0', $config['port']);
        $this->_webhookd->setServer($server);
        $this->_webhookd->start();
    }
}
