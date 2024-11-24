<?php

namespace Php22\Config;

class Config
{
    /**
     * Load a configuration file from the project config directory.
     *
     * @param string $file
     * @return array
     * @throws \Exception
     */
    public static function load(string $file): array
    {
        $configPath = base_path("config/{$file}.php");

        if (!file_exists($configPath)) {
            throw new \Exception("Configuration file {$file} not found in {$configPath}");
        }

        return require $configPath;
    }
}
