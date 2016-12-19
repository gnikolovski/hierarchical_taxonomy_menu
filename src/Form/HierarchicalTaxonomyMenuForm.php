<?php

namespace Drupal\hierarchical_taxonomy_menu\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * @file
 * Contains \Drupal\hierarchical_taxonomy_menu\Form\HierarchicalTaxonomyMenuForm.
 */

/**
 * Defines a form that configures taxonomy terms menu settings.
 */
class HierarchicalTaxonomyMenuForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'hierarchical_taxonomy_menu_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return array('hierarchical_taxonomy_menu.settings');
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('hierarchical_taxonomy_menu.settings');
    $vocabularies = taxonomy_vocabulary_get_names();

    $form['vocabulary'] = array(
      '#title' => $this->t('Vocabulary'),
      '#type' => 'select',
      '#options' => $vocabularies,
      '#default_value' => $config->get('vocabulary'),
    );

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('hierarchical_taxonomy_menu.settings');
    $config->set('vocabulary', $values['vocabulary']);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
