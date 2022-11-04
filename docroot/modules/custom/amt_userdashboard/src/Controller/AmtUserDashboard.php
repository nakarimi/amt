<?php

namespace Drupal\amt_userdashboard\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * An amt_userdashboard controller.
 */
class AmtUserDashboard extends ControllerBase {

  /**
   * Base function to load the page.
   *
   * @return array
   *   Return the theme template name and the its script.
   */
  public function basePageCallback($user) {

    // Loading the required data.
    $teachers_data = amt_userdashboard_load_teacher_data($user);
    
    return [
      '#theme' => 'amt_userdashboard',
      '#teacher_data' => $teachers_data,
      '#attached' => [
        'library' => [
          'amt_userdashboard/amt_userdashboard_general',
        ],
      ],
    ];
  }

}
