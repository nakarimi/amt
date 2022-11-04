<?php

namespace Drupal\Tests\eck\Unit;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\TypedData\TypedDataManagerInterface;
use Drupal\eck\EckEntityTypeBundleInfo;
use PHPUnit_Framework_MockObject_MockObject;

/**
 * Tests the form element implementation.
 *
 * @group eck
 */
class EntityTypeBundleInfoTest extends UnitTestBase {

  /** @var EntityTypeManagerInterface */
  protected $entityTypeManagerMock;
  /** @var LanguageManagerInterface */
  protected $languageManagerMock;
  /** @var ModuleHandlerInterface */
  protected $moduleHandlerMock;
  /** @var TypedDataManagerInterface */
  protected $typedDataManagerMock;
  /** @var CacheBackendInterface */
  protected $cacheBackendMock;

  /**
   * @test
   */
  public function returnsFalseWhenNonExistingEntityTypeIsPassed() {
    $sut = $this->createNewTestSubject();
    $this->assertFalse($sut->entityTypeHasBundles('does not exist'));
  }

  /**
   * @test
   */
  public function returnsFalseWhenEntityTypeHasNoBundles() {
    $sut = $this->createNewTestSubjectWithEntityType();
    $this->assertFalse($sut->entityTypeHasBundles('existing_entity_type'));
  }

  /**
   * @test
   */
  public function returnsTrueWhenEntityTypeHasAtLeastOneBundle() {
    $sut = $this->createNewTestSubjectWithEntityTypeAndBundles();
    $this->assertTrue($sut->entityTypeHasBundles('existing_entity_type'));
  }

  /**
   * @test
   */
  public function entityTypeHasBundlesMethodCachesData() {
    $this->cacheBackendMock = $this->getMockForAbstractClass(CacheBackendInterface::class);
    $this->cacheBackendMock->expects($this->once())->method('set');
    $sut = $this->createNewTestSubject();
    $sut->entityTypeHasBundles('test');
  }

  /**
   * @test
   */
  public function usesCachedDataWhenAvailable() {
    $this->cacheBackendMock = $this->cacheBackendMock = $this->getMockForAbstractClass(CacheBackendInterface::class);
    $this->cacheBackendMock->expects($this->once())
      ->method('get')
      ->willReturn((object) ['data' => 'obviously not normal bundle info']);

    $sut = $this->createNewTestSubject();
    $this->assertSame('obviously not normal bundle info', $sut->getAllBundleInfo());
  }

  /**
   * @test
   */
  public function returnsNoMachineNamesIfEntityTypeDoesNotExist() {
    $sut = $this->createNewTestSubject();
    $this->assertEmpty($sut->getEntityTypeBundleMachineNames('non_existing_entity_type'));
  }

  /**
   * @test
   */
  public function returnsNoMachineNamesIfEntityTypeHasNoBundles() {
    $sut = $this->createNewTestSubjectWithEntityType();
    $this->assertEmpty($sut->getEntityTypeBundleMachineNames('existing_entity_type'));
  }

  /**
   * @test
   */
  public function returnsMachineNamesIfEntityTypeHasBundles() {
    $sut = $this->createNewTestSubjectWithEntityTypeAndBundles();
    $this->assertNotEmpty($sut->getEntityTypeBundleMachineNames('existing_entity_type'));
  }

  /**
   * @test
   */
  public function returnsZeroIfEntityTypeDoesNotExist() {
    $sut = $this->createNewTestSubject();
    $this->assertEquals(0, $sut->entityTypeBundleCount('non_existing_entity_type'));
  }

  /**
   * @test
   */
  public function returnsZeroIfEntityTypeHasNoBundles() {
    $sut = $this->createNewTestSubjectWithEntityType();
    $this->assertEquals(0, $sut->entityTypeBundleCount('existing_entity_type'));
  }

  /**
   * @test
   */
  public function correctlyCountsEntityTypeBundles() {
    for ($i = 1; $i <= 10; $i++) {
      $sut = $this->createNewTestSubjectWithEntityTypeAndBundles($i);
      $this->assertEquals($i, $sut->entityTypeBundleCount('existing_entity_type'));
    }
  }

  /**
   * @return EckEntityTypeBundleInfo
   */
  protected function createNewTestSubject() {
    if (NULL === $this->entityTypeManagerMock) {
      $this->entityTypeManagerMock = $this->getMockForAbstractClass(EntityTypeManagerInterface::class);
      $this->entityTypeManagerMock->method('getDefinitions')->willReturn([]);
    }
    if (NULL === $this->languageManagerMock) {
      $this->languageManagerMock = $this->createLanguageManagerMock();
    }
    if (NULL === $this->moduleHandlerMock) {
      $this->moduleHandlerMock = $this->getMockForAbstractClass(ModuleHandlerInterface::class);
    }
    if (NULL === $this->typedDataManagerMock) {
      $this->typedDataManagerMock = $this->getMockForAbstractClass(TypedDataManagerInterface::class);
    }
    if (NULL === $this->cacheBackendMock) {
      $this->cacheBackendMock = $this->getMockForAbstractClass(CacheBackendInterface::class);
    }

    return new EckEntityTypeBundleInfo($this->entityTypeManagerMock, $this->languageManagerMock, $this->moduleHandlerMock, $this->typedDataManagerMock, $this->cacheBackendMock);
  }

  /**
   * @param PHPUnit_Framework_MockObject_MockObject $entityTypeMock
   * @param PHPUnit_Framework_MockObject_MockObject $entityStorageMock
   *
   * @return EckEntityTypeBundleInfo
   */
  protected function createNewTestSubjectWithEntityType(PHPUnit_Framework_MockObject_MockObject $entityTypeMock = NULL, PHPUnit_Framework_MockObject_MockObject $entityStorageMock = NULL) {
    if (NULL === $entityTypeMock) {
      $entityTypeMock = $this->getMockForAbstractClass(EntityTypeInterface::class);
      $entityTypeMock->method('getBundleEntityType')
        ->willReturn('eck_entity_bundle');
    }
    if (NULL === $entityStorageMock) {
      $entityStorageMock = $this->getMockForAbstractClass(EntityStorageInterface::class);
      $entityStorageMock->method('loadMultiple')->willReturn([]);
    }

    $this->entityTypeManagerMock = $this->getMockForAbstractClass(EntityTypeManagerInterface::class);
    $this->entityTypeManagerMock->method('getDefinitions')
      ->willReturn(['existing_entity_type' => $entityTypeMock]);
    $this->entityTypeManagerMock->method('getStorage')
      ->willReturn($entityStorageMock);

    return $this->createNewTestSubject();
  }

  /**
   * @param int $numberOfBundlesToCreate
   *
   * @return EckEntityTypeBundleInfo
   */
  protected function createNewTestSubjectWithEntityTypeAndBundles($numberOfBundlesToCreate = 1) {
    $bundles = [];
    for ($i = 0; $i < $numberOfBundlesToCreate; $i++) {
      $machineName = $this->randomMachineName();
      $bundleMock = $this->getMockForAbstractClass(EntityInterface::class);
      $bundleMock->method('id')->willReturn(strtolower($machineName));
      $bundleMock->method('label')->willReturn($machineName);
      $bundles[strtolower($machineName)] = $bundleMock;
    }
    $entityStorageMock = $this->getMockForAbstractClass(EntityStorageInterface::class);
    $entityStorageMock->method('loadMultiple')->willReturn($bundles);
    return $this->createNewTestSubjectWithEntityType(NULL, $entityStorageMock);
  }

}
