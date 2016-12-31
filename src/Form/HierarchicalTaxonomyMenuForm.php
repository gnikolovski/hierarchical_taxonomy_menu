<?php

namespace Drupal\hierarchical_taxonomy_menu\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\field\FieldConfigInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManager;

/**
 * @file
 * Contains \Drupal\hierarchical_taxonomy_menu\Form\HierarchicalTaxonomyMenuForm.
 */

/**
 * Defines a form that configures taxonomy terms menu settings.
 */
class HierarchicalTaxonomyMenuForm extends ConfigFormBase {

  /**
   * Drupal\Core\Entity\EntityManager definition.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  public function __construct(EntityManager $entity_manager) {
    $this->entityManager = $entity_manager;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity.manager')
    );
  }

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

    $form['vocabulary'] = [
      '#title' => $this->t('Vocabulary'),
      '#type' => 'select',
      '#options' => $vocabularies,
      '#default_value' => $config->get('vocabulary'),
      "#empty_option"=>t('- Select -'),
      '#ajax' => [
        'callback' => '::fieldCallback',
        'wrapper' => 'field-wrapper',
        'event' => 'change',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Loading fields...'),
        ],
      ],
    ];

    $vocabulary = $form_state->getValue('vocabulary');
    $vocabulary = isset($vocabulary) ? $vocabulary : $config->get('vocabulary');

    $form['image_field'] = [
      '#type' => 'select',
      '#title' => $this->t('Image field'),
      '#options' => $this->getOptions($vocabulary),
      '#prefix' => '<div id="field-wrapper">',
      '#suffix' => '</div>',
      "#empty_option"=>t('- Select -'),
      '#default_value' => $config->get('image_field') != '' ? $config->get('image_field') : 0,
    ];

    $form['image_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Image height'),
      '#size' => 5,
      '#maxlength' => 3,
      '#default_value' => $config->get('image_height'),
    ];

    $form['image_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Image width'),
      '#size' => 5,
      '#maxlength' => 3,
      '#default_value' => $config->get('image_width'),
    ];

    return parent::buildForm($form, $form_state);
  }

  public function fieldCallback(array &$form, FormStateInterface $form_state) {
	  return $form['image_field'];
  }

  public function getOptions($vocabulary) {
    $entityManager = $this->entityManager;
    $fields = $entityManager->getFieldDefinitions('taxonomy_term', $vocabulary);
    $field_names = [];
    foreach ($fields as $field) {
      if ($field instanceof FieldConfigInterface && $field->getType() == 'image') {
        $field_names[$field->getName()] = $field->getName();
      }
    }

    return $field_names;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $config = $this->config('hierarchical_taxonomy_menu.settings');
    $config->set('vocabulary', $values['vocabulary']);
    $config->set('image_field', $values['image_field']);
    $config->set('image_height', $values['image_height']);
    $config->set('image_width', $values['image_width']);
    $config->save();

    parent::submitForm($form, $form_state);
  }

}
