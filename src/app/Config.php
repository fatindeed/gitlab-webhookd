<?php

namespace App;

use ArrayObject;
use InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;

/**
 * Load config with sanitization
 */
class Config extends ArrayObject
{
    /**
     * Construct a new config
     *
     * @param array $input Input array
     */
    public function __construct(array $input = [])
    {
        parent::__construct(
            $input + [
            // 'workdir' => getcwd(),
                'port'   => 9501,
                'events' => []
            ]
        );
        $this->sanitize();
    }

    /**
     * Load config.yaml
     *
     * @param string $filename The path to the YAML file to be parsed
     *
     * @return void
     */
    public function loadYaml(string $filename)
    {
        $config = Yaml::parseFile($filename);
        $this->exchangeArray($config + $this->getArrayCopy());
        $this->sanitize();
    }

    /**
     * Sanitize config values
     *
     * Port:
     * - Well-known ports: 0 to 1023
     * - Registered/user ports: 1024 to 49151
     * - Dynamic/private ports: 49152 to 65535
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    private function sanitize(): void
    {
        $port = filter_var(
            $this['port'],
            FILTER_VALIDATE_INT,
            [
                'options' => ['min_range' => 0, 'max_range' => 49151]
            ]
        );
        if ($port === false) {
            throw new InvalidArgumentException('Invalid port: ' . $this['port'] . '.', 1001);
        }
        $this['port'] = $port;
    }
}
