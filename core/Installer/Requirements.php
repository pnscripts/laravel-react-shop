<?php

namespace PnShop\Installer;

/**
 * Server requirements for running PN Shop.
 */
final class Requirements
{
    public const PHP = '8.4.0';

    /** Required PHP extensions and what needs them. */
    private const EXTENSIONS = [
        'pdo' => 'Database access',
        'mbstring' => 'Text handling',
        'openssl' => 'Encryption',
        'tokenizer' => 'Laravel',
        'xml' => 'Laravel',
        'ctype' => 'Laravel',
        'fileinfo' => 'Upload type checks',
        'intl' => 'Prices and dates in each language',
        'bcmath' => 'Exact money arithmetic',
        'sodium' => 'Plugin signatures',
        'curl' => 'Payment gateways',
    ];

    /** Optional extensions. */
    private const OPTIONAL = [
        'zip' => 'Uploading plugins as zip archives',
        'gd' => 'Image resizing (or imagick)',
    ];

    /**
     * @return list<array{label: string, ok: bool, required: bool, detail: string}>
     */
    public function check(?string $databaseDriver = null): array
    {
        $checks = [[
            'label' => 'PHP '.self::PHP.' or newer',
            'ok' => version_compare(PHP_VERSION, self::PHP, '>='),
            'required' => true,
            'detail' => 'Running '.PHP_VERSION,
        ]];

        foreach (self::EXTENSIONS as $extension => $purpose) {
            $checks[] = ['label' => "PHP extension {$extension}", 'ok' => extension_loaded($extension), 'required' => true, 'detail' => $purpose];
        }

        foreach (self::OPTIONAL as $extension => $purpose) {
            $ok = extension_loaded($extension) || ($extension === 'gd' && extension_loaded('imagick'));
            $checks[] = ['label' => "PHP extension {$extension}", 'ok' => $ok, 'required' => false, 'detail' => $purpose];
        }

        if ($databaseDriver !== null) {
            $checks[] = ['label' => "PDO driver {$databaseDriver}", 'ok' => in_array($databaseDriver, \PDO::getAvailableDrivers(), true), 'required' => true, 'detail' => 'The chosen database'];
        }

        foreach ($this->writablePaths() as $path) {
            $checks[] = ['label' => 'Writable: '.$this->relative($path), 'ok' => is_writable($path), 'required' => true, 'detail' => 'The web server must be able to write here'];
        }

        return $checks;
    }

    /**
     * @param  list<array{label: string, ok: bool, required: bool, detail: string}>  $checks
     */
    public static function passes(array $checks): bool
    {
        foreach ($checks as $check) {
            if ($check['required'] && ! $check['ok']) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    private function writablePaths(): array
    {
        return [
            // The installer writes .env (or creates it in the project folder).
            is_file(app()->environmentFilePath()) ? app()->environmentFilePath() : base_path(),
            storage_path(),
            app()->bootstrapPath('cache'),
            public_path(),
        ];
    }

    private function relative(string $path): string
    {
        return ltrim(str_replace(base_path(), '', $path), '/') ?: '.';
    }
}
