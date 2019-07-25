<?php

namespace App\Service;

use DateTime;
use Psr\Log\AbstractLogger;
use Psr\Log\LoggerAwareTrait;

/**
 * Logger class with current time
 * -v   NOTICE
 * -vv  NOTICE & INFO
 * -vvv NOTICE & INFO & DEBUG
 */
class Logger extends AbstractLogger
{
    use LoggerAwareTrait;

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level   The log level
     * @param string $message The log message
     * @param array  $context The log context
     *
     * @return void
     */
    public function log($level, $message, array $context = array())
    {
        $message = '[{now}] ' . $message;
        $context['now'] = new DateTime();
        $this->logger->log($level, $message, $context);
    }
}
