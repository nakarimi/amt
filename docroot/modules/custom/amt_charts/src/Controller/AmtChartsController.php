<?php

namespace Drupal\amt_charts\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * An amt_dayview controller.
 */
class AmtChartsController extends ControllerBase {

  /**
   * Returns a render-able array for Dasboard page containing payments data.
   */
  public function ajaxLoadChartData() {
    // dd($_POST['typess']);.
    if ($_POST['chartCategory'] == 'payments') {
      // Loading Payments data for chart generation.
      $chartData['payments'] = _amt_charts_load_payment_charts();
    }
    elseif ($_POST['chartCategory'] == 'enrollments') {
      // Encoding the final data to json format.
      $chartData['enrollment'] = _amt_charts_enrollment_sold_chart();
    }
    elseif ($_POST['chartCategory'] == 'lessons') {
      $chartData['lessons_booked'] = _amt_charts_lesson_booked();
      // Encoding the data in json format.
    }
    $chartData = json_encode($chartData);
    // Returning the data as JsonResponse.
    return new JsonResponse($chartData);
  }

  /**
   * Loading the Dashboard paage.
   */
  public function loadDashboardPage() {
    $showed = amt_charts_query_for_inquiry_data('field_showed');
    $contact = amt_charts_query_for_inquiry_data('field_inquired');
    $booked = amt_charts_query_for_inquiry_data('field_booked_on', [572, 581, 580]);

    return [
      '#theme' => 'amt_charts',
      '#showed' => (!empty($showed)) ? $showed : NULL,
      '#contact' => (!empty($contact)) ? $contact : NULL,
      '#booked' => (!empty($booked)) ? $booked : NULL,
      '#attached' => [
        'library' => [
          'amt_charts/amt_scripts',
        ],
      ],
    ];
  }

}
