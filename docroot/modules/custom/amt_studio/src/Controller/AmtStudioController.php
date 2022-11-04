<?php

namespace Drupal\amt_studio\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\amt_staff\SendReportsToAPI;

/**
 * An amt_studio controller.
 */
class AmtStudioController extends ControllerBase {

  /**
   * Indicates whether the table is empty or not.
   *
   * @var bool
   */
  public $tableEmpty = TRUE;

  /**
   * Base function to load the page.
   *
   * @return array
   *   Return the theme template name and the its script.
   */
  public function basePageCallback() {

    // Return the data to twig template view where the containing.
    $rows = [];
    $csvRows = [];  // For csv export.
    
    // Iterate over the mapping to build all of the rows and headers.
    foreach (_amt_studio_api_submit_sps() as $key => $label) {
      $rows[] = [
        ['data' => ucwords(str_replace("_", " ", $key))],
        $label,
      ];

      // Collect rows for csv export file.
      $csvRows[] = [ucwords(str_replace("_", " ", $key)), $label];
    }

    // Build the royalty report header info table.
    $studioReportTable['studio_content_table'] = [
      '#theme' => 'table',
      '#rows' => $rows,
    ];

    // Pass date range to display on top of the page.
    $date_range = '';
    if (isset($_GET['week'])) {
      $date_range = SendReportsToAPI::convertStartEndDate($_GET['week'], $_GET['year']);
      $date_range = date('m-d-Y', strtotime($date_range['week_start'])) . ' To ' . date('m-d-Y', strtotime($date_range['week_end']));
    }
    
    // For CSV export.
    $session = \Drupal::request()->getSession();
    // Since report for this does not have header, but just two colums, we add empty header.
    $session->set('csv_header',  [ $this->t(''), $this->t('')]);
    $session->set('csv_rows', $csvRows);
    $session->set('csv_file_name', 'Studio_Business_Summary.csv');

    return [
      '#theme' => 'amt_studio',
      '#studio_content_table' => $studioReportTable,
      '#date_range' => $date_range,
      '#table_empty' => (empty($rows)) ? $this->tableEmpty : FALSE,
      '#years' => [date("Y"), date("Y") - 1, date("Y") - 2, date("Y") - 3],
      '#weeks' => range(1, 52),
      '#selectedData' => (empty($_GET)) ? ['week' => date("W"), 'year' => date("Y")] : $_GET,
      '#attached' => [
        'library' => ['amt_studio/amt_scripts', 'amt_reports/amt_report_script'],
      ],
    ];
  }

}
