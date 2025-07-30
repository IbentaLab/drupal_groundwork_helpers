<?php

declare(strict_types=1);

namespace Drupal\groundwork_helpers_components\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;
use Drupal\file\Entity\File;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Call to Action block using the CTA SDC.
 *
 * @Block(
 *   id = "groundwork_cta",
 *   admin_label = @Translation("Call to Action"),
 *   category = @Translation("Groundwork Components"),
 *   context_definitions = {
 *     "layout_builder.entity" = @ContextDefinition("entity", required = FALSE),
 *   }
 * )
 */
class CTABlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * The file URL generator service.
   *
   * @var \Drupal\Core\File\FileUrlGeneratorInterface
   */
  protected FileUrlGeneratorInterface $fileUrlGenerator;

  /**
   * Constructs a new CTABlock instance.
   *
   * @param array $configuration
   *   The plugin configuration.
   * @param string $plugin_id
   *   The plugin ID.
   * @param mixed $plugin_definition
   *   The plugin definition.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   The file URL generator service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RendererInterface $renderer,
    FileUrlGeneratorInterface $fileUrlGenerator
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->renderer = $renderer;
    $this->fileUrlGenerator = $fileUrlGenerator;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): static {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('renderer'),
      $container->get('file_url_generator')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'cta_title' => '',
      'body_type' => 'simple',
      'body_text' => '',
      'body_html' => ['value' => '', 'format' => 'basic_html'],
      'button_label' => '',
      'button_url' => '',
      'background_type' => 'none',
      'background_image_url' => '',
      'background_image_upload' => '',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $config = $this->getConfiguration();

    $form['cta_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('CTA Heading'),
      '#default_value' => $config['cta_title'] ?? '',
      '#required' => TRUE,
      '#maxlength' => 255,
      '#description' => $this->t('The main heading for the call to action.'),
    ];

    $form['body_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Body Content Type'),
      '#options' => [
        'simple' => $this->t('Simple text'),
        'rich' => $this->t('Rich HTML'),
      ],
      '#default_value' => $config['body_type'] ?? 'simple',
      '#description' => $this->t('Choose whether to use simple text or rich HTML for the body content.'),
    ];

    $form['body_text'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Body Text'),
      '#default_value' => $config['body_text'] ?? '',
      '#maxlength' => 500,
      '#description' => $this->t('Optional descriptive text under the title.'),
      '#states' => [
        'visible' => [
          ':input[name="settings[body_type]"]' => ['value' => 'simple'],
        ],
      ],
    ];

    $form['body_html'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Body HTML'),
      '#default_value' => is_array($config['body_html']) ? ($config['body_html']['value'] ?? '') : ($config['body_html'] ?? ''),
      '#format' => is_array($config['body_html']) ? ($config['body_html']['format'] ?? 'basic_html') : 'basic_html',
      '#description' => $this->t('Rich HTML content for the body area.'),
      '#states' => [
        'visible' => [
          ':input[name="settings[body_type]"]' => ['value' => 'rich'],
        ],
      ],
    ];

    $form['button_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Button Settings'),
      '#open' => TRUE,
    ];

    $form['button_settings']['button_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button Label'),
      '#default_value' => $config['button_label'] ?? '',
      '#required' => TRUE,
      '#maxlength' => 100,
      '#description' => $this->t('The text displayed on the action button.'),
    ];

    $form['button_settings']['button_url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button URL'),
      '#default_value' => $config['button_url'] ?? '',
      '#required' => TRUE,
      '#description' => $this->t('The URL the button should link to. Can be internal (/contact) or external (https://example.com).'),
    ];

    $form['styling'] = [
      '#type' => 'details',
      '#title' => $this->t('Background Options'),
      '#open' => FALSE,
    ];

    $form['styling']['background_type'] = [
      '#type' => 'radios',
      '#title' => $this->t('Background Type'),
      '#options' => [
        'none' => $this->t('No background image'),
        'url' => $this->t('Image URL'),
        'upload' => $this->t('Upload image'),
      ],
      '#default_value' => $config['background_type'] ?? 'none',
      '#description' => $this->t('Choose how to set the background image.'),
    ];

    $form['styling']['background_image_url'] = [
      '#type' => 'url',
      '#title' => $this->t('Background Image URL'),
      '#default_value' => $config['background_image_url'] ?? '',
      '#description' => $this->t('Enter the URL of the background image.'),
      '#states' => [
        'visible' => [
          ':input[name="settings[styling][background_type]"]' => ['value' => 'url'],
        ],
        'required' => [
          ':input[name="settings[styling][background_type]"]' => ['value' => 'url'],
        ],
      ],
    ];

    $form['styling']['background_image_upload'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Background Image'),
      '#default_value' => $config['background_image_upload'],
      '#upload_location' => 'public://cta-backgrounds/',
      '#description' => $this->t('Upload a background image for the CTA.'),
      '#upload_validators' => [
        'FileIsImage' => [],
        'FileExtension' => [
          'extensions' => 'jpg jpeg png gif svg webp avif',
        ],
      ],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    // Use the same pattern as HeroBlock for all fields
    foreach ([
      'cta_title', 'body_type', 'body_text', 'body_html'
    ] as $field) {
      $this->setConfigurationValue($field, $form_state->getValue($field));
    }

    // Handle nested fields
    $this->setConfigurationValue('button_label', $form_state->getValue(['button_settings', 'button_label']));
    $this->setConfigurationValue('button_url', $form_state->getValue(['button_settings', 'button_url']));
    $this->setConfigurationValue('background_type', $form_state->getValue(['styling', 'background_type']));
    $this->setConfigurationValue('background_image_url', $form_state->getValue(['styling', 'background_image_url']));

    // Handle file upload EXACTLY like HeroBlock
    if ($fid = $form_state->getValue(['styling', 'background_image_upload'])) {
      $file = File::load($fid[0]);
      if ($file) {
        $file->setPermanent();
        $file->save();
      }
      $this->setConfigurationValue('background_image_upload', $fid);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $config = $this->getConfiguration();

    // Validate required fields
    if (empty($config['cta_title']) || empty($config['button_label']) || empty($config['button_url'])) {
      return [];
    }

    // Prepare props
    $props = [
      'title' => $config['cta_title'],
      'button_label' => $config['button_label'],
      'button_url' => $config['button_url'],
    ];

    // Handle background image exactly like HeroBlock - NO $file->get() calls
    $background_type = $config['background_type'] ?? 'none';
    if ($background_type === 'url' && !empty($config['background_image_url'])) {
      $props['background_image'] = $config['background_image_url'];
    } elseif ($background_type === 'upload' && !empty($config['background_image_upload'][0])) {
      $file = File::load($config['background_image_upload'][0]);
      if ($file) {
        $bg_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
        $props['background_image'] = $bg_url;
      }
    }

    // Prepare slots array
    $slots = [];

    // Handle body content based on type
    $body_type = $config['body_type'] ?? 'simple';

    if ($body_type === 'simple' && !empty($config['body_text'])) {
      // Use simple text as a prop
      $props['body'] = $config['body_text'];
    } elseif ($body_type === 'rich') {
      // Use rich HTML as a slot
      $body_html = $config['body_html'] ?? ['value' => '', 'format' => 'basic_html'];
      if (is_array($body_html) && !empty($body_html['value'])) {
        $slots['body_html'] = [
          '#type' => 'processed_text',
          '#text' => $body_html['value'],
          '#format' => $body_html['format'] ?? 'basic_html',
        ];
      }
    }

    // Build the component render array
    $build = [
      '#type' => 'component',
      '#component' => 'groundwork:cta',
      '#props' => $props,
      '#cache' => [
        'contexts' => ['url.path', 'url.query_args'],
      ],
    ];

    // Add slots if we have any
    if (!empty($slots)) {
      $build['#slots'] = $slots;
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheContexts(): array {
    return array_merge(parent::getCacheContexts(), ['url.path', 'url.query_args']);
  }

}
