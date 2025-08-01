<?php

namespace Drupal\groundwork_helpers_components\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\file\Entity\File;
use Drupal\Core\File\FileUrlGeneratorInterface;

/**
 * Provides a configurable Banner block using the Banner SDC.
 *
 * @Block(
 *   id = "banner_block",
 *   admin_label = @Translation("Banner"),
 *   category = @Translation("Groundwork Components")
 * )
 */
class BannerBlock extends BlockBase implements ContainerFactoryPluginInterface {

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
      // Content
      'title' => '',
      'subtitle' => '',
      'content' => [
        'value' => '',
        'format' => 'basic_html',
      ],

      // Background
      'background_color' => '',
      'background_image' => [],
      'background_image_path' => '',
      'background_is_dark' => FALSE,
      'background_attachment' => 'scroll',
      'background_position' => 'center',

      // Overlay
      'overlay_type' => '',
      'overlay_opacity' => '50',

      // Layout
      'layout' => 'center',
      'height_variant' => 'natural',

      // Actions
      'primary_action' => [
        'label' => '',
        'url' => '',
        'color' => 'primary',
        'style' => '',
        'size' => '',
      ],
      'secondary_action' => [
        'label' => '',
        'url' => '',
        'color' => 'secondary',
        'style' => 'ghost',
        'size' => '',
      ],
    ];
  }

  public function build(): array {
    $config = $this->getConfiguration();
    $background_image_url = NULL;

    // Handle background image - prioritize uploaded file over path
    if (!empty($config['background_image'][0])) {
      $file = File::load($config['background_image'][0]);
      if ($file) {
        $background_image_url = $this->fileUrlGenerator->generateAbsoluteString($file->getFileUri());
      }
    }
    elseif (!empty($config['background_image_path'])) {
      // Handle direct path to image
      $background_image_url = $this->fileUrlGenerator->generateAbsoluteString($config['background_image_path']);
    }

    // Process content
    $processed_content = '';
    if (!empty($config['content']['value'])) {
      $processed_content = [
        '#type' => 'processed_text',
        '#text' => $config['content']['value'],
        '#format' => $config['content']['format'] ?? 'basic_html',
      ];
    }

    return [
      '#type' => 'component',
      '#component' => 'groundwork:banner',
      '#props' => [
        // Content
        'title' => $config['title'],
        'subtitle' => $config['subtitle'],
        'content' => $processed_content,

        // Background
        'background_color' => $config['background_color'],
        'background_image' => $background_image_url,
        'background_is_dark' => (bool) $config['background_is_dark'],
        'background_attachment' => $config['background_attachment'],
        'background_position' => $config['background_position'],

        // Overlay
        'overlay_type' => $config['overlay_type'],
        'overlay_opacity' => $config['overlay_opacity'],

        // Layout
        'layout' => $config['layout'],
        'height_variant' => $config['height_variant'],

        // Actions
        'primary_action' => $config['primary_action'],
        'secondary_action' => $config['secondary_action'],
      ],
    ];
  }

  public function blockForm($form, FormStateInterface $form_state): array {
    $config = $this->getConfiguration();

    // Content section
    $form['content_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Content'),
      '#open' => TRUE,
    ];

    $form['content_section']['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Title'),
      '#default_value' => $config['title'],
      '#required' => TRUE,
      '#description' => $this->t('Main headline for the banner.'),
    ];

    $form['content_section']['subtitle'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Subtitle'),
      '#default_value' => $config['subtitle'],
      '#description' => $this->t('Optional subtitle or tagline.'),
    ];

    $form['content_section']['content'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Content'),
      '#format' => $config['content']['format'] ?? 'basic_html',
      '#default_value' => $config['content']['value'] ?? '',
      '#description' => $this->t('Optional body content with rich text formatting.'),
    ];

    // Background section
    $form['background_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Background'),
      '#description' => $this->t('Choose either a color background OR an image background. Color backgrounds automatically provide proper text contrast.'),
      '#open' => TRUE,
    ];

    $form['background_section']['background_color'] = [
      '#type' => 'select',
      '#title' => $this->t('Background Color'),
      '#options' => [
        '' => $this->t('- None -'),
        'blue' => $this->t('Blue'),
        'indigo' => $this->t('Indigo'),
        'deep-purple' => $this->t('Deep Purple'),
        'purple' => $this->t('Purple'),
        'pink' => $this->t('Pink'),
        'red' => $this->t('Red'),
        'orange' => $this->t('Orange'),
        'amber' => $this->t('Amber'),
        'yellow' => $this->t('Yellow'),
        'lime' => $this->t('Lime'),
        'green' => $this->t('Green'),
        'teal' => $this->t('Teal'),
        'cyan' => $this->t('Cyan'),
        'brown' => $this->t('Brown'),
        'blue-gray' => $this->t('Blue Gray'),
        'gray' => $this->t('Gray'),
      ],
      '#default_value' => $config['background_color'],
      '#description' => $this->t('Pre-defined colors with automatic text contrast for accessibility. Leave empty to use a background image instead.'),
    ];

    $form['background_section']['use_background_image'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use custom background image'),
      '#default_value' => !empty($config['background_image']) || !empty($config['background_image_path']),
      '#description' => $this->t('Check this to use a custom background image instead of a color background. Uncheck to use only the color background above.'),
      '#states' => [
        'visible' => [
          ':input[name="settings[background_section][background_color]"]' => ['value' => ''],
        ],
      ],
    ];

    $form['background_section']['image_options_container']['background_attachment'] = [
      '#type' => 'select',
      '#title' => $this->t('Background Scroll'),
      '#options' => [
        'scroll' => $this->t('Normal scrolling'),
        'fixed' => $this->t('Fixed (parallax effect)'),
      ],
      '#default_value' => $config['background_attachment'],
      '#description' => $this->t('Normal scrolling moves the image with the page. Fixed creates a parallax effect where the image stays in place while content scrolls over it.'),
    ];

    $form['background_section']['image_options_container']['background_position'] = [
      '#type' => 'select',
      '#title' => $this->t('Image Position'),
      '#options' => [
        'top' => $this->t('Top - Show upper part of image'),
        'center' => $this->t('Center - Show middle of image (default)'),
        'bottom' => $this->t('Bottom - Show lower part of image'),
      ],
      '#default_value' => $config['background_position'],
      '#description' => $this->t('Controls which part of your image is visible when the banner height is smaller than the image. Useful for mobile optimization and ensuring important image elements stay visible.'),
    ];

    // Container for all image-related options
    $form['background_section']['image_options_container'] = [
      '#type' => 'container',
      '#states' => [
        'visible' => [
          ':input[name="settings[background_section][use_background_image]"]' => ['checked' => TRUE],
        ],
      ],
    ];

    // All image-related fields go inside the container
    $form['background_section']['image_options_container']['background_image'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Upload Background Image'),
      '#upload_location' => 'public://banner_backgrounds/',
      '#default_value' => $config['background_image'],
      '#description' => $this->t('Upload a background image file. Recommended formats: JPG, PNG, WebP, AVIF. For best results, use high-resolution images (1920px width or larger).'),
      '#upload_validators' => [
        'FileIsImage' => [],
        'FileExtension' => [
          'extensions' => 'jpg jpeg png gif svg webp avif',
        ],
      ],
    ];

    $form['background_section']['image_options_container']['background_image_path'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Or specify image path'),
      '#default_value' => $config['background_image_path'] ?? '',
      '#description' => $this->t('Alternative: enter the path to an image already on your server. Examples: <code>public://images/hero.jpg</code> or <code>themes/custom/groundwork/images/banner.jpg</code>. This field is ignored if you upload an image above.'),
    ];

    $form['background_section']['image_options_container']['background_is_dark'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('My background image is predominantly dark'),
      '#default_value' => $config['background_is_dark'],
      '#description' => $this->t('Check this if your image has mostly dark colors (like night scenes, dark photography, or graphics with dark backgrounds). This ensures light-colored text is used for optimal readability. Leave unchecked for bright, light-colored images.'),
    ];

    $form['background_section']['image_options_container']['overlay_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Overlay Effect'),
      '#options' => [
        '' => $this->t('- No overlay -'),
        'dark' => $this->t('Dark overlay'),
        'light' => $this->t('Light overlay'),
        'primary' => $this->t('Primary color overlay'),
      ],
      '#default_value' => $config['overlay_type'],
      '#description' => $this->t('Add a colored layer over your image for better text contrast or branding:<br><strong>Dark:</strong> Improves readability for light text on bright images<br><strong>Light:</strong> Improves readability for dark text on dark images<br><strong>Primary:</strong> Adds your brand color for subtle branding (works best at 25-50% strength)'),
    ];

    $form['background_section']['image_options_container']['overlay_opacity'] = [
      '#type' => 'select',
      '#title' => $this->t('Overlay Strength'),
      '#options' => [
        '10' => $this->t('10% - Very subtle'),
        '25' => $this->t('25% - Subtle (ideal for branding)'),
        '50' => $this->t('50% - Balanced'),
        '75' => $this->t('75% - Strong'),
        '90' => $this->t('90% - Very strong'),
      ],
      '#default_value' => $config['overlay_opacity'],
      '#description' => $this->t('Controls the intensity of the overlay effect. Lower percentages preserve more of your original image, while higher percentages prioritize text readability over image visibility.'),
      '#states' => [
        'visible' => [
          ':input[name="settings[background_section][image_options_container][overlay_type]"]' => ['!value' => ''],
        ],
      ],
    ];

    // Layout section
    $form['layout_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Layout'),
    ];

    $form['layout_section']['layout'] = [
      '#type' => 'select',
      '#title' => $this->t('Content Alignment'),
      '#options' => [
        'center' => $this->t('Center'),
        'left' => $this->t('Left'),
        'right' => $this->t('Right'),
      ],
      '#default_value' => $config['layout'],
      '#description' => $this->t('How to align the banner content.'),
    ];

    $form['layout_section']['height_variant'] = [
      '#type' => 'select',
      '#title' => $this->t('Height'),
      '#options' => [
        'natural' => $this->t('Natural height (content-based)'),
        '50' => $this->t('50% of viewport height'),
        '75' => $this->t('75% of viewport height'),
        '100' => $this->t('100% of viewport height (full screen)'),
      ],
      '#default_value' => $config['height_variant'],
      '#description' => $this->t('Controls the minimum height of the banner.'),
    ];

    // Actions section
    $form['actions_section'] = [
      '#type' => 'details',
      '#title' => $this->t('Action Buttons'),
      '#description' => $this->t('Add call-to-action buttons to your banner.'),
    ];

    // Primary action
    $form['actions_section']['primary_action'] = [
      '#type' => 'details',
      '#title' => $this->t('Primary Action'),
      '#description' => $this->t('Main call-to-action button.'),
    ];

    $form['actions_section']['primary_action']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button Text'),
      '#default_value' => $config['primary_action']['label'],
    ];

    $form['actions_section']['primary_action']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button URL'),
      '#default_value' => $config['primary_action']['url'],
      '#description' => $this->t('Internal path (e.g., /contact) or external URL (e.g., https://example.com).'),
    ];

    $this->addButtonStyleFields($form['actions_section']['primary_action'], $config['primary_action'], 'primary_action');

    // Secondary action
    $form['actions_section']['secondary_action'] = [
      '#type' => 'details',
      '#title' => $this->t('Secondary Action'),
      '#description' => $this->t('Optional secondary call-to-action button.'),
    ];

    $form['actions_section']['secondary_action']['label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button Text'),
      '#default_value' => $config['secondary_action']['label'],
    ];

    $form['actions_section']['secondary_action']['url'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Button URL'),
      '#default_value' => $config['secondary_action']['url'],
      '#description' => $this->t('Internal path (e.g., /about) or external URL (e.g., https://example.com).'),
    ];

    $this->addButtonStyleFields($form['actions_section']['secondary_action'], $config['secondary_action'], 'secondary_action');

    return $form;
  }

  /**
   * Helper method to add button style fields.
   */
  private function addButtonStyleFields(array &$form_section, array $config, string $action_key): void {
    $form_section['color'] = [
      '#type' => 'select',
      '#title' => $this->t('Button Color'),
      '#options' => [
        'primary' => $this->t('Primary'),
        'secondary' => $this->t('Secondary'),
        'info' => $this->t('Info'),
        'notice' => $this->t('Notice'),
        'success' => $this->t('Success'),
        'danger' => $this->t('Danger'),
        'warning' => $this->t('Warning'),
        'muted' => $this->t('Muted'),
      ],
      '#default_value' => $config['color'],
    ];

    $form_section['style'] = [
      '#type' => 'select',
      '#title' => $this->t('Button Style'),
      '#options' => [
        '' => $this->t('Filled (default)'),
        'ghost' => $this->t('Ghost (outline)'),
        'light' => $this->t('Light variant'),
        'gradient' => $this->t('Gradient effect'),
        'glow' => $this->t('Glow effect'),
      ],
      '#default_value' => $config['style'],
    ];

    $form_section['size'] = [
      '#type' => 'select',
      '#title' => $this->t('Button Size'),
      '#options' => [
        '' => $this->t('Default'),
        'sm' => $this->t('Small'),
        'md' => $this->t('Medium'),
        'lg' => $this->t('Large'),
        'xl' => $this->t('Extra Large'),
      ],
      '#default_value' => $config['size'],
    ];
  }

  public function blockSubmit($form, FormStateInterface $form_state): void {
    // Handle file upload
    $background_image = $form_state->getValue(['background_section', 'image_options_container', 'background_image']);
    if (!empty($background_image[0])) {
      $file = File::load($background_image[0]);
      if ($file) {
        $file->setPermanent();
        $file->save();
      }
    }

    // Save all configuration
    $this->setConfigurationValue('title', $form_state->getValue(['content_section', 'title']));
    $this->setConfigurationValue('subtitle', $form_state->getValue(['content_section', 'subtitle']));
    $this->setConfigurationValue('content', $form_state->getValue(['content_section', 'content']));

    $this->setConfigurationValue('background_color', $form_state->getValue(['background_section', 'background_color']));
    $this->setConfigurationValue('background_image', $background_image);
    $this->setConfigurationValue('background_image_path', $form_state->getValue(['background_section', 'image_options_container', 'background_image_path']));
    $this->setConfigurationValue('background_is_dark', $form_state->getValue(['background_section', 'image_options_container', 'background_is_dark']));
    $this->setConfigurationValue('background_attachment', $form_state->getValue(['background_section', 'image_options_container', 'background_attachment']));
    $this->setConfigurationValue('background_position', $form_state->getValue(['background_section', 'image_options_container', 'background_position']));

    $this->setConfigurationValue('overlay_type', $form_state->getValue(['background_section', 'image_options_container', 'overlay_type']));
    $this->setConfigurationValue('overlay_opacity', $form_state->getValue(['background_section', 'image_options_container', 'overlay_opacity']));

    $this->setConfigurationValue('layout', $form_state->getValue(['layout_section', 'layout']));
    $this->setConfigurationValue('height_variant', $form_state->getValue(['layout_section', 'height_variant']));

    // Handle primary action
    $primary_action = [
      'label' => $form_state->getValue(['actions_section', 'primary_action', 'label']),
      'url' => $form_state->getValue(['actions_section', 'primary_action', 'url']),
      'color' => $form_state->getValue(['actions_section', 'primary_action', 'color']),
      'style' => $form_state->getValue(['actions_section', 'primary_action', 'style']),
      'size' => $form_state->getValue(['actions_section', 'primary_action', 'size']),
    ];
    $this->setConfigurationValue('primary_action', $primary_action);

    // Handle secondary action
    $secondary_action = [
      'label' => $form_state->getValue(['actions_section', 'secondary_action', 'label']),
      'url' => $form_state->getValue(['actions_section', 'secondary_action', 'url']),
      'color' => $form_state->getValue(['actions_section', 'secondary_action', 'color']),
      'style' => $form_state->getValue(['actions_section', 'secondary_action', 'style']),
      'size' => $form_state->getValue(['actions_section', 'secondary_action', 'size']),
    ];
    $this->setConfigurationValue('secondary_action', $secondary_action);
  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state): void {
    // Validate URLs if provided
    $primary_url = $form_state->getValue(['actions_section', 'primary_action', 'url']);
    $secondary_url = $form_state->getValue(['actions_section', 'secondary_action', 'url']);

    if (!empty($primary_url) && !$this->isValidUrl($primary_url)) {
      $form_state->setError($form['actions_section']['primary_action']['url'], $this->t('Primary action URL is not valid.'));
    }

    if (!empty($secondary_url) && !$this->isValidUrl($secondary_url)) {
      $form_state->setError($form['actions_section']['secondary_action']['url'], $this->t('Secondary action URL is not valid.'));
    }

    // Validate that if button label is provided, URL is also provided
    $primary_label = $form_state->getValue(['actions_section', 'primary_action', 'label']);
    if (!empty($primary_label) && empty($primary_url)) {
      $form_state->setError($form['actions_section']['primary_action']['url'], $this->t('Primary action URL is required when button text is provided.'));
    }

    $secondary_label = $form_state->getValue(['actions_section', 'secondary_action', 'label']);
    if (!empty($secondary_label) && empty($secondary_url)) {
      $form_state->setError($form['actions_section']['secondary_action']['url'], $this->t('Secondary action URL is required when button text is provided.'));
    }
  }

  /**
   * Validates if a URL is properly formatted.
   *
   * @param string $url
   *   The URL to validate.
   *
   * @return bool
   *   TRUE if the URL is valid, FALSE otherwise.
   */
  private function isValidUrl(string $url): bool {
    // Check for internal paths (starting with /)
    if (str_starts_with($url, '/')) {
      return TRUE;
    }

    // Check for external URLs
    return filter_var($url, FILTER_VALIDATE_URL) !== FALSE;
  }

}
