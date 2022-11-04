<?php

namespace Drupal\Tests\eck\Functional;

use Drupal\Core\Url;

/**
 * Tests eck's bundle creation, update and deletion.
 *
 * @group eck
 */
class BundleCRUDTest extends FunctionalTestBase {

  /**
   * @test
   */
  public function singleBundleCreation() {
    $entityTypeInfo = $this->createEntityType([], 'TestType');
    $this->createEntityBundle($entityTypeInfo['id'], 'TestBundle');
  }

  /**
   * @test
   */
  public function multipleBundleCreation() {
    $entityTypeInfo = $this->createEntityType([], 'TestType');
    $this->createEntityBundle($entityTypeInfo['id'], 'TestBundle1');
    $this->createEntityBundle($entityTypeInfo['id'], 'TestBundle2');
  }

  /**
   * @test
   */
  public function identicallyNamedBundleCreation() {
    $entityTypeInfo1 = $this->createEntityType([], 'TestType1');
    $entityTypeInfo2 = $this->createEntityType([], 'TestType2');

    $this->createEntityBundle($entityTypeInfo1['id'], 'TheBundle');
    $this->createEntityBundle($entityTypeInfo2['id'], 'TheBundle');
  }

}
