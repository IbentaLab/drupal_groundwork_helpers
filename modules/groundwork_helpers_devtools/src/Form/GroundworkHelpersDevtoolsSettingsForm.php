<?php

namespace Drupal\groundwork_helpers_devtools\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Configure settings for Groundwork Helpers DevTools.
 */
class GroundworkHelpersDevtoolsSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'groundwork_helpers_devtools_settings_form';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['groundwork_helpers_devtools.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('groundwork_helpers_devtools.settings');

    $form['visibility'] = [
      '#type' => 'details',
      '#title' => $this->t('DevTools bar visibility'),
      '#open' => TRUE,
    ];
    $form['visibility']['show_on_paths'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Show on paths'),
      '#description' => $this->t('Enter one path per line. Use * as a wildcard. Examples: <br>/node/1<br>/admin/*<br>Leave blank to show everywhere.'),
      '#default_value' => $config->get('show_on_paths') ?? '',
    ];
    $form['visibility']['hide_on_content_types'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Hide on content types'),
      '#description' => $this->t('Enter machine names of content types to hide the bar on, one per line.'),
      '#default_value' => $config->get('hide_on_content_types') ?? '',
    ];

    $form['default_state'] = [
      '#type' => 'radios',
      '#title' => $this->t('Default bar state'),
      '#options' => [
        'expanded' => $this->t('Expanded (visible)'),
        'minimized' => $this->t('Minimized (compact button)'),
      ],
      '#default_value' => $config->get('default_state') ?? 'expanded',
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('groundwork_helpers_devtools.settings')
      ->set('show_on_paths', $form_state->getValue('show_on_paths'))
      ->set('hide_on_content_types', $form_state->getValue('hide_on_content_types'))
      ->set('default_state', $form_state->getValue('default_state'))
      ->save();

    parent::submitForm($form, $form_state);
  }
}
