<?php

namespace Hunjian\AliyunImsMixcut\Storage;

use RuntimeException;

/**
 * Class StorageFileWriter
 *
 * Writes exported content to disk and creates parent directories when needed.
 */
class StorageFileWriter
{
    /**
     * Write content to file.
     *
     * @param string $path
     * @param string $content
     *
     * @return string
     */
    public function write($path, $content)
    {
        $directory = dirname($path);

        if (!is_dir($directory) && !mkdir($directory, 0777, true) && !is_dir($directory)) {
            throw new RuntimeException('Failed to create directory: ' . $directory);
        }

        if (file_put_contents($path, $content) === false) {
            throw new RuntimeException('Failed to write file: ' . $path);
        }

        return $path;
    }
}
