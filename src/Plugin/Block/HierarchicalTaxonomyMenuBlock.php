<?php

namespace Drupal\hierarchical_taxonomy_menu\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\ResettableStackedRouteMatchInterface;
use Drupal\Core\Url;
use Drupal\Core\Cache\Cache;
use Drupal\file\Entity\File;
use Drupal\image\Entity\ImageStyle;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a 'HierarchicalTaxonomyMenuBlock' block.
 *
 * @Block(
 *  id = "hierarchical_taxonomy_menu",
 *  admin_label = @Translation("Hierarchical Taxonomy Menu"),
 *  category = @Translation("Menus")
 * )
 */
class HierarchicalTaxonomyMenuBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

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
   * Constructs a HierarchicalTaxonomyMenuBlock object.
   *
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager service.
   * @param \Drupal\Core\Routing\ResettableStackedRouteMatchInterface $current_route_match
   *   The current route match service.
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    EntityFieldManagerInterface $entity_field_manager,
    EntityTypeManagerInterface $entity_type_manager,
    LanguageManagerInterface $language_manager,
    ResettableStackedRouteMatchInterface $current_route_match
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
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
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager'),
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
      'max_depth' => 10,
      'max_age' => 'PERMANENT',
      'base_term' => '',
      'use_image_style' => FALSE,
      'image_height' => 16,
      'image_width' => 16,
      'image_style' => '',
      'collapsible' => 0,
      'interactive_parent' => 0,
      'dynamic_base_term' => 0,
      'dynamic_block_title' => FALSE,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form['dynamic_block_title'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Dynamic block title'),
      '#default_value' => $this->configuration['dynamic_block_title'],
      '#description' => $this->t('Make the block title match the current taxonomy term.'),
    ];

    $form['vocabulary'] = [
      '#title' => $this->t('Vocabulary'),
      '#type' => 'select',
      '#options' => $this->getVocabularyOptions(),
      '#required' => TRUE,
      '#default_value' => $this->configuration['vocabulary'],
    ];
    $form['max_age'] = [
      '#title' => $this->t('Cache Max Age'),
      '#type' => 'select',
      '#options' => [
        '0' => 'No Caching',
        '1800' => '30 Minutes',
        '3600' => '1 Hour',
        '21600' => '6 Hours',
        '43200' => '12 Hours',
        '86400' => '1 Day',
        '604800' => '1 Week',
        'PERMANENT' => 'PERMANENT',
      ],
      '#default_value' => $this->configuration['max_age'],
      '#description' => $this->t('Set the max age the menu is allowed to be cached for.'),
    ];
    $form['max_depth'] = [
      '#title' => $this->t('Number of sublevels to display'),
      '#type' => 'select',
      '#options' => [
        '0' => '0',
        '1' => '1',
        '2' => '2',
        '3' => '3',
        '4' => '4',
        '5' => '5',
        '6' => '6',
        '7' => '7',
        '8' => '8',
        '9' => '9',
        '10' => $this->t('Unlimited'),
      ],
      '#required' => TRUE,
      '#default_value' => $this->configuration['max_depth'],
    ];

    $form['dynamic_base_term'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Dynamic Base term'),
      '#default_value' => $this->configuration['dynamic_base_term'],
      '#description' => $this->t('Automatically set the base term from taxonomy page. The base term is then set to the current term and menu items will only be generated for its children.'),
    ];

    $form['base_term'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Base term'),
      '#size' => 20,
      '#default_value' => $this->configuration['base_term'],
      '#description' => $this->t('Enter a base term and menu items will only be generated for its children. You can enter term ID or term name. Leave empty to generate menu for the entire vocabulary.'),
      '#states' => [
        'visible' => [
          ':input[name="settings[dynamic_base_term]"]' => array('checked' => FALSE),
        ]
      ],
    ];

    $form['use_image_style'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Use image style'),
      '#default_value' => $this->configuration['use_image_style'],
    ];
    $form['image_height'] = [
      '#type' => 'number',
      '#title' => $this->t('Image height'),
      '#default_value' => $this->configuration['image_height'],
      '#states' => [
        'visible' => [
          [
            ':input[name="settings[use_image_style]"]' => ['checked' => FALSE],
          ],
        ],
      ],
    ];
    $form['image_width'] = [
      '#type' => 'number',
      '#title' => $this->t('Image width'),
      '#default_value' => $this->configuration['image_width'],
      '#states' => [
        'visible' => [
          [
            ':input[name="settings[use_image_style]"]' => ['checked' => FALSE],
          ],
        ],
      ],
    ];
    $form['image_style'] = [
      '#title' => $this->t('Image style'),
      '#type' => 'select',
      '#options' => $this->getImageStyleOptions(),
      '#default_value' => $this->configuration['image_style'],
      '#states' => [
        'visible' => [
          [
            ':input[name="settings[use_image_style]"]' => ['checked' => TRUE],
          ],
        ],
      ],
    ];
    $form['collapsible'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make menu collapsible'),
      '#default_value' => $this->configuration['collapsible'],
    ];

    $form['interactive_parent'] = [
      '#type' => 'checkbox',
      '#title' => $this->t('Make parent menu items interactive while collapsible'),
      '#default_value' => $this->configuration['interactive_parent'],
      '#states' => [
        'visible' => [
          [
            ':input[name="settings[collapsible]"]' => ['checked' => TRUE],
          ],
        ],
      ],
    ];
    return $form;
  }

  /**
   * Generate vocabulary select options.
   */
  private function getVocabularyOptions() {
    $options = [];
    $vocabularies = taxonomy_vocabulary_get_names();
    foreach ($vocabularies as $vocabulary) {
      $fields = $this->entityFieldManager->getFieldDefinitions('taxonomy_term', $vocabulary);
      $options[$vocabulary] = $vocabulary;
      $suboptions = [];
      $suboptions[$vocabulary . '|'] = $this->t('@vocabulary (with no image)', ['@vocabulary' => $vocabulary]);
      foreach ($fields as $field) {
        if ($field->getType() == 'image') {
          $field_name = $field->getName();
          $suboptions[$vocabulary . '|' . $field_name] = $this->t('@vocabulary (with image: @image_field)', [
            '@vocabulary' => $vocabulary,
            '@image_field' => $field_name,
          ]);
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
    $this->configuration['dynamic_block_title'] = $form_state->getValue('dynamic_block_title');
    $this->configuration['vocabulary'] = $form_state->getValue('vocabulary');
    $this->configuration['max_depth'] = $form_state->getValue('max_depth');
    $this->configuration['dynamic_base_term'] = $form_state->getValue('dynamic_base_term');
    $this->configuration['base_term'] = $form_state->getValue('base_term');
    $this->configuration['max_age'] = $form_state->getValue('max_age');
    $this->configuration['use_image_style'] = $form_state->getValue('use_image_style');
    $this->configuration['image_height'] = $form_state->getValue('image_height');
    $this->configuration['image_width'] = $form_state->getValue('image_width');
    $this->configuration['image_style'] = $form_state->getValue('image_style');
    $this->configuration['collapsible'] = $form_state->getValue('collapsible');
    $this->configuration['interactive_parent'] = $form_state->getValue('interactive_parent');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $vocabulary_config = $this->configuration['vocabulary'];
    $vocabulary_config = explode('|', $vocabulary_config);
    $vocabulary = isset($vocabulary_config[0]) ? $vocabulary_config[0] : NULL;
    $max_depth = $this->configuration['max_depth'];
    $base_term = $this->getVocabularyBaseTerm($this->configuration['base_term'], $this->configuration['dynamic_base_term']);
    $vocabulary_tree = $this->entityTypeManager->getStorage('taxonomy_term')
      ->loadTree($vocabulary, $base_term);
    $image_field = isset($vocabulary_config[1]) ? $vocabulary_config[1] : NULL;
    $use_image_style = $this->configuration['use_image_style'];
    $image_height = $this->configuration['image_height'];
    $image_width = $this->configuration['image_width'];
    $image_style = $use_image_style == TRUE ? $this->configuration['image_style'] : NULL;
    $route_tid = $this->getCurrentRoute();
    $max_age = $this->getMaxAge($this->configuration['max_age']);

    $vocabulary_tree_array = [];
    foreach ($vocabulary_tree as $item) {
      $vocabulary_tree_array[] = [
        'tid' => $item->tid,
        'name' => $this->getNameFromTid($item->tid),
        'url' => $this->getLinkFromTid($item->tid),
        'parents' => $item->parents,
        'use_image_style' => $use_image_style,
        'image' => $this->getImageFromTid($item->tid, $image_field, $image_style),
        'height' => $image_height != '' ? $image_height : 16,
        'width' => $image_width != '' ? $image_width : 16,
        'interactive_parent' => $this->configuration['interactive_parent'],
      ];
    }

    $tree = $this->generateTree($vocabulary_tree_array, $base_term);

    return [
      '#theme' => 'hierarchical_taxonomy_menu',
      '#menu_tree' => $tree,
      '#route_tid' => $route_tid,
      '#cache' => ['max-age' => $max_age],
      '#current_depth' => 0,
      '#vocabularies' => $vocabulary,
      '#max_depth' => $max_depth,
      '#attached' => [
        'library' => [
          'hierarchical_taxonomy_menu/hierarchical_taxonomy_menu',
        ],
        'drupalSettings' => [
          'collapsibleMenu' => $this->configuration['collapsible'],
          'interactiveParentMenu' => $this->configuration['interactive_parent'],
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
    $term = Term::load($tid);
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
    $term = Term::load($tid);
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
  private function getImageFromTid($tid, $image_field, $image_style) {
    if (!is_numeric($tid) || $image_field == '') {
      return '';
    }
    $term = Term::load($tid);
    $image_field_name = $term->get($image_field)->getValue();
    if (!isset($image_field_name[0]['target_id'])) {
      return '';
    }
    $fid = $image_field_name[0]['target_id'];
    if ($fid) {
      $file = File::load($fid);
      if ($image_style) {
        $style = ImageStyle::load($image_style);
        if ($style) {
          $path = $style->buildUrl($file->getFileUri());
        }
        else {
          $path = Url::fromUri(file_create_url($file->getFileUri()));
        }
      }
      else {
        $path = Url::fromUri(file_create_url($file->getFileUri()));
      }
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
      $style_name = $style->getName();
      $options[$style_name] = $style_name;
    }
    return $options;
  }

  /**
   * Return base taxonomy term ID.
   */
  private function getVocabularyBaseTerm($base_term, $dynamic_base_term) {
    if ($dynamic_base_term) {
      if ($term_id = $this->currentRouteMatch->getRawParameter('taxonomy_term')) {
        return $term_id;
      }
      else {
        return 0;
      }
    }
    else {
      if (!$base_term) {
        return 0;
      }
      if (is_numeric($base_term)) {
        return $base_term;
      }
      else {
        $term = $this->entityTypeManager->getStorage('taxonomy_term')
          ->loadByProperties(['name' => $base_term]);
        return $term ? reset($term)->id() : 0;
      }
    }

  }

  /**
   * Return Cache Max Age
   */
  public function getMaxAge($max_age) {
    if (!$max_age){
      $max_age = 0;
      return $max_age;
    }

    if ($max_age == 'PERMANENT') {
      $max_age = Cache::PERMANENT;
      return $max_age;
    }
    else {
      return $max_age;
    }
  }

}
