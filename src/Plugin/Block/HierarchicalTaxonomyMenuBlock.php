<?php

namespace Drupal\hierarchical_taxonomy_menu\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Url;

/**
 * Provides a 'HierarchicalTaxonomyMenuBlock' block.
 *
 * @Block(
 *  id = "hierarchical_taxonomy_menu",
 *  admin_label = @Translation("Hierarchical taxonomy menu"),
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
    $image_field = $config->get('image_field');
    $image_height = $config->get('image_height');
    $image_width = $config->get('image_width');
    $vocabulary_tree = \Drupal::entityManager()->getStorage('taxonomy_term')->loadTree($vocabulary);
    $route_tid = $this->getCurrentRoute();

    $vocabulary_tree_array = array();
    foreach ($vocabulary_tree as $item) {
      $vocabulary_tree_array[] = array(
        'tid' => $item->tid,
        'name' => $this->getNameFromTid($item->tid),
        'url' => $this->getLinkFromTid($item->tid),
        'parents' => $item->parents,
        'image' => $this->getImageFromTid($item->tid, $image_field),
        'height' => $image_height != '' ? $image_height : 16,
        'width' => $image_width != '' ? $image_width : 16,
      );
    }

    $tree = $this->generateTree($vocabulary_tree_array);

    dsm($config->get('collapsible'));

    return array(
      '#theme' => 'hierarchical_taxonomy_menu',
      '#menu_tree' => $tree,
      '#route_tid' => $route_tid,
      '#classes' => $config->get('classes'),
      '#cache' => array('max-age' => 0),
      '#attached' => array(
        'library' =>  array(
          'hierarchical_taxonomy_menu/menu',
        ),
        'drupalSettings' => array(
          'collapsibleMenu' => $config->get('collapsible'),
        )
      ),
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
