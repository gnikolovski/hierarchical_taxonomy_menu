<?php

namespace Drupal\hierarchical_taxonomy_menu\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Url;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityManager;
use Drupal\file\Entity\File;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\CurrentRouteMatch;
use Drupal\image\Entity\ImageStyle;

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
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The current route match service.
   *
   * @var \Drupal\Core\Routing\CurrentRouteMatch
   */
  protected $currentRouteMatch;

  /**
   * Constructor.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityManager $entity_manager,
    LanguageManagerInterface $language_manager,
    CurrentRouteMatch $current_route_match
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityManager = $entity_manager;
    $this->languageManager = $language_manager;
    $this->currentRouteMatch = $current_route_match;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity.manager'),
      $container->get('language_manager'),
      $container->get('current_route_match')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
      'vocabulary' => '',
      'image_settings' => FALSE,
      'image_height' => 16,
      'image_width' => 16,
      'image_style' => '',
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
      '#options' => $this->getVocabularyOptions(),
      '#required' => TRUE,
      '#default_value' => $this->configuration['vocabulary'],
    ];
    $form['image_settings'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use image style'),
      '#default_value' => $this->configuration['image_settings'],
    ];
    $form['image_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Image height'),
      '#default_value' => $this->configuration['image_height'],
      '#states' => array(
        'visible' => array(
          array(
            ':input[name="settings[image_settings]"]' => array('checked' => FALSE),
          ),
        ),
      ),
    ];
    $form['image_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Image width'),
      '#default_value' => $this->configuration['image_width'],
      '#states' => array(
        'visible' => array(
          array(
            ':input[name="settings[image_settings]"]' => array('checked' => FALSE),
          ),
        ),
      ),
    ];
    $form['image_style'] = [
      '#title' => $this->t('Image style'),
      '#type' => 'select',
      '#options' => $this->getImageStyleOptions(),
      '#default_value' => $this->configuration['image_style'],
      '#states' => array(
        'visible' => array(
          array(
            ':input[name="settings[image_settings]"]' => array('checked' => TRUE),
          ),
        ),
      ),
    ];
    $form['collapsible'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make menu collapsible'),
      '#default_value' => $this->configuration['collapsible'],
    ];
    return $form;
  }

  /**
   * Generate vocabulary select options.
   */
  private function getVocabularyOptions() {
    $options = [];
    $vocabularies = taxonomy_vocabulary_get_names();
    $entityManager = $this->entityManager;
    foreach ($vocabularies as $vocabulary) {
      $fields = $entityManager->getFieldDefinitions('taxonomy_term', $vocabulary);
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
    $this->configuration['image_settings'] = $form_state->getValue('image_settings');
    $this->configuration['image_height'] = $form_state->getValue('image_height');
    $this->configuration['image_width'] = $form_state->getValue('image_width');
    $this->configuration['image_style'] = $form_state->getValue('image_style');
    $this->configuration['collapsible'] = $form_state->getValue('collapsible');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $vocabulary_config = $this->configuration['vocabulary'];
    $vocabulary_config = explode('|', $vocabulary_config);
    $vocabulary = isset($vocabulary_config[0]) ? $vocabulary_config[0] : NULL;
    $entityManager = $this->entityManager;
    $vocabulary_tree = $entityManager->getStorage('taxonomy_term')->loadTree($vocabulary);
    $image_field = isset($vocabulary_config[1]) ? $vocabulary_config[1] : NULL;
    $image_height = $this->configuration['image_height'];
    $image_width = $this->configuration['image_width'];
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
        'library' => [
          'hierarchical_taxonomy_menu/hierarchical_taxonomy_menu',
        ],
        'drupalSettings' => [
          'collapsibleMenu' => $this->configuration['collapsible'],
        ],
      ],
    ];
  }

  /**
   * Generate menu tree.
   */
  private function generateTree($array, $parent = 0) {
    $tree = [];
    foreach ($array as $item) {
      if (reset($item['parents']) == $parent) {
        $item['subitem'] = isset($item['subitem']) ? $item['subitem'] : $this->generateTree($array, $item['tid']);
        $tree[] = $item;
      }
    }
    return $tree;
  }

  /**
   * Get term name.
   */
  private function getNameFromTid($tid) {
    $language = $this->languageManager->getCurrentLanguage()->getId();
    $term = taxonomy_term_load($tid);
    $translation_languages = $term->getTranslationLanguages();
    if (isset($translation_languages[$language])) {
      $term_translated = $term->getTranslation($language);
      return $term_translated->getName();
    }
    return $term->getName();
  }

  /**
   * Get term url.
   */
  private function getLinkFromTid($tid) {
    $language = $this->languageManager->getCurrentLanguage()->getId();
    $term = taxonomy_term_load($tid);
    $translation_languages = $term->getTranslationLanguages();
    if (isset($translation_languages[$language])) {
      $term_translated = $term->getTranslation($language);
      return $term_translated->url();
    }
    return $term->url();
  }

  /**
   * Get current route.
   */
  private function getCurrentRoute() {
    if ($this->currentRouteMatch->getRouteName() == 'entity.taxonomy_term.canonical') {
      return $this->currentRouteMatch->getRawParameter('taxonomy_term');
    }
    return NULL;
  }

  /**
   * Get image from term.
   */
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
      $file = File::load($fid);
      $path = Url::fromUri(file_create_url($file->getFileUri()));
      return $path;
    }
    return '';
  }

  /**
   * Generate image style select options.
   */
  private function getImageStyleOptions() {
    $options = [];
    $styles = ImageStyle::loadMultiple();
    foreach ($styles as $style) {
      $options[] = $style->getName();
    }
    return $options;
  }

}
