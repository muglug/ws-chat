<?php

namespace App\MigrationsComponent\Internal;

use App\MigrationsComponent\Migration;

class MigrationsLoader
{
    /**
     * @var string
     */
    private $migrationsFolderPath;

    /**
     * @param string $migrationsFolderPath
     */
    public function __construct(string $migrationsFolderPath)
    {
        $this->migrationsFolderPath = $migrationsFolderPath;
    }

    /**
     * @return Migration[] [version(int) => Migration class instance]
     * @throws \ReflectionException
     * @throws MigratorException
     */
    public function loadAllMigrations(): array {
        $migrationFiles = [];
        $scandir = scandir($this->migrationsFolderPath);
        foreach (array_diff($scandir, ['.', '..']) as $filename) {
            $fullClassName = $this->getFullClassName($filename);
            $migrationFiles[$fullClassName::currentVersion()] = $fullClassName;
        }

        $res = ksort($migrationFiles);
        if($res === false) {
            throw new MigratorException('Can`t use ksort()');
        }

        $migrations = [];
        $prevVersion = 0;
        foreach ($migrationFiles as $version => $fullClassName) {
            $migrations[$version] = new $fullClassName($prevVersion);
            $prevVersion = $version;
        }
        return $migrations;
    }

    /**
     * @psalm-return class-string<Migration>
     * @throws MigratorException
     */
    private function getFullClassName(string $migrationFileName): string
    {
        $migrationsNamespace = 'App\Migration';
        preg_match('/^(Version\d+)\.php$/', $migrationFileName, $matches);
        if(!isset($matches[1])) {
            throw new MigratorException('Incorrect migration filename');
        }
        $className = $migrationsNamespace . '\\' . $matches[1];
        
        if (!class_exists($className) || !is_a($className, Migration::class, true)) {
            throw new \UnexpectedValueException('Class ' . $className . ' should exist');
        }
        
        return $className;
    }

    private function isMigrationFile(string $filename): bool
    {
        return (bool)preg_match('/^Version\d+\.php$/', $filename);
    }
}
