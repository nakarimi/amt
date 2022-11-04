<?php

namespace Drupal\amt_royalty\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * An amt_royalty controller.
 */
class AmtRoyaltyController extends ControllerBase {

  /**
   * Indicates whether the table is empty or not.
   *
   * @var tableEmpty
   */
  public $tableEmpty = TRUE;

  /**
   * Loading filtered data for service type.
   *
   * This function Loads the autocomplete data for
   * for chat success rate filters.
   */
  public function ajaxLoadAutoComplete() {
    // Call public search function and assign required data
    // that comes from the ajax request.
    // Multiple bundle should be seperated by comma then it will explod it.
    $baseData = amt_dayview_autocomplete($_POST['eckType'], $_POST['term'], $_POST['bundle'], $_POST['field']);
    return new JsonResponse($baseData);
  }

  /**
   * Base function to load the page.
   *
   * @return array
   *   Return the theme template name and the its script.
   */
  public function basePageCallback() {

    $royaltyReport = _amt_royalty_api_submit_royalty();

    if (!empty($royaltyReport)) {
      $royaltyInfoTable = $this->buildRoyaltyInfoTable($royaltyReport);
      $staffDataTable = $this->buildStaffDataTable($royaltyReport);
    }

    // Just when user want to see this page, the cache should be clear.
    return [
      '#theme' => 'amt_royalty',
      '#royalty_header_table' => (!empty($royaltyInfoTable)) ? $royaltyInfoTable : NULL,
      '#royalty_content_table' => (!empty($staffDataTable)) ? $staffDataTable : NULL,
      '#table_empty' => (empty($royaltyReport)) ? $this->tableEmpty : FALSE,
      '#empty_text' => $this->t('Reports for this week is empty!'),
      '#years' => [date("Y"), date("Y") - 1, date("Y") - 2, date("Y") - 3],
      '#weeks' => range(1, 52),
      '#selectedData' => (empty($_GET)) ? ['week' => date("W"), 'year' => date("Y")] : $_GET,
      '#attached' => [
        'library' => ['amt_royalty/amt_filters', 'amt_reports/amt_report_script'],
      ],
    ];
  }

  /**
   * Builds the Info table for the Royalty Report.
   */
  private function buildRoyaltyInfoTable($royaltyReport) {
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
      $infoRow[] = $royaltyReport[$key];
    }

    // Since this table has only one row, convert this row into a rows value.
    $rows = [$infoRow];

    $infoTable = [];

    // Build the royalty report header info table.
    $infoTable['royalty_data_header'] = [
      '#theme' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
    ];
    return $infoTable;
  }

  /**
   * Builds the table for staff.
   */
  private function buildStaffDataTable($royaltyReport) {
    $rows = !empty($royaltyReport['items']) ? $royaltyReport['items'] : NULL;
    
    $footer = [];
    if ($rows) {

      // Initialize needed vars.
      $totleLessonCount = $totleLessonPrice = $totlePrice = $totleCash = $totleMisc = $totleSundry = 0 ;

      // Total all values.
      foreach($rows as $row) {
        $totleLessonCount += $row['lesson_count'];
        $totlePrice += (int) ltrim($row['total_price'], '$');
        $totleCash += (int) ltrim($row['cash'], '$');
        $totleMisc += $row['miscellaneous_services'];
        $totleSundry += $row['sundry'];
      }

      // Make template of footer.
      $footer = [
        'receipt_number' => NULL, 
        'date_paid' => NULL, 
        'student_name' => NULL, 
        'executive' => NULL, 
        'sale_code' => NULL, 
        'package_name' => 'Total: ', 
        'lesson_count' => $totleLessonCount, 
        // 'lesson_price' => NULL, 
        'total_price' => '$'.$totlePrice, 
        'cash' => '$' . $totleCash, 
        'miscellaneous_services' => $totleMisc, 
        'subject_to_royalty' => $totleCash + $totleMisc, 
        'sundry' => $totleSundry,
        'total_receipts' => $totleCash + $totleMisc + $totleSundry
      ];
    }
    
    // Set the flag for whether there are results for this table.
    $this->tableEmpty = empty($royaltyReport['items']);

    // Create the header mapping for this table.
    $headerMap = [
      $this->t('Receipt Number'),
      $this->t('Date Paid'),
      $this->t('Student Name'),
      $this->t('Executive Staff Member'),
      $this->t('Sale Code'),
      $this->t('Sale Package'),
      $this->t('Lesson Count'),
      // $this->t('Lesson price'),
      $this->t('Total Price'),
      $this->t('Cash'),
      $this->t('Misc Services'),
      $this->t('Subject to Royalty'),
      $this->t('Sundry'),
      $this->t('Total Receipts'),
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
        'class' => ['table-class ami-royalty-report royalty_data_4_system'],
        'style' => ['white-space: nowrap;'],
      ],
      '#header' => $headers,
      '#rows' => $rows,
      '#footer' => array(
        array(
          'class' => array('footer-class'),
          'data' => $footer,
        ),
      ),
    ];

    // For CSV export.
    $session = \Drupal::request()->getSession();
    $session->set('csv_header', $headerMap);
    $session->set('csv_rows', $rows);
    $session->set('csv_file_name', 'Royalty_Report.csv');
    
    return $staffDatatable;
  }

}
