<?php

namespace Drupal\hierarchical_taxonomy_menu\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManager;

/**
 * Provides a 'HierarchicalTaxonomyMenuBlock' block.
 *
 * @Block(
 *  id = "hierarchical_taxonomy_menu",
 *  admin_label = @Translation("Hierarchical taxonomy menu"),
 *  category = @Translation("Menus")
 * )
 */
class HierarchicalTaxonomyMenuBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * Drupal\Core\Entity\entity_manager definition.
   *
   * @var \Drupal\Core\Entity\entity_manager
   */
  protected $entity_manager;

  /**
   * Construct.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param string $plugin_definition
   *   The plugin implementation definition.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    entityManager $entity_manager
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entity_manager = $entity_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'vocabulary' => '',
      'image_height' => 16,
      'image_width' => 16,
      'collapsible' => 0,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['vocabulary'] = [
      '#title' => $this->t('Vocabulary'),
      '#type' => 'select',
      '#options' => $this->getOptions(),
      '#required' => TRUE,
      '#default_value' => $this->configuration['vocabulary'],
    ];
    $form['image_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Image height'),
      '#default_value' => $this->configuration['image_height'],
    ];
    $form['image_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Image width'),
      '#default_value' => $this->configuration['image_width'],
    ];
    $form['collapsible'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make menu collapsible'),
      '#default_value' => $this->configuration['collapsible'],
    ];
    return $form;
  }

  private function getOptions() {
    $options = [];
    $vocabularies = taxonomy_vocabulary_get_names();
    $entity_manager = $this->entity_manager;
    foreach ($vocabularies as $vocabulary) {
      $fields = $entity_manager->getFieldDefinitions('taxonomy_term', $vocabulary);
      $options[$vocabulary] = $vocabulary;
      $suboptions = [];
      $suboptions[$vocabulary . '|'] = $this->t('@vocabulary (with no image)', ['@vocabulary' => $vocabulary]);
      foreach ($fields as $field) {
        if ($field->getType() == 'image') {
          $field_name = $field->getName();
          $suboptions[$vocabulary . '|' . $field_name] = $this->t('@vocabulary (with image: @image_field)', ['@vocabulary' => $vocabulary, '@image_field' => $field_name]);
        }
      }
      $options[$vocabulary] = $suboptions;
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['vocabulary'] = $form_state->getValue('vocabulary');
    $this->configuration['image_height'] = $form_state->getValue('image_height');
    $this->configuration['image_width'] = $form_state->getValue('image_width');
    $this->configuration['collapsible'] = $form_state->getValue('collapsible');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $vocabulary_config = $this->configuration['vocabulary'];
    $vocabulary_config = explode('|', $vocabulary_config);
    $image_height = $this->configuration['image_height'];
    $image_width = $this->configuration['image_width'];
    $vocabulary = isset($vocabulary_config[0]) ? $vocabulary_config[0] : NULL;
    $vocabulary_tree = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree($vocabulary);
    $image_field = isset($vocabulary_config[1]) ? $vocabulary_config[1] : NULL;
    $route_tid = $this->getCurrentRoute();

    $vocabulary_tree_array = [];
    foreach ($vocabulary_tree as $item) {
      $vocabulary_tree_array[] = [
        'tid' => $item->tid,
        'name' => $this->getNameFromTid($item->tid),
        'url' => $this->getLinkFromTid($item->tid),
        'parents' => $item->parents,
        'image' => $this->getImageFromTid($item->tid, $image_field),
        'height' => $image_height != '' ? $image_height : 16,
        'width' => $image_width != '' ? $image_width : 16,
      ];
    }

    $tree = $this->generateTree($vocabulary_tree_array);

    return [
      '#theme' => 'hierarchical_taxonomy_menu',
      '#menu_tree' => $tree,
      '#route_tid' => $route_tid,
      '#cache' => ['max-age' => 0],
      '#attached' => [
        'library' =>  [
          'hierarchical_taxonomy_menu/hierarchical_taxonomy_menu',
        ],
        'drupalSettings' => [
          'collapsibleMenu' => $this->configuration['collapsible'],
        ]
      ],
    ];
  }

  private function generateTree($array, $parent = 0) {
    $tree = [];
    foreach($array as $item) {
      if (reset($item['parents']) == $parent) {
        $item['subitem'] = isset($item['subitem']) ? $item['subitem'] : $this->generateTree($array, $item['tid']);
        $tree[] = $item;
      }
    }
    return $tree;
  }

  private function getNameFromTid($tid) {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $term = taxonomy_term_load($tid);
    $translation_languages = $term->getTranslationLanguages();
    if (isset($translation_languages[$language])) {
      $term_translated = $term->getTranslation($language);
      return $term_translated->getName();
    }
    return $term->getName();
  }

  private function getLinkFromTid($tid) {
    $language = \Drupal::languageManager()->getCurrentLanguage()->getId();
    $term = taxonomy_term_load($tid);
    $translation_languages = $term->getTranslationLanguages();
    if (isset($translation_languages[$language])) {
      $term_translated = $term->getTranslation($language);
      return $term_translated->url();
    }
    return $term->url();
  }

  private function getCurrentRoute() {
    if (\Drupal::routeMatch()->getRouteName() == 'entity.taxonomy_term.canonical') {
      return \Drupal::routeMatch()->getRawParameter('taxonomy_term');
    }
    return NULL;
  }

  private function getImageFromTid($tid, $image_field) {
    if (!is_numeric($tid) || $image_field == '') {
      return '';
    }
    $term = taxonomy_term_load($tid);
    $image_field_name = $term->get($image_field)->getValue();
    if (!isset($image_field_name[0]['target_id'])) {
      return '';
    }
    $fid = $image_field_name[0]['target_id'];
    if ($fid) {
      $file = \Drupal\file\Entity\File::load($fid);
      $path = Url::fromUri(file_create_url($file->getFileUri()));
      return $path;
    }
    return '';
  }

}
