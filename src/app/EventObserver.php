<?php

namespace App;

use SplSubject;
use SplObserver;
use Symfony\Component\Process\Process;

/**
 * Event observer class
 *
 * @see https://www.php.net/manual/zh/class.splobserver.php
 */
class EventObserver implements SplObserver
{
    /**
     * Event handler config
     *
     * @var array
     */
    private $_config;

    /**
     * Event handler results
     *
     * @var array
     */
    private $_results = [];

    /**
     * Construct a new event observer
     *
     * @param array $config Event handler config
     */
    public function __construct(array $config)
    {
        $this->_config = $config + [
            'type' => '*',
            'conditions' => [],
            'commands' => []
        ];
    }

    /**
     * Receive update from subject
     *
     * @param \SplSubject $subject Event subject
     *
     * @return void
     */
    public function update(SplSubject $subject): void
    {
        if ($subject instanceof EventSubject) {
            $this->handleEvent($subject);
        }
    }

    /**
     * Handle event.
     *
     * @param \App\EventSubject $subject Event subject
     *
     * @return void
     */
    private function handleEvent(EventSubject $subject): void
    {
        $this->_results = [];
        $this->_config['conditions']['object_kind'] = $this->_config['type'];
        foreach ($this->_config['conditions'] as $key => $expected) {
            if (strpos($key, '.') !== false) {
                list($attr, $subkey) = explode('.', $key);
                $value = $subject[$attr][$subkey];
            } else {
                $value = $subject[$key];
            }
            if (self::validate($value, $expected) === false) {
                $this->_results[] = ['debug', '{event} event ignored, expected: {expected}, actual: {actual}.', [
                    'event' => $this->_config['name'],
                    'expected' => (is_array($expected) ? json_encode($expected) : $expected),
                    'actual' => $value,
                ]];
                return;
            }
        }
        foreach ($this->_config['commands'] as $command) {
            $process = new Process($command, null, $subject->getEnv());
            $process->run();
            $output = $process->getOutput();
            $this->_results[] = [
                ($process->isSuccessful() ? 'notice' : 'error'),
                '{event}: {command} (return:{code}){output}',
                [
                    'event' => $this->_config['name'],
                    'command' => implode(' ', $command),
                    'code' => $process->getExitCode(),
                    'output' => ($output ? PHP_EOL . $output : '')
                ]
            ];
        }
    }

    /**
     * Get results
     *
     * @return array
     */
    public function getResults(): array
    {
        return $this->_results;
    }

    /**
     * Validate
     *
     * @param mixed $actual   Actual value
     * @param mixed $expected Expected value
     *
     * @return bool
     */
    public static function validate($actual, $expected): bool
    {
        if (is_array($expected)) {
            $result = false;
            foreach ($expected as $item) {
                if ($result = self::validate($actual, $item)) {
                    break;
                }
            }
            return $result;
        } elseif (strpos($expected, '*') !== false) {
            return preg_match('#^' . str_replace('*', '.*', $expected) . '$#', $actual) > 0;
        } else {
            return $actual == $expected;
        }
    }
}
