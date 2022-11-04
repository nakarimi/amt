<?php

namespace Drupal\amt_userdashboard\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Listens to the dynamic route events.
 */
class UserDashboardRedirection extends RouteSubscriberBase {

    /**
     * {@inheritdoc}
     */
    protected function alterRoutes(RouteCollection $collection) {
        // Change the route associated with the user profile page (/user, /user/{uid}).
        if ($route = $collection->get('user.page')) {
        $route->setDefault('_controller', '\Drupal\amt_userdashboard\Controller\AmtUserDashboard::basePageCallback');
        }
    }

}