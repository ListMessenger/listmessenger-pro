<?php

class LM_ZipArchive extends ZipArchive
{
    public function extractSubdirTo($destination, $subdir)
    {
        $numDirs = 0;
        $numFiles = 0;
        $errors = [];

        // Prepare dirs
        $destination = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $destination);
        $subdir = str_replace(['/', '\\'], '/', $subdir);

        if (substr($destination, mb_strlen(DIRECTORY_SEPARATOR, 'UTF-8') * -1) != DIRECTORY_SEPARATOR) {
            $destination .= DIRECTORY_SEPARATOR;
        }

        if (substr($subdir, -1) != '/') {
            $subdir .= '/';
        }

        // Extract files
        for ($i = 0; $i < $this->numFiles; ++$i) {
            $filename = $this->getNameIndex($i);

            if (substr($filename, 0, mb_strlen($subdir, 'UTF-8')) == $subdir) {
                $relativePath = substr($filename, mb_strlen($subdir, 'UTF-8'));
                $relativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);

                if (mb_strlen($relativePath, 'UTF-8') > 0) {
                    if (substr($filename, -1) == '/') {  // Directory
                        // New dir
                        if (!is_dir($destination.$relativePath)) {
                            if (mkdir($destination.$relativePath, 0755, true)) {
                                ++$numDirs;
                            } else {
                                $errors[$i] = $filename;
                            }
                        }
                    } else {
                        if (dirname($relativePath) != '.') {
                            if (!is_dir($destination.dirname($relativePath))) {
                                // New dir (for file)
                                if (mkdir($destination.dirname($relativePath), 0755, true)) {
                                    ++$numDirs;
                                } else {
                                    $errors[$i] = $relativePath;
                                }
                            }
                        }

                        // New file
                        if (file_put_contents($destination.$relativePath, $this->getFromIndex($i)) !== false) {
                            ++$numFiles;
                        } else {
                            $errors[$i] = $filename;
                        }
                    }
                }
            }
        }

        $results = [
            'numDirs' => $numDirs,
            'numFiles' => $numFiles,
            'errors' => $errors,
        ];

        return $results;
    }
}
