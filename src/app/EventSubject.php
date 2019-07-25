<?php

namespace App;

use ArrayObject;
use SplObjectStorage;
use SplSubject;
use SplObserver;
use Psr\Log\LoggerInterface;

/**
 * Event subject class
 *
 * @see https://www.php.net/manual/zh/class.splsubject.php
 */
class EventSubject extends ArrayObject implements SplSubject
{
    /**
     * Attached event observers
     *
     * @var \SplObjectStorage
     */
    private $_observers;

    /**
     * Logger instance
     *
     * @var \Psr\Log\LoggerInterface
     */
    private $_logger;

    /**
     * Construct a new event subject.
     *
     * @param array                    $input  Input array
     * @param \Psr\Log\LoggerInterface $logger Logger instance
     */
    public function __construct(array $input, LoggerInterface $logger)
    {
        parent::__construct($input);
        $this->_observers = new SplObjectStorage();
        $this->_logger = $logger;
    }

    /**
     * Attach an SplObserver.
     *
     * @param \SplObserver $observer Event observer
     *
     * @return void
     */
    public function attach(SplObserver $observer): void
    {
        $this->_observers->attach($observer);
    }

    /**
     * Detach an observer.
     *
     * @param \SplObserver $observer Event observer
     *
     * @return void
     */
    public function detach(SplObserver $observer): void
    {
        $this->_observers->detach($observer);
    }

    /**
     * Notify an observer.
     *
     * @return void
     */
    public function notify(): void
    {
        foreach ($this->_observers as $observer) {
            $observer->update($this);
            $results = $observer->getResults();
            foreach ($results as $result) {
                $level = array_shift($result);
                call_user_func_array([$this->_logger, $level], $result);
            }
        }
    }

    /**
     * Get environment variables
     *
     * @return array
     */
    public function getEnv(): array
    {
        if ($this['object_kind'] == 'push') {
            return [
                // The commit hash being checked out.
                'GIT_COMMIT' => $this['checkout_sha'],
                // The remote branch name, if any.
                'GIT_BRANCH' => substr($this['ref'], 11),
                // The remote URL.
                'GIT_URL' => $this['repository']['url'],
                // Gitlab homepage URL.
                'GIT_HOMEPAGE' => $this['repository']['homepage'],
                // The configured Git committer name, if any.
                'GIT_COMMITTER_NAME' => $this['user_name'],
                // The configured Git committer email, if any.
                'GIT_COMMITTER_EMAIL' => $this['user_email'],
            ];
        } else {
            return [];
        }
    }
}
