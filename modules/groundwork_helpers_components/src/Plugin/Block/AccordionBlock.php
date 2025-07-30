<?php

declare(strict_types=1);

namespace Drupal\groundwork_helpers_components\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides an Accordion block using the Content Accordion SDC.
 *
 * @Block(
 *   id = "groundwork_accordion",
 *   admin_label = @Translation("Content Accordion"),
 *   category = @Translation("Groundwork Components"),
 *   context_definitions = {
 *     "layout_builder.entity" = @ContextDefinition("entity", required = FALSE),
 *   }
 * )
 */
class AccordionBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * Constructs a new AccordionBlock instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
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
      'accordion_items' => [
        [
          'title' => '',
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
    $saved_items = $config['accordion_items'] ?? [];

    // Filter out any empty saved items first
    $saved_items = array_filter($saved_items, function($item) {
      return !empty($item['title']) && !empty($item['content']['value']);
    });
    $saved_items = array_values($saved_items); // Re-index

    // Check if this is an AJAX rebuild
    $is_ajax_rebuild = $form_state->isRebuilding() && $form_state->getTriggeringElement();

    // Initialize item count - clear any existing state on fresh load
    if (!$is_ajax_rebuild) {
      $form_state->set('item_count', NULL);
    }

    $item_count = $form_state->get('item_count');
    if ($item_count === NULL) {
      $item_count = !empty($saved_items) ? count($saved_items) : 1;
      $form_state->set('item_count', $item_count);
    }

    $form['#attached']['library'][] = 'core/drupal.tabledrag';

    $form['items_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'items-wrapper'],
    ];

    $form['items_wrapper']['items'] = [
      '#type' => 'table',
      '#header' => [
        $this->t('Item'),
        $this->t('Weight'),
        $this->t('Operations'),
      ],
      '#empty' => $this->t('No items yet.'),
      '#tableselect' => FALSE,
      '#tabledrag' => [
        [
          'action' => 'order',
          'relationship' => 'sibling',
          'group' => 'item-weight',
        ],
      ],
      '#tree' => TRUE,
    ];

    // Determine data source
    if ($is_ajax_rebuild) {
      $user_input = $form_state->getUserInput();
      $current_items = $user_input['items_wrapper']['items'] ?? $saved_items;
    } else {
      $current_items = $saved_items;
    }

    // Build form items
    for ($i = 0; $i < $item_count; $i++) {
      // Only create empty items if we don't have data for this index
      if (!isset($current_items[$i])) {
        $current_items[$i] = [
          'title' => '',
          'content' => ['value' => '', 'format' => 'basic_html'],
          'weight' => $i,
        ];
      }

      $weight = isset($current_items[$i]['weight']) ? $current_items[$i]['weight'] : $i;

      $form['items_wrapper']['items'][$i] = [
        '#attributes' => [
          'class' => ['draggable'],
        ],
        '#weight' => $weight,
      ];

      // Item content cell
      $form['items_wrapper']['items'][$i]['item'] = [
        '#type' => 'details',
        '#title' => $this->t('Item @num', ['@num' => $i + 1]),
        '#open' => TRUE,
      ];

      $form['items_wrapper']['items'][$i]['item']['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Heading'),
        '#default_value' => $current_items[$i]['title'] ?? '',
        '#required' => TRUE,
        '#maxlength' => 255,
      ];

      $content_value = '';
      $content_format = 'basic_html';

      if ($is_ajax_rebuild && isset($current_items[$i]['item']['content'])) {
        $content_data = $current_items[$i]['item']['content'];
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

      $form['items_wrapper']['items'][$i]['item']['content'] = [
        '#type' => 'text_format',
        '#title' => $this->t('Panel Content'),
        '#default_value' => $content_value,
        '#format' => $content_format,
        '#required' => TRUE,
      ];

      // Weight cell
      $form['items_wrapper']['items'][$i]['weight'] = [
        '#type' => 'weight',
        '#title' => $this->t('Weight'),
        '#title_display' => 'invisible',
        '#default_value' => $weight,
        '#attributes' => ['class' => ['item-weight']],
      ];

      // Operations cell
      $form['items_wrapper']['items'][$i]['operations'] = [
        '#type' => 'container',
      ];

      if ($item_count > 1) {
        $form['items_wrapper']['items'][$i]['operations']['remove'] = [
          '#type' => 'submit',
          '#value' => $this->t('Remove'),
          '#name' => 'remove_' . $i,
          '#submit' => [[static::class, 'removeItemCallback']],
          '#ajax' => [
            'callback' => [static::class, 'ajaxCallback'],
            'wrapper' => 'items-wrapper',
          ],
          '#limit_validation_errors' => [],
          '#attributes' => [
            'class' => ['button--small', 'button--danger'],
          ],
        ];
      }
    }

    $form['items_wrapper']['add_item'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add New Item'),
      '#submit' => [[static::class, 'addItemCallback']],
      '#ajax' => [
        'callback' => [static::class, 'ajaxCallback'],
        'wrapper' => 'items-wrapper',
      ],
      '#limit_validation_errors' => [],
      '#attributes' => [
        'class' => ['button--primary'],
      ],
    ];

    return $form;
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
      // Get current user input and remove the specified item
      $user_input = $form_state->getUserInput();
      if (isset($user_input['items_wrapper']['items'][$item_index])) {
        unset($user_input['items_wrapper']['items'][$item_index]);
        // Re-index the array
        $user_input['items_wrapper']['items'] = array_values($user_input['items_wrapper']['items']);
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
    if (isset($form['settings']['items_wrapper'])) {
      return $form['settings']['items_wrapper'];
    }
    return $form['items_wrapper'] ?? $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();
    $items = [];

    \Drupal::logger('accordion_debug')->notice('blockSubmit - Raw values: @values', [
      '@values' => print_r($values, TRUE)
    ]);

    if (isset($values['items_wrapper']['items']) && is_array($values['items_wrapper']['items'])) {
      // Sort by weight first
      uasort($values['items_wrapper']['items'], function ($a, $b) {
        return ($a['weight'] ?? 0) <=> ($b['weight'] ?? 0);
      });

      foreach ($values['items_wrapper']['items'] as $index => $item) {
        \Drupal::logger('accordion_debug')->notice('Processing item @index: @item', [
          '@index' => $index,
          '@item' => print_r($item, TRUE)
        ]);

        // Only save items that have both title AND content
        if (isset($item['item']) &&
            !empty(trim($item['item']['title'])) &&
            !empty(trim($item['item']['content']['value']))) {

          $items[] = [
            'title' => trim($item['item']['title']),
            'content' => $item['item']['content'],
          ];

          \Drupal::logger('accordion_debug')->notice('Added valid item: @title', [
            '@title' => trim($item['item']['title'])
          ]);
        } else {
          \Drupal::logger('accordion_debug')->notice('Skipped empty item at index @index', [
            '@index' => $index
          ]);
        }
      }
    }

    \Drupal::logger('accordion_debug')->notice('Final items to save: @count', [
      '@count' => count($items)
    ]);

    // Only set default if absolutely no valid items
    if (empty($items)) {
      $items = [
        [
          'title' => '',
          'content' => ['value' => '', 'format' => 'basic_html'],
        ],
      ];
      \Drupal::logger('accordion_debug')->notice('No valid items found, using default empty item');
    }

    $this->setConfigurationValue('accordion_items', $items);
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $config = $this->getConfiguration();
    $items = $config['accordion_items'] ?? [];

    // Prepare items for the SDC
    $accordion_items = [];
    foreach ($items as $item) {
      if (!empty($item['title']) && !empty($item['content'])) {
        $content = $item['content'];

        // Handle text format fields
        if (is_array($content) && isset($content['value'])) {
          $processed_content = [
            '#type' => 'processed_text',
            '#text' => $content['value'],
            '#format' => $content['format'] ?? 'basic_html',
          ];
          $content_html = (string) $this->renderer->renderInIsolation($processed_content);
        } else {
          $content_html = is_string($content) ? $content : '';
        }

        $accordion_items[] = [
          'title' => $item['title'],
          'content' => $content_html,
        ];
      }
    }

    // Return empty if no valid items
    if (empty($accordion_items)) {
      return [];
    }

    // Render using the SDC
    return [
      '#type' => 'component',
      '#component' => 'groundwork:accordion',
      '#props' => [
        'unique_id' => uniqid('acc_'),
        'items' => $accordion_items,
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
