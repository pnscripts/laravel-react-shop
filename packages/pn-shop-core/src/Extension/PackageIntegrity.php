<?php

namespace PnShop\Extension;

use PnShop\Extension\Exceptions\ExtensionException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

/**
 * File checksums of a plugin folder (to detect files changed after install) and optional
 * Ed25519 signatures over them.
 *
 * A signed plugin ships pnshop.sig: {"key": "<key id>", "signature": "<base64>"}, signing
 * the checksum list (see canonical()). Trusted public keys are configured in
 * pnshop.extensions.trusted_keys; with require_signatures on, unsigned plugins are refused.
 */
final class PackageIntegrity
{
    public const SIGNATURE_FILE = 'pnshop.sig';

    /**
     * @return array<string, string> relative path => sha256, sorted
     */
    public static function checksums(string $directory): array
    {
        $directory = rtrim($directory, '/');
        $sums = [];

        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS));

        foreach ($files as $file) {
            /** @var SplFileInfo $file */
            if (! $file->isFile() || $file->isLink()) {
                continue;
            }

            $relative = ltrim(str_replace('\\', '/', substr($file->getPathname(), strlen($directory))), '/');

            if ($relative === self::SIGNATURE_FILE || str_starts_with($relative, '.git/') || str_starts_with($relative, 'node_modules/')) {
                continue;
            }

            $sums[$relative] = (string) hash_file('sha256', $file->getPathname());
        }

        ksort($sums, SORT_STRING);

        return $sums;
    }

    /**
     * The signed text: one "sha256  path" line per file, sorted by path.
     *
     * @param  array<string, string>  $checksums
     */
    public static function canonical(array $checksums): string
    {
        ksort($checksums, SORT_STRING);

        return implode('', array_map(fn (string $path, string $sum) => "{$sum}  {$path}\n", array_keys($checksums), $checksums));
    }

    /**
     * Files added, changed or removed since the given checksums were taken.
     *
     * @param  array<string, string>  $expected
     * @return array{added: list<string>, changed: list<string>, removed: list<string>}
     */
    public static function compare(array $expected, string $directory): array
    {
        $actual = self::checksums($directory);

        return [
            'added' => array_values(array_diff(array_keys($actual), array_keys($expected))),
            'changed' => array_keys(array_filter($expected, fn (string $sum, string $path) => isset($actual[$path]) && $actual[$path] !== $sum, ARRAY_FILTER_USE_BOTH)),
            'removed' => array_values(array_diff(array_keys($expected), array_keys($actual))),
        ];
    }

    /**
     * Check the plugin's signature against the trusted keys.
     *
     * @return string|null the key id that signed it, or null when unsigned and signatures are optional
     *
     * @throws ExtensionException when the signature is invalid, from an unknown key, or missing but required
     */
    public static function verify(string $directory): ?string
    {
        $file = rtrim($directory, '/').'/'.self::SIGNATURE_FILE;
        $required = (bool) config('pnshop.extensions.require_signatures', false);

        if (! is_file($file)) {
            if ($required) {
                throw new ExtensionException(__('This plugin is not signed, and only signed plugins may be installed.'));
            }

            return null;
        }

        $data = json_decode((string) file_get_contents($file), true);
        $keyId = is_array($data) && is_string($data['key'] ?? null) ? $data['key'] : '';
        $signature = is_array($data) && is_string($data['signature'] ?? null) ? base64_decode($data['signature'], true) : false;
        $publicKey = base64_decode((string) (config('pnshop.extensions.trusted_keys', [])[$keyId] ?? ''), true);

        if ($publicKey === false || strlen($publicKey) !== SODIUM_CRYPTO_SIGN_PUBLICKEYBYTES) {
            throw new ExtensionException(__('This plugin is signed with an unknown key (:key).', ['key' => $keyId ?: '?']));
        }

        if ($signature === false || strlen($signature) !== SODIUM_CRYPTO_SIGN_BYTES
            || ! sodium_crypto_sign_verify_detached($signature, self::canonical(self::checksums($directory)), $publicKey)) {
            throw new ExtensionException(__('The plugin signature does not match its files.'));
        }

        return $keyId;
    }

    /**
     * Sign a plugin folder with a base64 Ed25519 secret key (for plugin authors).
     */
    public static function sign(string $directory, string $keyId, string $secretKeyBase64): void
    {
        $secret = base64_decode($secretKeyBase64, true);

        if ($secret === false || strlen($secret) !== SODIUM_CRYPTO_SIGN_SECRETKEYBYTES) {
            throw new ExtensionException(__('The secret key is not a base64 Ed25519 secret key.'));
        }

        $signature = sodium_crypto_sign_detached(self::canonical(self::checksums($directory)), $secret);
        sodium_memzero($secret);

        file_put_contents(rtrim($directory, '/').'/'.self::SIGNATURE_FILE, json_encode(['key' => $keyId, 'signature' => base64_encode($signature)], JSON_PRETTY_PRINT)."\n");
    }
}
