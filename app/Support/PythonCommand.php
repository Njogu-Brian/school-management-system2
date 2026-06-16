<?php

namespace App\Support;

use Symfony\Component\Process\Process;

class PythonCommand
{
    public static function resolve(): ?string
    {
        $configured = config('services.python.binary');
        if (is_string($configured) && $configured !== '') {
            return self::canImportPdfplumber($configured) ? $configured : null;
        }

        foreach (self::candidatePaths() as $candidate) {
            if (self::canImportPdfplumber($candidate)) {
                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    protected static function candidatePaths(): array
    {
        $candidates = [];

        $venvPython = self::projectVenvPython();
        if ($venvPython !== null) {
            $candidates[] = $venvPython;
        }

        $commandNames = PHP_OS_FAMILY === 'Windows'
            ? ['python', 'py', 'python3']
            : ['python3', 'python'];

        foreach ($commandNames as $command) {
            $executable = self::resolveExecutablePath($command);
            if ($executable !== null) {
                $candidates[] = $executable;
            }
        }

        return array_values(array_unique($candidates));
    }

    protected static function projectVenvPython(): ?string
    {
        $relative = PHP_OS_FAMILY === 'Windows'
            ? 'app/Services/python/venv/Scripts/python.exe'
            : 'app/Services/python/venv/bin/python';

        $path = base_path($relative);

        return is_file($path) ? $path : null;
    }

    protected static function resolveExecutablePath(string $command): ?string
    {
        $process = new Process([$command, '-c', 'import sys; print(sys.executable)'], base_path());
        $process->setTimeout(15);
        $process->run();

        if (! $process->isSuccessful()) {
            return null;
        }

        $path = trim($process->getOutput());

        return $path !== '' && is_file($path) ? $path : null;
    }

    protected static function canImportPdfplumber(string $pythonExecutable): bool
    {
        if (! is_file($pythonExecutable)) {
            $process = new Process([$pythonExecutable, '--version'], base_path());
            $process->setTimeout(15);
            $process->run();

            if (! $process->isSuccessful()) {
                return false;
            }
        }

        $check = new Process([$pythonExecutable, '-c', 'import pdfplumber'], base_path());
        $check->setTimeout(30);
        $check->run();

        return $check->isSuccessful();
    }
}
