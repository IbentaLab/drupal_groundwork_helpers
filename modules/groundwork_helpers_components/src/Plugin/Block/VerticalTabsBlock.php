<?php

declare(strict_types=1);

namespace Drupal\groundwork_helpers_components\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Vertical Tabs block using the Vertical Content Tabs SDC.
 *
 * @Block(
 * id = "groundwork_vertical_tabs",
 * admin_label = @Translation("Vertical Content Tabs"),
 * category = @Translation("Groundwork Components"),
 * permission = "use groundwork vertical tabs component"
 * )
 */
class VerticalTabsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * Constructs a new VerticalTabsBlock instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RendererInterface $renderer
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'vertical_tabs_items' => [
        [
          'label' => '',
          'content' => ['value' => '', 'format' => 'basic_html'],
        ],
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $config = $this->getConfiguration();
    $saved_items = $config['vertical_tabs_items'] ?? [];

    $saved_items = array_filter($saved_items, function($item) {
      return !empty($item['label']) && !empty($item['content']['value']);
    });
    $saved_items = array_values($saved_items);

    $is_ajax_rebuild = $form_state->isRebuilding() && $form_state->getTriggeringElement();

    if (!$is_ajax_rebuild) {
      $form_state->set('item_count', NULL);
    }

    $item_count = $form_state->get('item_count');
    if ($item_count === NULL) {
      $item_count = !empty($saved_items) ? count($saved_items) : 1;
      $form_state->set('item_count', $item_count);
    }

    $form['items_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'items-wrapper'],
      '#tree' => TRUE,
    ];

    if ($is_ajax_rebuild) {
      $user_input = $form_state->getUserInput();
      // On AJAX rebuild, the values are in 'settings'.
      $current_items = $user_input['settings']['items_wrapper'] ?? $saved_items;
    } else {
      $current_items = $saved_items;
    }

    for ($i = 0; $i < $item_count; $i++) {
      if (!isset($current_items[$i])) {
        $current_items[$i] = [
          'label' => '',
          'content' => ['value' => '', 'format' => 'basic_html'],
        ];
      }

      $form['items_wrapper'][$i] = [
        '#type' => 'details',
        '#title' => $this->t('Item @num', ['@num' => $i + 1]),
        '#open' => TRUE,
        '#attributes' => ['class' => ['tabs-item-wrapper']],
      ];

      $form['items_wrapper'][$i]['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Tab Label'),
        '#default_value' => $current_items[$i]['label'] ?? '',
        '#required' => TRUE,
        '#maxlength' => 255,
      ];

      $content_value = '';
      $content_format = 'basic_html';
      if ($is_ajax_rebuild && isset($current_items[$i]['content'])) {
        $content_data = $current_items[$i]['content'];
        if (is_array($content_data)) {
          $content_value = $content_data['value'] ?? '';
          $content_format = $content_data['format'] ?? 'basic_html';
        }
      } elseif (isset($current_items[$i]['content'])) {
        if (is_array($current_items[$i]['content'])) {
          $content_value = $current_items[$i]['content']['value'] ?? '';
          $content_format = $current_items[$i]['content']['format'] ?? 'basic_html';
        } else {
          $content_value = $current_items[$i]['content'];
        }
      }

      $form['items_wrapper'][$i]['content'] = [
        '#type' => 'text_format',
        '#title' => $this->t('Tab Content'),
        '#default_value' => $content_value,
        '#format' => $content_format,
        '#required' => TRUE,
      ];

      // Use a standard container for actions to prevent them from being moved.
      $form['items_wrapper'][$i]['actions'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['container-inline', 'item-actions']],
      ];

      if ($item_count > 1) {
        $form['items_wrapper'][$i]['actions']['move_up'] = [
          '#type' => 'submit',
          '#value' => $this->t('Move Up'),
          '#name' => 'move_up_' . $i,
          '#submit' => [[static::class, 'moveItemCallback']],
          '#ajax' => [
            'callback' => [static::class, 'ajaxCallback'],
            'wrapper' => 'items-wrapper',
          ],
          '#limit_validation_errors' => [],
          '#disabled' => $i === 0,
        ];
        $form['items_wrapper'][$i]['actions']['move_down'] = [
          '#type' => 'submit',
          '#value' => $this->t('Move Down'),
          '#name' => 'move_down_' . $i,
          '#submit' => [[static::class, 'moveItemCallback']],
          '#ajax' => [
            'callback' => [static::class, 'ajaxCallback'],
            'wrapper' => 'items-wrapper',
          ],
          '#limit_validation_errors' => [],
          '#disabled' => $i === ($item_count - 1),
        ];
      }

      if ($item_count > 1) {
        $form['items_wrapper'][$i]['actions']['remove'] = [
          '#type' => 'submit',
          '#value' => $this->t('Remove'),
          '#name' => 'remove_' . $i,
          '#submit' => [[static::class, 'removeItemCallback']],
          '#ajax' => [
            'callback' => [static::class, 'ajaxCallback'],
            'wrapper' => 'items-wrapper',
          ],
          '#limit_validation_errors' => [],
          '#attributes' => ['class' => ['button--danger']],
        ];
      }
    }

    $form['add_item_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['container-inline']],
    ];
    $form['add_item_wrapper']['add_item'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add New Item'),
      '#submit' => [[static::class, 'addItemCallback']],
      '#ajax' => [
        'callback' => [static::class, 'ajaxCallback'],
        'wrapper' => 'items-wrapper',
      ],
      '#limit_validation_errors' => [],
    ];

    return $form;
  }

  /**
   * Submit callback for moving items up or down.
   */
  public static function moveItemCallback(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $name_parts = explode('_', $trigger['#name']);
    $direction = $name_parts[1];
    $index = (int) $name_parts[2];

    $user_input = $form_state->getUserInput();
    // Correctly access the nested items array from user input.
    $items = &$user_input['settings']['items_wrapper'];

    if (!is_array($items)) {
      return;
    }

    if ($direction === 'up' && $index > 0) {
      $swap_index = $index - 1;
    }
    elseif ($direction === 'down' && $index < (count($items) - 1)) {
      $swap_index = $index + 1;
    }
    else {
      return;
    }

    $temp = $items[$index];
    $items[$index] = $items[$swap_index];
    $items[$swap_index] = $temp;

    $form_state->setUserInput($user_input);
    $form_state->setRebuild();
  }


  /**
   * Submit callback for adding items.
   */
  public static function addItemCallback(array $form, FormStateInterface $form_state): void {
    $item_count = $form_state->get('item_count') ?? 1;
    $form_state->set('item_count', $item_count + 1);
    $form_state->setRebuild();
  }

  /**
   * Submit callback for removing items.
   */
  public static function removeItemCallback(array $form, FormStateInterface $form_state): void {
    $triggering_element = $form_state->getTriggeringElement();
    $item_index = (int) str_replace('remove_', '', $triggering_element['#name']);

    $item_count = $form_state->get('item_count') ?? 1;
    if ($item_count > 1) {
      $user_input = $form_state->getUserInput();
      // Correctly access the nested items array from user input.
      if (isset($user_input['settings']['items_wrapper'][$item_index])) {
        unset($user_input['settings']['items_wrapper'][$item_index]);
        $user_input['settings']['items_wrapper'] = array_values($user_input['settings']['items_wrapper']);
        $form_state->setUserInput($user_input);
      }
      $form_state->set('item_count', $item_count - 1);
    }
    $form_state->setRebuild();
  }

  /**
   * AJAX callback for form operations.
   */
  public static function ajaxCallback(array $form, FormStateInterface $form_state): array {
    // In a block configuration form, the form elements from blockForm()
    // are nested within the 'settings' key of the main form array.
    return $form['settings']['items_wrapper'];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $items = [];

    if (isset($values['items_wrapper']) && is_array($values['items_wrapper'])) {
      foreach ($values['items_wrapper'] as $item_values) {
        if (!empty(trim($item_values['label'])) &&
            !empty(trim($item_values['content']['value']))) {
          $items[] = [
            'label' => trim($item_values['label']),
            'content' => $item_values['content'],
          ];
        }
      }
    }

    if (empty($items)) {
      $items = [['label' => '', 'content' => ['value' => '', 'format' => 'basic_html']]];
    }

    $this->setConfigurationValue('vertical_tabs_items', $items);
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $config = $this->getConfiguration();
    $items = $config['vertical_tabs_items'] ?? [];

    $tab_items = [];
    foreach ($items as $item) {
      if (!empty($item['label']) && !empty($item['content'])) {
        $content = $item['content'];
        $content_html = '';

        if (is_array($content) && isset($content['value'])) {
          $processed_content = [
            '#type' => 'processed_text',
            '#text' => $content['value'],
            '#format' => $content['format'] ?? 'basic_html',
          ];
          $content_html = (string) $this->renderer->renderInIsolation($processed_content);
        }
        elseif (is_string($content)) {
          $content_html = $content;
        }

        if (!empty($content_html)) {
            $tab_items[] = [
              'label' => $item['label'],
              'content' => $content_html,
            ];
        }
      }
    }

    if (empty($tab_items)) {
      return [];
    }

    return [
      '#type' => 'component',
      '#component' => 'groundwork:vertical-tabs',
      '#props' => [
        'unique_id' => uniqid('vtabs_'),
        'items' => $tab_items,
      ],
      '#cache' => [
        'contexts' => ['url.path', 'url.query_args'],
      ],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return array_merge(parent::getCacheContexts(), ['url.path', 'url.query_args']);
  }

}
