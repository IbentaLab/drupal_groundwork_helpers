<?php

declare(strict_types=1);

namespace Drupal\groundwork_helpers_components\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Card with Ribbon block using the Card with Ribbon SDC.
 *
 * @Block(
 *   id = "groundwork_card_with_ribbon",
 *   admin_label = @Translation("Card with Ribbon"),
 *   category = @Translation("Groundwork Components"),
 *   permission = "use groundwork card with ribbon component"
 * )
 */
class CardWithRibbonBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * Constructs a new CardWithRibbonBlock instance.
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
      'card_title' => '',
      'card_content' => ['value' => '', 'format' => 'basic_html'],
      'ribbon_label' => '',
      'ribbon_variant' => 'ribbon--new',
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $config = $this->getConfiguration();

    $form['card_title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Card Heading'),
      '#default_value' => $config['card_title'] ?? '',
      '#maxlength' => 255,
      '#description' => $this->t('The heading displayed at the top of the card.'),
    ];

    $form['card_content'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Card Content'),
      '#default_value' => is_array($config['card_content']) ? ($config['card_content']['value'] ?? '') : ($config['card_content'] ?? ''),
      '#format' => is_array($config['card_content']) ? ($config['card_content']['format'] ?? 'basic_html') : 'basic_html',
      '#required' => TRUE,
      '#description' => $this->t('The main content area of the card.'),
    ];

    $form['ribbon_settings'] = [
      '#type' => 'details',
      '#title' => $this->t('Ribbon Settings'),
      '#open' => TRUE,
    ];

    $form['ribbon_settings']['ribbon_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Ribbon Label'),
      '#default_value' => $config['ribbon_label'] ?? '',
      '#maxlength' => 50,
      '#required' => TRUE,
      '#description' => $this->t('Text to display on the ribbon.'),
    ];

    $form['ribbon_settings']['ribbon_variant'] = [
      '#type' => 'select',
      '#title' => $this->t('Ribbon Style'),
      '#default_value' => $config['ribbon_variant'] ?? 'ribbon--new',
      '#options' => [
        'ribbon--new' => $this->t('New (Green)'),
        'ribbon--sale' => $this->t('Sale (Red)'),
        'ribbon--beta' => $this->t('Beta (Yellow)'),
        'ribbon--hot' => $this->t('Hot (Pink)'),
        'ribbon--exclusive' => $this->t('Exclusive (Gray)'),
        'ribbon--featured' => $this->t('Featured (Indigo)'),
      ],
      '#description' => $this->t('Choose the color and style of the ribbon.'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $values = $form_state->getValues();

    $this->setConfigurationValue('card_title', $values['card_title']);
    $this->setConfigurationValue('card_content', $values['card_content']);
    $this->setConfigurationValue('ribbon_label', $values['ribbon_settings']['ribbon_label']);
    $this->setConfigurationValue('ribbon_variant', $values['ribbon_settings']['ribbon_variant']);
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $config = $this->getConfiguration();

    // Process the content - return the render array directly for the slot
    $content = $config['card_content'] ?? ['value' => '', 'format' => 'basic_html'];
    if (is_array($content) && isset($content['value'])) {
      $content_render_array = [
        '#type' => 'processed_text',
        '#text' => $content['value'],
        '#format' => $content['format'] ?? 'basic_html',
      ];
    } else {
      $content_render_array = [
        '#markup' => is_string($content) ? $content : '',
      ];
    }

    // Return early if no content
    if (empty($content['value']) && empty($config['card_title'])) {
      return [];
    }

    // Prepare props
    $props = [];

    // Add title if provided
    if (!empty($config['card_title'])) {
      $props['title'] = $config['card_title'];
    }

    // Always add ribbon since this is a "card with ribbon" component
    $props['ribbon'] = [
      'label' => $config['ribbon_label'] ?? '',
      'variant' => $config['ribbon_variant'] ?? 'ribbon--new',
    ];

    // Render using the SDC
    return [
      '#type' => 'component',
      '#component' => 'groundwork:card-with-ribbon',
      '#props' => $props,
      '#slots' => [
        'children' => $content_render_array,
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
