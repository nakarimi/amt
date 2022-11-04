<?php

namespace Drupal\amt_feeds\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * An amt_dayview controller.
 */
class AmtFeedsController extends ControllerBase {

  /**
   * A custom function.
   *
   * AMT feeds to set color to each term of lesson type and service type.
   */
  public function setTermsColor() {

    // List of specific service type that has same maroon color.
    $maroonColorServiceTypes = [
      'Regularly Schedule Out',
      'Meeting',
      'Dance Session',
      'Calls',
      'Read Me',
      'Training Class',
      'Desk',
      'One to One',
      'Out Sick',
      'Assist Lesson',
      'Internal Use',
      'Outside Event',
    ];

    // List of specific service type that has same blue color.
    $blueColorServiceTypes = [
      'Misc',
      'Dream Sheet',
      'Service Visit',
      'Benefit Sheet',
    ];

    // List of specific service types that has same pink color.
    $pinkColorServiceTypes = [
      'Meal Break',
      'Break',
    ];

    // List of lesson and service types with colors and abbrivations.
    $serviceAndLessonColors = [
      'Front Department Lesson (FD)'    => ['#001A57', 'BPRI'],
      'Coach Lesson (C)'         => ['#01FF70', 'COACH'],
      'Group'                    => ['#3D9970', 'GRP'],
      'Buddy Lesson'             => ['#FF851B', 'BDYLSN'],
      'Master Class'             => ['#add8e6', 'MCLS'],
      'Practice Party'           => ['#3D9970', 'PARTY'],
      'Front Department Lesson (FD)'        => ['#E8A8B8', 'FPRI'],
      'Dance Evaluation (DE)'         => ['#39CCCC', 'DE'],
      'Bonus/Comp Lesson'              => ['#FFDC00', 'CMPL'],
      'Wedding Lesson'           => ['#B10DC9', 'WED'],
      'Middle Department Lesson' => ['#E6E6FA', 'MPRI'],
      // Service types.
      'Service Chat'             => ['#B10DC9'],
      'Graduation'               => ['#F9A602'],
      'Dance Show'               => ['#3D9970'],
      'Out by Time Off Request'  => ['#FCF4A3'],
      'Schedule'                 => ['#01FF70'],
      'Tuition'                  => ['#F012BE'],
      'Coaching Lesson'          => ['#01FF70'],
      'Lesson Package Chat'      => ['#7FDBFF'],
      'Dance O Rama Chat'        => ['#FCA3B7'],
      'Progress Check'           => ['#d5a6e6'],
      'Grad Chat'                => ['#E6E6FA'],
      'Showcase Chat'            => ['#FCA3B7'],
      'Original Chat'            => ['#7FDBFF'],
    ];
    // Query service types and lesson types taxonomy terms.
    $termQuery = \Drupal::entityQuery('taxonomy_term')
      ->condition('vid', ['lesson_type', 'service_type'], 'IN')
      ->execute();
    // Load terms.
    $terms = \Drupal::entityManager()
      ->getStorage('taxonomy_term')
      ->loadMultiple($termQuery);

    foreach ($terms as $term) {
      $color = "";
      $abbr = "";
      $termName = $term->getName();

      // If the termname is and maroon service type.
      if (in_array($termName, $maroonColorServiceTypes)) {
        $color = '#85144b';
      }

      // If the termname is and blue service type.
      elseif (in_array($termName, $blueColorServiceTypes)) {
        $color = '#0074D9';
      }

      // Set terms to pink color.
      elseif (in_array($termName, $pinkColorServiceTypes)) {
        $color = '#AD5C84';
      }
      // If term is in service and lesson types, then get its color.
      elseif (array_key_exists($termName, $serviceAndLessonColors)) {
        $color = $serviceAndLessonColors[$termName][0];

        // Check that the matched item has abbrivation or not.
        if (count($serviceAndLessonColors[$termName]) > 1) {
          $abbr = $serviceAndLessonColors[$termName][1];
        }
      }

      $term->set('field_color', $color);
      $term->set('field_abbreviation', $abbr);
      $term->save();
    }
    // Redirect to homepage after saving.
    // Set base path.
    global $base_url;
    // Set Url.
    $response = new RedirectResponse($base_url . "/");
    $response->send();
    // Show message while redirecting.
    drupal_set_message(t('Colors and Abbreviations Set Successfully'), 'status', TRUE);
  }
}
