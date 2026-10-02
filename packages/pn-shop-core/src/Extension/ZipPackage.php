<?php

namespace PnShop\Extension;

use Illuminate\Support\Facades\File;
use PnShop\Extension\Exceptions\ExtensionException;
use ZipArchive;

/**
 * Unpacks an uploaded plugin archive into the extensions folder, checking every entry
 * before anything is written: no absolute paths, no "..", no symbolic links, only allowed
 * file types, and limits on size and file count. The manifest is read and validated from
 * the archive first.
 */
final class ZipPackage
{
    public const MAX_FILES = 5000;

    public const MAX_BYTES = 50 * 1024 * 1024;

    private const ALLOWED_EXTENSIONS = ['php', 'json', 'js', 'mjs', 'css', 'map', 'md', 'txt', 'xml', 'yml', 'yaml', 'neon', 'png', 'jpg', 'jpeg', 'gif', 'webp', 'avif', 'ico', 'woff', 'woff2', 'ttf', 'sig', 'lock', 'stub', 'csv', 'pot', 'po', 'mo'];

    /**
     * @return Manifest the installed plugin's manifest (not yet installed in the database)
     *
     * @throws ExtensionException
     */
    public static function extract(string $zipPath, string $extensionsPath, bool $replace = false): Manifest
    {
        $zip = new ZipArchive;

        if ($zip->open($zipPath, ZipArchive::RDONLY) !== true) {
            throw new ExtensionException(__('The file is not a valid zip archive.'));
        }

        try {
            $root = self::inspect($zip);
            $manifestJson = $zip->getFromName($root.Manifest::FILE);
            $data = json_decode((string) $manifestJson, true);

            // Validate the manifest from the archive before writing anything.
            $manifest = Manifest::fromArray(is_array($data) ? $data : [], 'archive');
            $target = rtrim($extensionsPath, '/').'/'.$manifest->id;

            if (is_dir($target) && ! $replace) {
                throw new ExtensionException(__(':id is already present. Update it instead.', ['id' => $manifest->id]));
            }

            $staging = rtrim($extensionsPath, '/').'/.staging-'.bin2hex(random_bytes(6));
            File::ensureDirectoryExists($staging);

            try {
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $name = (string) $zip->getNameIndex($i);
                    $relative = substr($name, strlen($root));

                    if ($relative === '' || str_ends_with($name, '/')) {
                        continue;
                    }

                    $destination = $staging.'/'.$relative;
                    File::ensureDirectoryExists(dirname($destination));

                    $stream = $zip->getStream($name);
                    if ($stream === false || file_put_contents($destination, $stream) === false) {
                        throw new ExtensionException(__('Could not extract :file.', ['file' => $relative]));
                    }
                }

                File::ensureDirectoryExists(dirname($target));

                if (is_dir($target)) {
                    File::deleteDirectory($target);
                }

                rename($staging, $target);
            } finally {
                if (is_dir($staging)) {
                    File::deleteDirectory($staging);
                }
            }

            return Manifest::fromDirectory($target);
        } finally {
            $zip->close();
        }
    }

    /**
     * Check every entry and find the folder holding pnshop.json (root or one top folder).
     *
     * @throws ExtensionException
     */
    private static function inspect(ZipArchive $zip): string
    {
        if ($zip->numFiles === 0 || $zip->numFiles > self::MAX_FILES) {
            throw new ExtensionException(__('The archive is empty or has too many files.'));
        }

        $bytes = 0;
        $roots = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = is_array($stat) ? (string) $stat['name'] : '';

            if ($name === '' || str_starts_with($name, '/') || str_contains($name, '\\') || preg_match('#(^|/)\.\.(/|$)#', $name) === 1 || str_contains($name, "\0") || preg_match('#^[A-Za-z]:#', $name) === 1) {
                throw new ExtensionException(__('The archive contains an unsafe path: :path', ['path' => $name]));
            }

            // Unix mode in the upper 16 bits of the external attributes; 0120000 is a symbolic link.
            if ($zip->getExternalAttributesIndex($i, $system, $attributes) && $system === ZipArchive::OPSYS_UNIX && (($attributes >> 16) & 0170000) === 0120000) {
                throw new ExtensionException(__('The archive contains a symbolic link: :path', ['path' => $name]));
            }

            if (! str_ends_with($name, '/')) {
                $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));
                $base = strtolower(basename($name));

                if (! in_array($extension, self::ALLOWED_EXTENSIONS, true) && ! in_array($base, ['license', 'readme', 'changelog'], true)) {
                    throw new ExtensionException(__('The archive contains a file type that is not allowed: :path', ['path' => $name]));
                }

                $bytes += (int) $stat['size'];
            }

            if (basename($name) === Manifest::FILE) {
                $roots[] = substr($name, 0, -strlen(Manifest::FILE));
            }
        }

        if ($bytes > self::MAX_BYTES) {
            throw new ExtensionException(__('The archive is larger than :size MB when unpacked.', ['size' => self::MAX_BYTES / 1024 / 1024]));
        }

        // pnshop.json at the root or directly inside a single top folder.
        $root = collect($roots)->first(fn (string $prefix) => $prefix === '' || substr_count($prefix, '/') === 1);

        if ($root === null) {
            throw new ExtensionException(__('The archive has no :file at its top level.', ['file' => Manifest::FILE]));
        }

        if ($root !== '') {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                if (! str_starts_with((string) $zip->getNameIndex($i), $root)) {
                    throw new ExtensionException(__('All files must be inside the plugin folder.'));
                }
            }
        }

        return $root;
    }
}
