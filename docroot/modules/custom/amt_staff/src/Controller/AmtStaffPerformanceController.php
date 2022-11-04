<?php

namespace Drupal\amt_staff\Controller;

use Drupal\Core\Controller\ControllerBase;

/**
 * An amt_staff controller.
 */
class AmtStaffPerformanceController extends ControllerBase {

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
    // Check that filter data passed by get method.
    if (!empty($_GET)) {
      if ($_GET['week'] != '' || $_GET['year'] != '') {
        // This script is going to load
        // studio business report in a new tab.
        // First it checks if the a certian parameter is passed,
        // the studio report is then loaded passing
        // the week and year parameter in the current url.
        echo "
        <script>
          let searchParams = new URLSearchParams(window.location.search)
          if (searchParams.has('drop')) {
            window.open('/studio-business-summary?year=" . $_GET['year'] . "&week=" . $_GET['week'] . "', '_blank');
          }
        </script>";
        $staffReport = _amt_staff_performance_reports();
        if (!empty($staffReport)) {
          $staffInfoTable = $this->buildStaffInfoTable($staffReport);
          $staffDataTable = $this->buildStaffDataTable($staffReport);
        }
      }
    }

    // Just when user want to see this page, the cache should be clear.
    return [
      '#theme' => 'amt_staff',
      '#staff_header_table' => (!empty($staffInfoTable)) ? $staffInfoTable : NULL,
      '#staff_content_table' => (!empty($staffDataTable)) ? $staffDataTable : NULL,
      '#table_empty' => (empty($staffReport)) ? $this->tableEmpty : FALSE,
      '#years' => [date("Y"), date("Y") - 1, date("Y") - 2, date("Y") - 3],
      '#weeks' => range(1, 52),
      '#selectedData' => (empty($_GET)) ? ['week' => date("W"), 'year' => date("Y")] : $_GET,
      '#attached' => [
        'library' => ['amt_staff/amt_filters', 'amt_reports/amt_report_script'],
      ],
    ];
  }

  /**
   * Builds the Info table for the Staff Report.
   */
  private function buildStaffInfoTable($staffReport) {
    // Define the table mapping for headers and row data.
    $tableMapping = [
      'type' => $this->t('Report Type'),
      'prepared_by' => $this->t('Prepared By'),
      'week_year' => $this->t('Year'),
      'week_number' => $this->t('Week Number'),
      'date_range' => $this->t('Date Range'),
    ];

    $headers = $rows = $infoRow = [];
    // Iterate over the mapping to build all of the rows and headers.
    foreach ($tableMapping as $key => $label) {
      $headers[] = ['data' => $label];
      $infoRow[] = $staffReport[$key];
    }

    // Since this table has only one row, convert this row into a rows value.
    $rows = [$infoRow];

    $infoTable = [];

    // Build the Staff report header info table.
    $infoTable['staff_data_header'] = [
      '#theme' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
    ];
    return $infoTable;
  }

  /**
   * Builds the Data table for the Staff Report.
   */
  private function buildStaffDataTable($staffReport) {
    $rows = !empty($staffReport['items']) ? $staffReport['items'] : NULL;

    // Set the flag for whether there are results for this table.
    $this->tableEmpty = empty($staffReport['items']);

    // Create the header mapping for this table.
    $headerMap = [
      $this->t('Name'),
      // $this->t('Email'),
      $this->t('Staff Type'),
      $this->t('DE/Bonus'),
      $this->t('Number of Guests'),
      $this->t('Private Lessons'),
      $this->t('Number in Class'),
      // $this->t('Staff Member'),
      $this->t('Interview Dept'),
      $this->t('Renewal Dept'),
      $this->t('Dor/Sanct. Competition'),
      $this->t('Showcase Medal Ball'),
      $this->t('Party Time Non-Unit'),
    ];

    // Iterate over the headers to build them into what tables expect.
    $headers = [];
    foreach ($headerMap as $label) {
      $headers[] = ['data' => $label];
    }

    // Define the staff data table.
    $staffDatatable = [];
    $staffDatatable['staff_data_4_system'] = [
      '#theme' => 'table',
      '#attributes' => [
        'class' => ['table-class ami-staff-report'],
        'style' => ['white-space: nowrap;'],
      ],
      '#header' => $headers,
      '#rows' => $rows,
    ];

    // For CSV export.
    $session = \Drupal::request()->getSession();
    $session->set('csv_header', $headerMap);
    $session->set('csv_rows', $rows);
    $session->set('csv_file_name', 'Staff_Performance.csv');

    return $staffDatatable;
  }

}
