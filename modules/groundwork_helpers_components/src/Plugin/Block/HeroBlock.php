<?php

namespace Drupal\groundwork_helpers_components\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileUrlGeneratorInterface;

/**
 * Provides a configurable Hero block using the Hero SDC.
 *
 * @Block(
 *   id = "hero_block",
 *   admin_label = @Translation("Hero Banner"),
 *   category = @Translation("Groundwork Components")
 * )
 */
class HeroBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected FileUrlGeneratorInterface $fileUrlGenerator;

  public function __construct(array $configuration, $plugin_id, $plugin_definition, FileUrlGeneratorInterface $fileUrlGenerator) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->fileUrlGenerator = $fileUrlGenerator;
  }

  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): self {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('file_url_generator')
    );
  }

  public function defaultConfiguration(): array {
    return [
      'title' => '',
      'content' => [
        'value' => '',
        'format' => 'basic_html',
      ],
      'button_1_label' => '',
      'button_1_url' => '',
      'button_1_color' => 'success',
      'button_2_label' => '',
      'button_2_url' => '',
      'button_2_color' => 'info',
      'background_image' => NULL,
      'vh_height' => 'none',
    ];
  }

  public function build(): array {
    $config = $this->getConfiguration();
    $bg_url = NULL;

    if (!empty($config['background_image'][0])) {
      $file = File::load($config['background_image'][0]);
      if ($file) {
        $bg_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
      }
    }

    return [
      '#type' => 'component',
      '#component' => 'groundwork:hero',
      '#props' => [
        'title' => $config['title'],
        'content' => [
          '#type' => 'processed_text',
          '#text' => $config['content']['value'] ?? '',
          '#format' => $config['content']['format'] ?? 'basic_html',
        ],
        'button_1_label' => $config['button_1_label'],
        'button_1_url' => $config['button_1_url'],
        'button_1_color' => $config['button_1_color'],
        'button_2_label' => $config['button_2_label'],
        'button_2_url' => $config['button_2_url'],
        'button_2_color' => $config['button_2_color'],
        'background_image' => $bg_url,
        'vh_height' => $config['vh_height'],
      ],
    ];
  }

  public function blockForm($form, FormStateInterface $form_state): array {
    $config = $this->getConfiguration();

    $form['title'] = [
      '#type' => 'textfield',
      '#title' => t('Title'),
      '#default_value' => $config['title'],
      '#required' => TRUE,
    ];

    $form['content'] = [
      '#type' => 'text_format',
      '#title' => t('Content (formatted)'),
      '#format' => $config['content']['format'] ?? 'basic_html',
      '#default_value' => $config['content']['value'] ?? '',
    ];

    $form['button_1_label'] = [
      '#type' => 'textfield',
      '#title' => t('Primary Button Label'),
      '#default_value' => $config['button_1_label'],
    ];

    $form['button_1_url'] = [
      '#type' => 'textfield',
      '#title' => t('Primary Button URL'),
      '#default_value' => $config['button_1_url'],
    ];

    $form['button_1_color'] = [
      '#type' => 'select',
      '#title' => t('Primary Button Color'),
      '#options' => [
        'info' => 'Info',
        'notice' => 'Notice',
        'success' => 'Success',
        'danger' => 'Danger',
        'warning' => 'Warning',
        'muted' => 'Muted',
      ],
      '#default_value' => $config['button_1_color'],
    ];

    $form['button_2_label'] = [
      '#type' => 'textfield',
      '#title' => t('Secondary Button Label'),
      '#default_value' => $config['button_2_label'],
    ];

    $form['button_2_url'] = [
      '#type' => 'textfield',
      '#title' => t('Secondary Button URL'),
      '#default_value' => $config['button_2_url'],
    ];

    $form['button_2_color'] = [
      '#type' => 'select',
      '#title' => t('Secondary Button Color'),
      '#options' => [
        'info' => 'Info',
        'notice' => 'Notice',
        'success' => 'Success',
        'danger' => 'Danger',
        'warning' => 'Warning',
        'muted' => 'Muted',
      ],
      '#default_value' => $config['button_2_color'],
    ];

    $form['vh_height'] = [
      '#type' => 'select',
      '#title' => t('Height Variant'),
      '#description' => t('Choose how tall the hero section should be.'),
      '#options' => [
        'none' => 'Natural height',
        '50' => '50% viewport height',
        '75' => '75% viewport height',
        '100' => '100% viewport height',
      ],
      '#default_value' => $config['vh_height'],
    ];

    $form['background_image'] = [
      '#type' => 'managed_file',
      '#title' => t('Background image'),
      '#upload_location' => 'public://hero_backgrounds/',
      '#default_value' => $config['background_image'],
      '#description' => t('Optional background image for the hero banner.'),
      '#upload_validators' => [
        'FileIsImage' => [],
        'FileExtension' => [
          'extensions' => 'jpg jpeg png gif svg webp avif',
        ],
      ],
    ];

    return $form;
  }

  public function blockSubmit($form, FormStateInterface $form_state): void {
    foreach ([
      'title',
      'button_1_label', 'button_1_url', 'button_1_color',
      'button_2_label', 'button_2_url', 'button_2_color',
      'vh_height'
    ] as $field) {
      $this->setConfigurationValue($field, $form_state->getValue($field));
    }

    $this->setConfigurationValue('content', $form_state->getValue('content'));

    if ($fid = $form_state->getValue('background_image')) {
      $file = File::load($fid[0]);
      if ($file) {
        $file->setPermanent();
        $file->save();
      }
      $this->setConfigurationValue('background_image', $fid);
    }
  }

}
