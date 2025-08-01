<?php

declare(strict_types=1);

namespace Drupal\groundwork_helpers_components\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a Personalized Welcome block.
 *
 * @Block(
 * id = "groundwork_personalized_welcome",
 * admin_label = @Translation("Personalized Welcome"),
 * category = @Translation("Groundwork Components"),
 * permission = "use groundwork welcome component",
 * context_definitions = {
 * "user" = @ContextDefinition("entity:user", label = @Translation("Current user"))
 * }
 * )
 */
class WelcomeBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected RendererInterface $renderer;

  /**
   * The current user account.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $account;

  /**
   * Constructs a new WelcomeBlock instance.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    RendererInterface $renderer,
    AccountInterface $account
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->renderer = $renderer;
    $this->account = $account;
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
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration(): array {
    return [
      'message_to_user' => [
        'value' => '',
        'format' => 'basic_html',
      ],
    ] + parent::defaultConfiguration();
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state): array {
    $config = $this->getConfiguration();

    $form['message_to_user'] = [
      '#type' => 'text_format',
      '#title' => $this->t('Message to User'),
      '#description' => $this->t('This content will be displayed below the dynamic welcome message (e.g., "Welcome back, Jane!").'),
      '#default_value' => $config['message_to_user']['value'] ?? '',
      '#format' => $config['message_to_user']['format'] ?? 'basic_html',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state): void {
    $this->setConfigurationValue('message_to_user', $form_state->getValue('message_to_user'));
  }

  /**
   * {@inheritdoc}
   */
  public function build(): array {
    $config = $this->getConfiguration();

    // Get the current user from context.
    $user = $this->getContextValue('user');
    $last_login_message = '';

    if ($user->isAuthenticated()) {
      $welcome_message = $this->t('Welcome back, @name!', ['@name' => $user->getDisplayName()]);
      $last_login = $user->getLastLoginTime();
      if ($last_login) {
        $last_login_message = $this->t('You last logged in on @date.', [
          '@date' => \Drupal::service('date.formatter')->format($last_login, 'long'),
        ]);
      }
    }
    else {
      $welcome_message = $this->t('Welcome, guest!');
    }

    // Render the generic content from the WYSIWYG editor.
    $generic_content_render_array = [
      '#type' => 'processed_text',
      '#text' => $config['message_to_user']['value'] ?? '',
      '#format' => $config['message_to_user']['format'] ?? 'basic_html',
    ];
    $generic_content_html = (string) $this->renderer->renderInIsolation($generic_content_render_array);

    return [
      '#type' => 'component',
      '#component' => 'groundwork:welcome',
      '#props' => [
        'welcome_message' => $welcome_message,
        'last_login' => $last_login_message,
        'generic_content' => $generic_content_html,
      ],
      '#cache' => [
        'contexts' => ['user'],
      ],
    ];
  }

}
