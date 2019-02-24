<?php

namespace Drupal\migrate_directory\Plugin\migrate\source;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate\Row;
use FilesystemIterator;

/**
 * Provides a migration source for files in a directory.
 *
 * @MigrateSource(
 *   id = "directory",
 *   source_module = "migrate_directory"
 * )
 */
class MigrateDirectory extends SourcePluginBase {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function initializeIterator() {
    // Always get UNIX paths, skipping . and .., key as filename, and follow links.
    $flags = FilesystemIterator::UNIX_PATHS |
             FilesystemIterator::SKIP_DOTS |
             FilesystemIterator::KEY_AS_FILENAME |
             FilesystemIterator::FOLLOW_SYMLINKS;

    // Recurse through the directory.
    $files = new \RecursiveDirectoryIterator($this->configuration['path'], $flags);

    // A filter could be added here if necessary.
    if (!empty($this->configuration['pattern'])) {
        $pattern = $this->configuration['pattern'];

        $filter = new \RecursiveCallbackFilterIterator($files, function ($current, $key, $iterator) use ($pattern) {

          // Get the current item's name.
          /** @var \SplFileInfo $current */
          $filename = $current->getFilename();

          if ($current->isDir()) {
            // Always descend into directories.
            return TRUE;
          }

          // Match the filename against the pattern.
          return preg_match($pattern, $filename) === 1;
        });
    }
    else {
      $filter = $files;
    }

    // Get an iterator of our iterator...
    $iterator = new \RecursiveIteratorIterator($filter);

    // ...because we need to get the path and filename of each item...
    /** @var \SplFileInfo $fileinfo */
    $out = [];
    foreach ($iterator as $fileinfo) {
      $out[] = [
        'path' => $fileinfo->getPathname(),
        'filename' => $fileinfo->getFilename(),
      ];
    }

    // ...and turn it back into an iterator.
    return new \ArrayIterator($out);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $ids = [];

    $ids['path']['type'] = 'string';

    return $ids;
  }

  /**
   * {@inheritdoc}
   */
  public function fields() {
    return [
      'path' => $this->t('The file path'),
      'filename' => $this->t('The filename'),
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function __toString() {
    return (string) $this->configuration['path'];
  }
}
