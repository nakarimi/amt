<?php

namespace Drupal\amt_reports\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;


/**
 * An amt_reports controller.
 */
class AmtReportsController extends ControllerBase {

  /**
   * Indicates whether the table is empty or not.
   *
   * @var bool
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
   * Loading Chat Success Rate data for user.
   *
   * This function Loads the user and services.
   */
  public function loadChatSuccessRate() {
    // Call public search function and assign required data.
    // Get the Data that is returned from module.
    $successRateData = _amt_reports_chat_success_rate();
    // Check if data is being filtered or not.
    // If data was filtered then build the filter version of table.
    if ($_GET['service_type'] != '' || $_GET['role'] != '' || ($_GET['start_date'] != '' && $_GET['end_date'] != '')) {
      $rateTable = $this->buildFilterRateDataTable($successRateData);
    }
    // Otherwise build table without any filter.
    else {
      $rateTable = $this->buildStaffDataTable($successRateData);
    }
    return [
      '#theme' => 'chat_success_rate',
      '#rate_table' => (!empty($rateTable)) ? $rateTable : NULL,
      '#table_empty' => (empty($staffReport)) ? $this->tableEmpty : FALSE,
      '#service_type' => $_GET['service_type'],
      '#start_date' => $_GET['start_date'],
      '#end_date' => $_GET['end_date'],
      '#userRole' => $_GET['role'],
      '#attached' => [
        'library' => ['chat_success_rate/amt_scripts'],
      ],
    ];
  }

  /**
   * Loading Active Students List.
   *
   * This function proviad the list of active student and
   * pass it in twig file.
   */
  public function loadStudentsList() {
    $activePage = 1;
    $totalPages = 1;
    $studentList = [];
    if (!empty($_GET['status']) && $_GET['status'] == "paid_finished") {
      $studentList = _amt_reports_students_list_paid_finished();
    }
    else {
      if (isset($_GET['status']) && count($_GET['status']) == 1) {
        if (in_array('nfa', $_GET['status'])) {
          $studentStatus = 'nfa';
          // Loading list of nfa students.
          $studentList = _amt_reports_students_list($studentStatus);
        }
        if (in_array('inactive', $_GET['status'])) {
          $studentStatus = 'inactive';
          // Loading list of inactive students.
          $studentList = _amt_reports_students_list($studentStatus);
        }
        if (in_array('active', $_GET['status'])) {
          $studentStatus = 'active';
          // Loading list of active students.
          $studentList = _amt_reports_students_list($studentStatus);
        }
      }
      elseif (isset($_GET['status']) && count($_GET['status']) == 2) {
        if (in_array('active', $_GET['status']) && in_array('nfa', $_GET['status'])) {
          $studentStatus = 'act-nfa';
          // Loading list of act-nfa students.
          $studentList = _amt_reports_students_list($studentStatus);
        }
        if (in_array('inactive', $_GET['status']) && in_array('nfa', $_GET['status'])) {
          $studentStatus = 'inact-nfa';
          // Loading list of act-nfa students.
          $studentList = _amt_reports_students_list($studentStatus);
        }
        if (in_array('inactive', $_GET['status']) && in_array('active', $_GET['status'])) {
          $studentStatus = 'inact-act';
          // Loading list of act-nfa students.
          $studentList = _amt_reports_students_list($studentStatus);
        }
      }
  
      else {
        $studentStatus = 'all';
        // Loading list of all students.
        $studentList = _amt_reports_students_list($studentStatus);
      }
    }
    $totalPages = $studentList['totalPages'];
    $studentList = $studentList['data'];

    // Set URL for pagination of student list.
    $pagerUrl = \Drupal::request()->getRequestUri();
    if (isset($_GET['page'])) {
      $pagerUrl = explode('page=', $pagerUrl)[0] . "page=";
      if ($_GET['page'] != '') {
        $activePage = $_GET['page'];
      }
    }
    else {
      $pagerUrl .= "?page=";
    }

    $filterData['status'] = $studentStatus;
    $filterData['statusCheck'] = (isset($_GET['status']))? $_GET['status'] : '';
    $filterData['department'] = (isset($_GET['department']))? $_GET['department'] : '';
    $filterData['date'] = (isset($_GET['date']))? $_GET['date'] : '';
    $filterData['name'] = (isset($_GET['name']))? $_GET['name'] : '';
    $filterData['email'] = (isset($_GET['email']))? $_GET['email'] : '';
    $filterData['phone'] = (isset($_GET['phone']))? $_GET['phone'] : '';
    $filterData['paid_finished'] = (isset($_GET['paid_finished']))? $_GET['paid_finished'] : '';
    $filterData['instructor'] = (isset($_GET['instructor']))? $_GET['instructor'] : '';
    $filterData['instructors'] = amt_reports_load_all_instructor();

    return [
      '#theme' => 'amt_active_students',
      '#events' => (!empty($studentList)) ? $studentList : NULL,
      '#departments' => amt_dashboard_load_departments_ids(),
      '#filterData'  => $filterData,
      '#active_page' => $activePage,
      '#total_pages' => $totalPages,
      '#pager_url' => $pagerUrl,
      '#attached' => [
        'library' => [
          'amt_reports/amt_students_filters',
          'amt_reports/amt_filters',
          'amt_reports/amt_mass_student_department_update'
          // 'amt_dashboard/amt_datatable',
        ],
      ],
    ];
  }

  /**
   * Loading Active Students List.
   *
   * This function proviad the list of active student and
   * pass it in twig file.
   */
  public function loadStudentsInventory() {

    $studentList = [];
    $studentStatus = isset($_GET['type']) ? $_GET['type'] : 'active';
    $filterData['type'] = $studentStatus;
    $filterData['from'] = (isset($_GET['from']))? $_GET['from'] : false;
    $filterData['to'] = (isset($_GET['to']))? $_GET['to'] : false;
    $activePage = isset($_GET['page'][0]) ? $_GET['page'] : 1;

    $pagerUrl = amt_reports_get_curent_pageURL();
   
    // Get ids of the students (active or inactive).
    $data = _amt_reports_students_list($studentStatus, $retriveIds = true, $numElement = 25);

    foreach ($data['ids'] as $id) {

      // Load student account to get title.
      $studentAccount = \Drupal::entityTypeManager()->getStorage('contacts')->load($id);

      // Total lessons enrolled.
      $totalEnrolled = amt_dashboard_load_student_total_lessons_enrolled($id, $filterData['from'], $filterData['to']);

      // lesson used for current student.
      $lessonUsed = amt_dashboard_total_lessons_used($id, $convert = true);

      // lesson used for current student.
      $remaining = amt_dashboard_student_total_lessons_remaining($id);

      $lastLessonThaught = amt_reports_get_last_thaught_lesson_id($id);
      $address = [];
      $address[] = $studentAccount->field_address->locality;
      $address[] = $studentAccount->field_address->address_line1;

      $studentList[] = [
        'name' => $studentAccount->title->value,
        'address' => implode(', ', array_filter($address)),
        'totalEnrolled' => $totalEnrolled,
        'lessonUsed' => $lessonUsed,
        'remaining' => $remaining,
        'lastLessonThaught' => $lastLessonThaught,
        'test' => ''
      ];
    }

    return [
      '#theme' => 'amt_students_inventory',
      '#students' => (!empty($studentList)) ? $studentList : NULL,
      '#filterData'  => $filterData,
      '#active_page' => $activePage,
      '#total_pages' => $data['totalPages'],
      '#pager_url' => $pagerUrl,
      '#attached' => [
        'library' => [
          // 'amt_reports/amt_student_inventory_report',
          // 'amt_reports/amt_report_script',
        ],
      ],
    ];
  }

  /**
   * Loading Projections Report.
   *
   * This function proviad the list of NFA student and
   * pass it in twig file.
   */
  public function loadProjectionsReport() {
    $totalPages = 1;
    $activePage = 1;
    $pagerUrl = \Drupal::request()->getRequestUri();
    if (isset($_GET['page'])) {
      $pagerUrl = explode('page=', $pagerUrl)[0] . "page=";
      if ($_GET['page'] != '') {
        $activePage = $_GET['page'];
      }
    }
    else {
      $pagerUrl .= "?page=";
    }
    if (isset($_GET['projection_date']) && $_GET['projection_date'] != '') {
      $filterData['projection_date'] = $_GET['projection_date'];
    }
    if (isset($_GET['student_name']) && $_GET['student_name'] != '') {
      $filterData['student_name'] = $_GET['student_name'];
    }
    if (isset($_GET['enrollment_title']) && $_GET['enrollment_title'] != '') {
      $filterData['enrollment_title'] = $_GET['enrollment_title'];
    }
    if (isset($_GET['status']) && $_GET['status'] != '') {
      $filterData['status'] = $_GET['status'];
    }
    $projectionsReport = _amt_reports_projections_report_list($activePage, $totalPages);
    $projectionsTable = $this->buildProjectionsReportTable($projectionsReport);
    return [
      '#theme' => 'amt_projections_report',
      '#projections_table' => (!empty($projectionsTable)) ? $projectionsTable : NULL,
      '#active_page' => $activePage,
      '#total_pages' => $totalPages,
      '#pager_url' => $pagerUrl,
      '#filter_data' => $filterData,
      '#attached' => [
        'library' => ['amt_reports/amt_filters'],
      ],
    ];
  }

  /**
   * Builds the Data table for the Staff Report.
   */
  private function buildProjectionsReportTable($projectionsReport) {
    // Set the flag for whether there are results for this table.
    $this->tableEmpty = empty($projectionsReport);

    // Create the header mapping for this table.
    $header = [
      ['data' => t('Date')],
      ['data' => t('Students')],
      ['data' => t('Enrollments')],
      ['data' => t('Expected Amount')],
      ['data' => t('Status')],
    ];

    // Define the staff data table.
    $buildTable['projections-report'] = [
      '#theme' => 'table',
      '#attributes' => [
        'class' => ['projections-report'],
        'style' => ['white-space: nowrap;'],
      ],
      '#header' => $header,
      '#rows' => $projectionsReport,
    ];
    return $buildTable;
  }

  /**
   * Builds the Data table for the Staff Report.
   */
  private function buildStaffDataTable($successRateData) {
    // Set the flag for whether there are results for this table.
    $this->tableEmpty = empty($successRateData);

    // Create the header mapping for this table.
    $header = [
      ['data' => t('Name')],
      ['data' => t('Role')],
      ['data' => t('Service Type')],
      ['data' => t('Success Rate')],
    ];

    // Define the staff data table.
    $buildTable['success-rate'] = [
      '#theme' => 'table',
      '#attributes' => [
        'class' => ['success-rate'],
        'style' => ['white-space: nowrap;'],
      ],
      '#header' => $header,
      '#rows' => $successRateData,
    ];
    return $buildTable;
  }

  /**
   * Builds the Data table for the chat success rate filters.
   */
  private function buildFilterRateDataTable($filterSuccessRateData) {
    // Set the flag for whether there are results for this table.
    $this->tableEmpty = empty($filterSuccessRateData);

    // Create the header mapping for this table.
    $header = [
      ['data' => t('Service Type')],
      ['data' => t('Name')],
      ['data' => t('Role')],
      ['data' => t('Success Rate')],
      ['data' => t('Average Success Rate')],
    ];

    // Define the staff data table.
    $buildTable['success-rate'] = [
      '#theme' => 'table',
      '#attributes' => [
        'class' => ['success-rate'],
        'style' => ['white-space: nowrap;'],
      ],
      '#header' => $header,
      '#rows' => $filterSuccessRateData,
    ];
    return $buildTable;
  }

  /**
   * Builds the Data table for the chat success rate filters.
   */
  private function buildTimeConflictDataTable($conflictData) {
    // Set the flag for whether there are results for this table.
    $this->data_table = empty($conflictData);

    // Create the header mapping for this table.
    $header = [
      ['data' => t('Event Title')],
      ['data' => t('Time')],
      ['data' => t('Conflicted With')],
      ['data' => t('Conflicted Time')],
    ];

    // Define the staff data table.
    $buildTable['success-rate'] = [
      '#theme' => 'table',
      '#attributes' => [
        'class' => ['success-rate'],
        'style' => ['white-space: nowrap;'],
      ],
      '#header' => $header,
      '#rows' => $conflictData,
    ];
    return $buildTable;
  }

  /**
   * Loading Time Conflict of Events.
   *
   * This function Load Instrucotr And check Conflict of Time.
   */
  public function loadTimeConflictList() {
    // Call public search function and assign required data.
    $dataTable = [];
    $eventID = isset($_GET['eid']) ? $_GET['eid'] : FALSE;
    // Source: where the lesson has been created, like dayview.
    $source = $_GET['source'];
    // Getting the date the event is created to be used in dayview.
    $date = $_GET['date'];
    if ($eventID) {
      $standingTimeData = _amt_reports_instructor_time_list();
      $dataTable = $this->buildTimeConflictDataTable($standingTimeData);
    }
    return [
      '#theme' => 'instructor_time_conflict',
      '#data_table' => $dataTable,
      '#eventID' => $eventID,
      '#source' => $source,
      '#date' => $date,
      '#attached' => [
        'library' => ['amt_reports/amt_conflict_printstyle'],
      ],
    ];
  }

  /**
   * Loading Time Conflict of Events.
   *
   * This function Load Instrucotr And check Conflict of Time.
   */
  public function loadTimeConflictListpagetitle() {
    $eventID = isset($_GET['eid']) ? $_GET['eid'] : FALSE;
    if ($eventID) {
      $event = \Drupal::entityManager()->getStorage('events')->load($eventID);
      return $event->get('title')->value . ' Time Conflict Check ';
    }
    else {
      return 'Error';
    }
  }

  /**
   * Mass updating student department.
   * 
   * This function hanldes the mass update of
   * students department. The request is coming from
   * the students-list page where it sends the IDs of
   * the student accounts that there student department
   * field should be updated as requested.
   */
  public function massStudentDepartmentUpdate() {
    // Getting the IDs of the student accounts.
    $students = $_POST['students'];

    // Getting the new chosen deparment's id.
    $department_id = $_POST['department'];

    // Path for redirection.
    $path = $_POST['redirection_path'];

    // If the list of Students IDs is not empty.
    if ($students != null && $students != "") {
      // Turn stringified array to json and then make the array unique.
      $students_array = array_unique(json_decode($students));

      // Loop through the IDs of student accounts.
      foreach($students_array as $student) {

        // Attempt to update the student department.
        _amt_dashboard_update_student_department($student, $department_id);
      }
    }
    

    // Redirecting back to the student dashboard page.
    $response = new RedirectResponse($path, 302);
    $response->send();
    return new JsonResponse($response);
  }

  /**
   * Create csv version of the AMI reports.
   * 
   * Some part of the code is copied from https://www.mediacurrent.com/blog/custom-data-file-responses-drupal/
   */
  public function csvExport() {

    $session = \Drupal::request()->getSession();

    // Check that filter data passed by get method.
    if (!empty($session->get('csv_rows'))) {
      
      $header = $session->get('csv_header');
      $rows = $session->get('csv_rows');
      $fileName = $session->get('csv_file_name');

      // Generate the csv file.
      return $this->svs_file_generate($header, $rows, $fileName, 'report');
    }

    // Add message for user.
   drupal_set_message(t('No Data avialble to generate CSV yet.'), 'warning');

   // Redirect back.
   $request = \Drupal::request();
   $response = new RedirectResponse($request->headers->get('referer'));
   $response->send();

  }

  /**
   * Create csv version of the student-list
   */
  public function csvExportStudentList() {

    $session = \Drupal::request()->getSession();

    // Check that filter data passed by get method.
    if (!empty($session->get('csv_rows'))) {
      
      // Create the header mapping for this table.
      $header = [
        $this->t('Student'),
        $this->t('Email'),
        $this->t('Phone'),
        $this->t('Student Department'),
        $this->t('Instructor'),
        $this->t('Paid Ahead'),
        $this->t('Next Lesson Scheduled'),
      ];

      $rows = $session->get('csv_rows');
      $indicator = $session->get('csv_indicator');

      // Generate the csv file.
      return $this->svs_file_generate($header, $rows[0], 'student-list.csv', 'studentList');
    }

    // Add message for user.
    drupal_set_message(t('No Data avialble to generate CSV yet.'), 'warning');

    global $base_url;
    $response = new RedirectResponse($base_url . '/students-list/', 302);
    $response->send();
    return new JsonResponse($response);

  }

  /**
   * Create main csv file.
   */
  public function svs_file_generate($header, $rows, $fileName, $type) {

    //instead of writing down to a file we write to the output stream
    $handle = fopen('php://temp', 'w+');
   
    //form header
    if (sizeof($header[0]) > 0) {
      fputcsv($handle, $header);
    }
   
    //write data in the CSV format
    foreach ($rows as $row) {

      if ($type == 'studentList') {
        // unset unnecessary fields for csv.
        unset($row['department_id']);
        unset($row['link']);
        unset($row['stac_id']);
        unset($row['lastlessondate']);
        unset($row['lessonRemaining']);

        // Fix date field.
        $row['upCommingLesson'] = str_replace("T", " ", $row['upCommingLesson']);
      }
      else if ($type == 'report') {
        // Remove html tags. Sine on the report data some filed like names are rendered as links for navigation.
        if (isset($row['name'])) {
          // On Staff performance report this is a name field.
          $row['name'] = strip_tags($row['name']);
        }
        else if (isset($row['receipt_number'])) {
          // On Reyalty  report this is a student_name field.
          $row['student_name'] = strip_tags($row['student_name']);
        }
      }
      
      fputcsv($handle, array_values($row));
    }
    
    // Reset where we are in the CSV.
    rewind($handle);

    // Reset where we are in the CSV.
    $csv_data = stream_get_contents($handle);

    // Close the file handler since we don't need it anymore.  We are not storing
    // this file anywhere in the filesystem.
    fclose($handle);

    //add necessary headers for browsers
    $response = new Response();
    $response->headers->set('Content-Type', 'text/csv; utf-8');
    $response->headers->set('Content-Disposition', 'attachment; filename="' . basename($fileName) . '"');
    $response->setContent($csv_data);

    return $response;
  }

}
