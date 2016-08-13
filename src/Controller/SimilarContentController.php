<?php

namespace Drupal\google_vision\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Database\Connection;

/**
 * Returns the related content based on dominant color of the images.
 */
class SimilarContentController extends ControllerBase {

  /**
   * Database Service Object.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Constructs a SimilarContentController object.
   */
  public function __construct(Connection $connection) {
    $this->connection = $connection;
  }

  /**
   * Returns the list of image links which share the same dominant color.
   *
   * @param \Drupal\file\FileInterface $file .
   *  The file of which the similar contents are to be displayed.
   *
   * @return array $build.
   *  The rendered list of items to be displayed.
   */
  public function fetchContent(FileInterface $file) {
    // Get the file id.
    $file_id = $file->id();

    //Get an array of just term ids.
    $query = \Drupal::entityQuery('taxonomy_term');
    $query->condition('vid', 'dominant_color');
    $tids = $query->execute();
    $terms = Term::loadMultiple($tids);
    $term_id = array_keys($terms);

    // Get the list of dominant colors per file.
    $dominant_color = [];
    foreach ($term_id as $key => $value) {
      $query = $this->connection->select('file__field_labels', 'ffl');
      $query->fields('ffl', ['field_labels_target_id']);
      $query->condition('ffl.entity_id', $file_id);
      $query->condition('ffl.field_labels_target_id', $value);
      $dominant_color[] = $query->execute()->fetchField();
    }

    $build = array();

    if (!empty($dominant_color)) {
      //Get all the file ids which have one or more dominant colors in common with $dominant_color.
      $query = $this->connection->select('file__field_labels', 'ffl');
      $query->fields('ffl', ['entity_id', 'field_labels_target_id']);
      $query->condition('field_labels_target_id', $dominant_color, 'IN');
      $file_ids = array_keys($query->execute()->fetchAllKeyed());

      // Get the file names linked with the file ids.
      $query = $this->connection->select('file_managed', 'fm');
      $query->fields('fm', ['fid', 'filename']);
      $query->condition('fm.fid', $file_ids, 'IN');
      $files = $query->execute()->fetchAllKeyed();

      $build['list'] = [
        '#theme' => 'item_list',
        '#items' => [],
      ];

      foreach ($files as $key => $value) {
        $build['list']['#items'][$key] = [
          '#type' => 'link',
          '#title' => $value,
          '#url' => Url::fromRoute('entity.file.canonical', ['file' => $key]),
        ];
      }
    }
    else {
      $build = [
        '#markup' => t('No items found.'),
      ];
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static($container->get('database'));
  }
}
