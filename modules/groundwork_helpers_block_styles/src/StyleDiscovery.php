<?php

namespace Drupal\groundwork_helpers_block_styles;

use Drupal\Core\Extension\ThemeHandlerInterface;

/**
 * Service to discover available block styles from the Groundwork theme.
 */
class StyleDiscovery {

  protected $themeHandler;

  /**
   * Defines the custom sort order for categories.
   *
   * @var array
   */
  private $categoryOrder = [
    '🔲 Layout' => 1,
    '📐 Spacing' => 2,
    '🧱 Box & Borders' => 3,
    '🔤 Typography' => 4,
    '🎨 Colors' => 5,
    '✨ Effects' => 6,
    'Uncategorized' => 99,
  ];

  /**
   * Constructs a new StyleDiscovery object.
   */
  public function __construct(ThemeHandlerInterface $theme_handler) {
    $this->themeHandler = $theme_handler;
  }

  /**
   * Scans the Groundwork theme's CSS for BSCs and groups them by category.
   *
   * @return array
   * A categorized and ordered array of available style classes.
   */
  public function getStyles() {
    $theme_path = $this->themeHandler->getTheme('groundwork')->getPath();
    $bsc_path = $theme_path . '/css/block-style-components';
    $discovered_files = [];

    if (!is_dir($bsc_path)) {
      return [];
    }

    foreach (scandir($bsc_path) as $file) {
      if (pathinfo($file, PATHINFO_EXTENSION) === 'css') {
        $content = file_get_contents($bsc_path . '/' . $file);

        $file_info = [
          'filename' => pathinfo($file, PATHINFO_FILENAME),
          'category' => 'Uncategorized',
          'order' => 0,
          'description' => '',
          'components' => [],
        ];

        if (preg_match('/^\s*\/\*\*(.*?)\*\//s', $content, $first_docblock_match)) {
          if (preg_match('/@category\s+(.+)/', $first_docblock_match[1], $cat_match)) {
            $file_info['category'] = trim($cat_match[1]);
          }
          if (preg_match('/@order\s+(-?\d+)/', $first_docblock_match[1], $order_match)) {
            $file_info['order'] = (int) $order_match[1];
          }
          if (preg_match('/@description\s+(.+)/', $first_docblock_match[1], $desc_match)) {
            $file_info['description'] = trim($desc_match[1]);
          }
        }

        preg_match_all('/\/\*\*(.*?)\*\//s', $content, $component_matches);
        foreach ($component_matches[1] as $docblock) {
          if (strpos($docblock, '@blockStyleComponent true') !== false) {
            if (preg_match('/@name\s+([\.a-zA-Z0-9_-]+)/', $docblock, $name)) {
              $description = preg_match('/@description\s+(.+)/', $docblock, $desc) ? trim($desc[1]) : '';
              $file_info['components'][trim($name[1], '.')] = $description;
            }
          }
        }

        if (!empty($file_info['components'])) {
          $discovered_files[] = $file_info;
        }
      }
    }

    usort($discovered_files, function ($a, $b) {
      if ($a['order'] == $b['order']) {
        return strcmp($a['filename'], $b['filename']);
      }
      return ($a['order'] < $b['order']) ? -1 : 1;
    });

    $styles = [];
    foreach ($discovered_files as $file_data) {
      $filename_key = $file_data['filename'];
      $styles[$file_data['category']][$filename_key] = [
        'description' => $file_data['description'],
        'components' => $file_data['components'],
      ];
    }

    // Sort the top-level categories based on the custom order.
    uksort($styles, function ($a, $b) {
      $order_a = $this->categoryOrder[$a] ?? 999;
      $order_b = $this->categoryOrder[$b] ?? 999;
      if ($order_a == $order_b) {
        return strcmp($a, $b);
      }
      return ($order_a < $order_b) ? -1 : 1;
    });

    return $styles;
  }
}
