<?php

namespace  Drupal\drush_delete;

class DeleteContent {

  protected $say_something;

  public function __construct() {
    $this->say_something = 'Hello World!';
  }

  public function  sayHello($name = ''){
    if (empty($name)) {
      return $this->say_something;
    }
    else {
      return "Hello " . $name . "!";
    }
  }

  /**
   * Function that provides the deleteing content functionality
   */
  public function deleteAllEntityType($type){
    $nids = $this->getEntityIds($type);
    if(!empty($nids)) {
      $no_delete = 0;
      foreach ($nids as $nid) {
        $delete = \Drupal::entityTypeManager()->getStorage('node')->load($nid)->delete();
        $no_delete++;

      }

      return $no_delete . " no of contents are deleted";
    }
    else {
      return "Content not found !";
    }
  }

  public function getEntityIds($type) {
    $nids = array();
    $connection = \Drupal::database();
    $result = $connection->select('node', 'n')
      ->fields('n', array('nid'))
      ->condition('type', $type, '=')
      ->execute()->fetchall();
    if(empty($result)) {
      return NULL;
    }
    else {
      foreach ($result as $item) {
        $nids[] = $item->nid;
      }
    }

    return $nids;
  }

  /**
   * To delete the entity content.
   *
   * @param string $type
   *   Entity type.
   *
   * @return string
   *   return.
   */
  public function deleteAllEntity($type) {
    $query = \Drupal::entityQuery($type);
    $nids = $query->execute();
    $no_delete = 0;
    if (!empty($nids)) {
      foreach ($nids as $nid) {
        $delete = \Drupal::entityTypeManager()->getStorage($type)->load($nid)->delete();
        $no_delete++;
      }
      return $no_delete . " no of contents are deleted";
    }
    else {
      return "Content not found !";
    }
  }

  /**
   * To delete the taxonomy vocabulary terms.
   *
   * @param string $type
   *   Entity type.
   *
   * @return string
   *   return.
   */
  public function deleteAllTerms($type) {
    $tids = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', $type)
      ->execute();
    if (!empty($tids)) {
      $count = count($tids);
      $controller = \Drupal::entityTypeManager()->getStorage('taxonomy_term');
      $entities = $controller->loadMultiple($tids);
      $controller->delete($entities);
      return $count . " no of contents are deleted";
    }
    else {
      return "Content not found !";
    }

  }

}
