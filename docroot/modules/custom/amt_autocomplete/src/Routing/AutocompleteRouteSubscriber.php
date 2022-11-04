<?php

namespace Drupal\amt_autocomplete\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Custom Autocomplete filter.
 */
class AutocompleteRouteSubscriber extends RouteSubscriberBase {

  /**
   * Custom Autocomplete filter.
   */
  public function alterRoutes(RouteCollection $collection) {
    if ($route = $collection->get('system.entity_autocomplete')) {
      $route->setDefault('_controller', '\Drupal\amt_autocomplete\Controller\EntityAutocompleteController::handleAutocomplete');
    }
  }

}
