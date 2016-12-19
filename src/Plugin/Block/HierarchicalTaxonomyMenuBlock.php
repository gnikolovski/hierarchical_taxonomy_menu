<?php

namespace Drupal\hierarchical_taxonomy_menu\Plugin\Block;

use Drupal\Core\Block\BlockBase;

/**
 * Provides a 'HierarchicalTaxonomyMenuBlock' block.
 *
 * @Block(
 *  id = "hierarchical_taxonomy_menu",
 *  admin_label = @Translation("Hierarchical Taxonomy Menu"),
 *  category = @Translation("Menus")
 * )
 */
class HierarchicalTaxonomyMenuBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = \Drupal::config('hierarchical_taxonomy_menu.settings');
    $vocabulary = $config->get('vocabulary');
    $vocabulary_tree = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree($vocabulary);
    $route_tid = $this->getCurrentRoute();

    $vocabulary_tree_array = array();
    foreach ($vocabulary_tree as $item) {
      $vocabulary_tree_array[] = array(
        'tid' => $item->tid,
        'name' => $this->getNameFromTid($item->tid),
        'url' => $this->getLinkFromTid($item->tid),
        'parents' => $item->parents,
      );
    }

    $tree = $this->generateTree($vocabulary_tree_array);

    return array(
      '#theme' => 'hierarchical_taxonomy_menu',
      '#menu_tree' => $tree,
      '#route_tid' => $route_tid,
      '#classes' => $config->get('classes'),
      '#cache' => array('max-age' => 0),
    );
  }

  private function generateTree($array, $parent = 0) {
    $tree = array();
    foreach($array as $item) {
      if(reset($item['parents']) == $parent) {
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

}
