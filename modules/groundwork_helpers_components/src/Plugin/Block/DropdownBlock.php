<?php

namespace Drupal\groundwork_helpers_components\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides a Dropdown block.
 *
 * @Block(
 *   id = "groundwork_dropdown_block",
 *   admin_label = @Translation("Dropdown Block"),
 *   category = @Translation("Groundwork Components")
 * )
 */
class DropdownBlock extends BlockBase {

  public function defaultConfiguration() {
    return ['items' => []];
  }

  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    if (!$form_state->has('items_count')) {
      $form_state->set('items_count', count($config['items'] ?? []) ?: 1);
    }
    $count = $form_state->get('items_count');

    // This key must match the wrapper ID exactly.
    $form['items_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'items_wrapper'],
    ];

    for ($i = 0; $i < $count; $i++) {
      $form['items_wrapper']['item_' . $i] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Item @num', ['@num' => $i + 1]),
      ];
      $form['items_wrapper']['item_' . $i]['label'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Label'),
        '#default_value' => $config['items'][$i]['label'] ?? '',
      ];
      $form['items_wrapper']['item_' . $i]['url'] = [
        '#type' => 'url',
        '#title' => $this->t('URL'),
        '#default_value' => $config['items'][$i]['url'] ?? '',
      ];
    }

    // The AJAX trigger must be at the top level, not nested.
    $form['add_item'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another item'),
      '#submit' => [[static::class, 'addItemSubmit']],
      '#ajax' => [
        'callback' => [static::class, 'ajaxRefresh'],
        'wrapper' => 'items_wrapper',
      ],
    ];

    return $form;
  }

  public static function addItemSubmit(array &$form, FormStateInterface $form_state) {
    $form_state->set('items_count', $form_state->get('items_count') + 1);
    $form_state->setRebuild(TRUE);
  }

  public static function ajaxRefresh(array &$form, FormStateInterface $form_state) {
    return $form['items_wrapper'];
  }

  public function blockSubmit($form, FormStateInterface $form_state) {
    $items = [];
    $count = $form_state->get('items_count') ?? 0;
    for ($i = 0; $i < $count; $i++) {
      $values = $form_state->getValue(['items_wrapper', 'item_' . $i]);
      if ($values) {
        $items[] = [
          'label' => $values['label'] ?? '',
          'url' => $values['url'] ?? '',
        ];
      }
    }
    $this->configuration['items'] = $items;
  }

  public function build() {
    $items = $this->configuration['items'] ?? [];
    return [
      '#theme' => 'item_list',
      '#items' => array_map(function ($item) {
        return [
          'data' => [
            '#markup' => '<a href="' . $item['url'] . '">' . $item['label'] . '</a>',
          ],
        ];
      }, $items),
    ];
  }
}
