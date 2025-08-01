<?php

declare(strict_types=1);

namespace Drupal\groundwork_helpers_components\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Process Steps block.
 *
 * @Block(
 * id = "groundwork_process_steps",
 * admin_label = @Translation("Process Steps"),
 * category = @Translation("Groundwork Components"),
 * permission = "use groundwork process steps component",
 * )
 */
class ProcessStepsBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * Constructs a new ProcessStepsBlock instance.
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
      'intro' => ['value' => '', 'format' => 'basic_html'],
      'heading' => '',
      'number_color' => 'primary',
      'steps' => [['title' => '', 'description' => ['value' => '', 'format' => 'basic_html']]],
      'outro' => ['value' => '', 'format' => 'basic_html'],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $config = $this->getConfiguration();

    $form['intro'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Introduction (Optional)'),
      '#default_value' => $config['intro']['value'] ?? '',
      '#format' => $config['intro']['format'] ?? 'basic_html',
    ];

    $form['heading'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Process Heading (Optional)'),
      '#default_value' => $config['heading'] ?? '',
    ];

    $form['number_color'] = [
      '#type' => 'select',
      '#title' => $this->t('Step Number Color'),
      '#description' => $this->t('Steps are numbered automatically. Choose a background color for the numbers.'),
      '#options' => [
        'primary' => $this->t('Primary'),
        'secondary' => $this->t('Secondary'),
        'success' => $this->t('Success'),
        'danger' => $this->t('Danger'),
        'warning' => $this->t('Warning'),
        'info' => $this->t('Info'),
        'muted' => $this->t('Muted'),
      ],
      '#default_value' => $config['number_color'] ?? 'primary',
    ];

    // --- Steps ---
    $saved_items = $config['steps'] ?? [];
    $saved_items = array_filter($saved_items, fn($item) => !empty($item['title']));
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

    $form['steps_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'steps-wrapper'],
      '#tree' => TRUE,
    ];

    if ($is_ajax_rebuild) {
      $user_input = $form_state->getUserInput();
      $current_items = $user_input['settings']['steps_wrapper'] ?? $saved_items;
    } else {
      $current_items = $saved_items;
    }

    for ($i = 0; $i < $item_count; $i++) {
      if (!isset($current_items[$i])) {
        $current_items[$i] = ['title' => '', 'description' => ['value' => '', 'format' => 'basic_html']];
      }

      $form['steps_wrapper'][$i] = [
        '#type' => 'details',
        '#title' => $this->t('Step @num', ['@num' => $i + 1]),
        '#open' => TRUE,
      ];

      $form['steps_wrapper'][$i]['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Step'),
        '#default_value' => $current_items[$i]['title'] ?? '',
        '#required' => TRUE,
      ];

      $form['steps_wrapper'][$i]['description'] = [
        '#type' => 'text_format',
        '#title' => $this->t('Description'),
        '#default_value' => $current_items[$i]['description']['value'] ?? '',
        '#format' => $current_items[$i]['description']['format'] ?? 'basic_html',
        '#required' => TRUE,
      ];

      $form['steps_wrapper'][$i]['actions'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['container-inline', 'item-actions']],
      ];

      if ($item_count > 1) {
        $form['steps_wrapper'][$i]['actions']['move_up'] = [
          '#type' => 'submit',
          '#value' => $this->t('Move Up'),
          '#name' => 'move_up_' . $i,
          '#submit' => [[static::class, 'moveItemCallback']],
          '#ajax' => ['callback' => [static::class, 'ajaxCallback'], 'wrapper' => 'steps-wrapper'],
          '#limit_validation_errors' => [],
          '#disabled' => $i === 0,
        ];
        $form['steps_wrapper'][$i]['actions']['move_down'] = [
          '#type' => 'submit',
          '#value' => $this->t('Move Down'),
          '#name' => 'move_down_' . $i,
          '#submit' => [[static::class, 'moveItemCallback']],
          '#ajax' => ['callback' => [static::class, 'ajaxCallback'], 'wrapper' => 'steps-wrapper'],
          '#limit_validation_errors' => [],
          '#disabled' => $i === ($item_count - 1),
        ];
        $form['steps_wrapper'][$i]['actions']['remove'] = [
          '#type' => 'submit',
          '#value' => $this->t('Remove'),
          '#name' => 'remove_' . $i,
          '#submit' => [[static::class, 'removeItemCallback']],
          '#ajax' => ['callback' => [static::class, 'ajaxCallback'], 'wrapper' => 'steps-wrapper'],
          '#limit_validation_errors' => [],
          '#attributes' => ['class' => ['button--danger']],
        ];
      }
    }

    $form['add_step_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['container-inline']],
    ];
    $form['add_step_wrapper']['add_item'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add New Step'),
      '#submit' => [[static::class, 'addItemCallback']],
      '#ajax' => ['callback' => [static::class, 'ajaxCallback'], 'wrapper' => 'steps-wrapper'],
      '#limit_validation_errors' => [],
    ];

    $form['outro'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Outro (Optional)'),
      '#default_value' => $config['outro']['value'] ?? '',
      '#format' => $config['outro']['format'] ?? 'basic_html',
    ];

    return $form;
  }

  /**
   * Submit callback for moving items.
   */
  public static function moveItemCallback(array &$form, FormStateInterface $form_state) {
    $trigger = $form_state->getTriggeringElement();
    $name_parts = explode('_', $trigger['#name']);
    $direction = $name_parts[1];
    $index = (int) $name_parts[2];
    $user_input = $form_state->getUserInput();
    $items = &$user_input['settings']['steps_wrapper'];
    if (!is_array($items)) {
      return;
    }
    $swap_index = ($direction === 'up') ? $index - 1 : $index + 1;
    if (isset($items[$swap_index])) {
      $temp = $items[$index];
      $items[$index] = $items[$swap_index];
      $items[$swap_index] = $temp;
    }
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
      if (isset($user_input['settings']['steps_wrapper'][$item_index])) {
        unset($user_input['settings']['steps_wrapper'][$item_index]);
        $user_input['settings']['steps_wrapper'] = array_values($user_input['settings']['steps_wrapper']);
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
    return $form['settings']['steps_wrapper'];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->setConfigurationValue('intro', $form_state->getValue('intro'));
    $this->setConfigurationValue('heading', $form_state->getValue('heading'));
    $this->setConfigurationValue('number_color', $form_state->getValue('number_color'));
    $this->setConfigurationValue('outro', $form_state->getValue('outro'));

    $values = $form_state->getValues();
    $steps = [];
    if (isset($values['steps_wrapper']) && is_array($values['steps_wrapper'])) {
      foreach ($values['steps_wrapper'] as $item_values) {
        if (!empty(trim($item_values['title'])) && !empty(trim($item_values['description']['value']))) {
          $steps[] = [
            'title' => trim($item_values['title']),
            'description' => $item_values['description']
          ];
        }
      }
    }
    $this->setConfigurationValue('steps', $steps);
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $config = $this->getConfiguration();

    // Process intro content
    $intro_html = '';
    if (!empty($config['intro']['value'])) {
      $intro_render_array = [
        '#type' => 'processed_text',
        '#text' => $config['intro']['value'],
        '#format' => $config['intro']['format']
      ];
      $intro_html = (string) $this->renderer->renderInIsolation($intro_render_array);
    }

    // Process outro content
    $outro_html = '';
    if (!empty($config['outro']['value'])) {
      $outro_render_array = [
        '#type' => 'processed_text',
        '#text' => $config['outro']['value'],
        '#format' => $config['outro']['format']
      ];
      $outro_html = (string) $this->renderer->renderInIsolation($outro_render_array);
    }

    // Process steps
    $steps_items = [];
    if (isset($config['steps']) && is_array($config['steps'])) {
      foreach ($config['steps'] as $item) {
        if (!empty($item['title']) && !empty($item['description']['value'])) {
          $description_render_array = [
            '#type' => 'processed_text',
            '#text' => $item['description']['value'],
            '#format' => $item['description']['format']
          ];
          $steps_items[] = [
            'title' => $item['title'],
            'description' => (string) $this->renderer->renderInIsolation($description_render_array)
          ];
        }
      }
    }

    if (empty($steps_items)) {
      return [];
    }

    // Generate a stable unique ID that doesn't change when block is edited
    // Strategy: Use content signature for stability + instance counter for uniqueness
    $content_signature = '';
    if (isset($config['steps']) && is_array($config['steps'])) {
      foreach ($config['steps'] as $step) {
        $content_signature .= $step['title'] ?? '';
      }
    }
    $content_signature .= $config['heading'] ?? '';

    // Create a stable hash from content
    $content_hash = substr(md5($content_signature), 0, 8);

    // Add a random component to ensure uniqueness between identical blocks
    static $instance_counter = 0;
    $instance_counter++;

    // Combine content hash with instance counter for uniqueness but stability
    $unique_id = 'process-block-' . $content_hash . '-' . $instance_counter;

    // Debug: Log the generated ID
    \Drupal::logger('process_steps')->info('Generated unique_id: @id from content: @content', [
      '@id' => $unique_id,
      '@content' => substr($content_signature, 0, 50) . '...',
    ]);

    return [
      '#type' => 'component',
      '#component' => 'groundwork:process-steps',
      '#props' => [
        'unique_id' => $unique_id,
        'intro' => $intro_html,
        'heading' => $config['heading'],
        'number_color' => $config['number_color'] ?? 'primary',
        'steps' => $steps_items,
        'outro' => $outro_html,
      ],
    ];
  }

}
