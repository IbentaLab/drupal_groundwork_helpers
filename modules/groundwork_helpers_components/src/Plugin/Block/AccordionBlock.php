<?php

namespace Drupal\groundwork_helpers_components\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Provides an Accordion block.
 *
 * @Block(
 *   id = "groundwork_accordion_block",
 *   admin_label = @Translation("Accordion Block"),
 *   category = @Translation("Groundwork Components")
 * )
 */
class AccordionBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'items' => [],
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $config = $this->getConfiguration();

    $form['items'] = [
      '#type' => 'details',
      '#title' => $this->t('Accordion Items'),
      '#open' => TRUE,
      '#tree' => TRUE,
      '#prefix' => '<div id="accordion-items-wrapper">',
      '#suffix' => '</div>',
    ];

    $items = $config['items'] ?? [];
    $count = $form_state->get('items_count') ?? count($items) ?: 1;
    $form_state->set('items_count', $count);

    for ($i = 0; $i < $count; $i++) {
      $form['items'][$i] = [
        '#type' => 'fieldset',
        '#title' => $this->t('Item @num', ['@num' => $i + 1]),
      ];
      $form['items'][$i]['title'] = [
        '#type' => 'textfield',
        '#title' => $this->t('Title'),
        '#default_value' => $items[$i]['title'] ?? '',
      ];
      $form['items'][$i]['content'] = [
        '#type' => 'text_format',
        '#title' => $this->t('Content'),
        '#format' => 'basic_html',
        '#default_value' => $items[$i]['content']['value'] ?? '',
      ];
    }

    $form['add_item'] = [
      '#type' => 'submit',
      '#value' => $this->t('Add another item'),
      '#submit' => [[get_class($this), 'addItemSubmit']],
      '#ajax' => [
        'callback' => [get_class($this), 'ajaxRefresh'],
        'wrapper' => 'accordion-items-wrapper',
      ],
    ];

    return $form;
  }

  public static function addItemSubmit(array &$form, FormStateInterface $form_state) {
    $count = $form_state->get('items_count') ?? 1;
    $form_state->set('items_count', $count + 1);
    $form_state->setRebuild(TRUE);
  }

  public static function ajaxRefresh(array &$form, FormStateInterface $form_state) {
    return $form['items'];
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['items'] = $form_state->getValue('items');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $items = $this->configuration['items'] ?? [];
    return [
      '#theme' => 'item_list',
      '#items' => array_map(function ($item) {
        return [
          'data' => [
            '#markup' => '<strong>' . $item['title'] . '</strong><div>' . $item['content']['value'] . '</div>',
          ],
        ];
      }, $items),
    ];
  }
}

