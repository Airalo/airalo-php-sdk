<?php

/**
 * @param string $dir
 * @return void
 */
function requireFiles(string $dir): void
{
    $dh = opendir($dir);

    while ($file = readdir($dh)) {
        if ($file == '.' || $file == '..') {
            continue;
        }

        $path = $dir . '/' . $file;

        if (is_dir($path)) {
            requireFiles($path);
        } else {
            if (pathinfo($path, PATHINFO_EXTENSION) == 'php') {
                require($path);
            }
        }
    }

    closedir($dh);
}

requireFiles(__DIR__ . '/src');
