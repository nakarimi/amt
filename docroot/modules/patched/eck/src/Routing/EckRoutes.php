<?php

namespace Drupal\eck\Routing;

use Drupal\eck\Entity\EckEntityType;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;

/**
 * Defines dynamic routes.
 *
 * @ingroup eck
 */
class EckRoutes {

  /**
   * {@inheritdoc}
   */
  public function routes() {
    $routeCollection = new RouteCollection();

    /** @var EckEntityType $entityType */
    foreach (EckEntityType::loadMultiple() as $entityType) {
      $entityTypeId = $entityType->id();
      $entityTypeLabel = $entityType->label();

      $routeCollection->add("eck.entity.{$entityTypeId}.list", $this->createListRoute($entityTypeId, $entityTypeLabel));
      $routeCollection->add("eck.entity.{$entityTypeId}_type.list", $this->createBundleListRoute($entityTypeId, $entityTypeLabel));
      $routeCollection->add("eck.entity.{$entityTypeId}_type.add", $this->createAddBundleRoute($entityTypeId, $entityTypeLabel));
      $routeCollection->add("entity.{$entityTypeId}_type.edit_form", $this->createEditBundleRoute($entityTypeId, $entityTypeLabel));
      $routeCollection->add("entity.{$entityTypeId}_type.delete_form", $this->createDeleteBundleRoute($entityTypeId, $entityTypeLabel));
    }
    return $routeCollection;
  }

  /**
   * @param string $entityTypeId
   * @param string $entityTypeLabel
   * @return \Symfony\Component\Routing\Route
   */
  private function createListRoute($entityTypeId, $entityTypeLabel) {
    $path = "admin/content/{$entityTypeId}";
    $defaults = [
      '_entity_list' => $entityTypeId,
      '_title' => '%type content',
      '_title_arguments' => ['%type' => ucfirst($entityTypeLabel)],
    ];
    $permissions = [
      "view own {$entityTypeId} entities",
      "view any {$entityTypeId} entities",
      "access {$entityTypeId} entity listing",
      "bypass eck entity access",
    ];
    $requirements = ['_permission' => implode('+', $permissions)];
    return new Route($path, $defaults, $requirements);
  }

  /**
   * @param string $entityTypeId
   * @param string $entityTypeLabel
   * @return \Symfony\Component\Routing\Route
   */
  private function createBundleListRoute($entityTypeId, $entityTypeLabel) {
    $path = "admin/structure/eck/{$entityTypeId}/bundles";
    $defaults = [
      '_controller' => '\Drupal\Core\Entity\Controller\EntityListController::listing',
      'entity_type' => "{$entityTypeId}_type",
      '_title' => '%type bundles',
      '_title_arguments' => ['%type' => ucfirst($entityTypeLabel)],
    ];
    return new Route($path, $defaults, $this->getBundleRouteRequirements());
  }

  /**
   * @return array
   */
  private function getBundleRouteRequirements() {
    return ['_permission' => 'administer eck entity bundles'];
  }

  /**
   * @param string $entityTypeId
   * @param string $entityTypeLabel
   * @return \Symfony\Component\Routing\Route
   */
  private function createAddBundleRoute($entityTypeId, $entityTypeLabel) {
    $path = "admin/structure/eck/{$entityTypeId}/bundles/add";
    return $this->createBundleCrudRoute($entityTypeId, $entityTypeLabel, $path, "add");
  }

  /**
   * @param string $entityTypeId
   * @param string $entityTypeLabel
   * @param $path
   * @param $op
   * @return \Symfony\Component\Routing\Route
   */
  private function createBundleCrudRoute($entityTypeId, $entityTypeLabel, $path, $op) {
    $defaults = [
      '_entity_form' => "{$entityTypeId}_type.{$op}",
      '_title' => ucfirst("{$op} %type bundle"),
      '_title_arguments' => ['%type' => $entityTypeLabel],
    ];
    return new Route($path, $defaults, $this->getBundleRouteRequirements());
  }

  /**
   * @param string $entityTypeId
   * @param string $entityTypeLabel
   * @return \Symfony\Component\Routing\Route
   */
  private function createEditBundleRoute($entityTypeId, $entityTypeLabel) {
    $path = "admin/structure/eck/{$entityTypeId}/bundles/{{$entityTypeId}_type}";
    return $this->createBundleCrudRoute($entityTypeId, $entityTypeLabel, $path, "edit");
  }


  /**
   * @param string $entityTypeId
   * @param string $entityTypeLabel
   * @return \Symfony\Component\Routing\Route
   */
  private function createDeleteBundleRoute($entityTypeId, $entityTypeLabel) {
    $path = "admin/structure/eck/{$entityTypeId}/bundles/{{$entityTypeId}_type}/delete";
    return $this->createBundleCrudRoute($entityTypeId, $entityTypeLabel, $path, "delete");
  }
}
