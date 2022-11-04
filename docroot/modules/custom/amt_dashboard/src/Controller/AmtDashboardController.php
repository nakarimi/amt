<?php

namespace Drupal\amt_dashboard\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * An amt_dayview controller.
 */
class AmtDashboardController extends ControllerBase
{

  /**
   * Loading filtered data for enrollment.
   *
   * This function returns an array of filtered data
   * and sends them back to the studetn dashboard view.
   */
  public function ajaxLoadFilterEnrollment()
  {
    // Calling the main function to query and filter the data.
    $filteredData = _amt_dashboard_filter_enrollment();
    if (empty($filteredData)) {
      $filteredData = [];
    }
    return new JsonResponse($filteredData);
  }

  /**
   * Loading filtered data for enrollment.
   *
   * This function Loads the autocoplete data for
   * for enrollment filters.
   */
  public function ajaxLoadAutoComplete()
  {
    // Call public search function and assign required data
    // that comes from the ajax request.
    // Multiple bundle should be seperated by comma then it will explod it.
    $baseData = amt_dayview_autocomplete($_POST['eckType'], $_POST['term'], $_POST['bundle'], $_POST['field']);
    return new JsonResponse($baseData);
  }

  /**
   * Loading the Student Dashboard page.
   */
  public function loadStudentDashboard()
  {

    if (isset($_GET['id']) || isset($_GET['nid'])) {

      // On the enrollment the link entity module passes account id as nid param, so we need to covert it to staudent id for to get data for dashboard.
      if (isset($_GET['nid'])) {
        $studenAccounttId = $_GET['nid'];
        $studentEntity =  \Drupal::entityTypeManager()->getStorage('student_accounts')->load($studenAccounttId);

        if (!is_null($studentEntity->field_contacts)) {
          // Set id to Get param, since other related functions like amt_dashboard_load_data_for_student_dashboard again get this param indirectly.
          $_GET['id'] = $studentEntity->field_contacts->getValue()[0]['target_id'];
        } else {
          // if id was not available.
          return [
            '#theme'   => 'amt_student_dashboard',
            '#message' => "You should select student!",
          ];
        }
      }

      $studentId = $_GET['id'];

      $basicInfo = $this->get_student_info($studentId);
      // Loading the displaying data for the page.
      $dataArray = amt_dashboard_load_data_for_student_dashboard();
      // Creating table for enrollment list.
      $dataArray['enrollment'] = _create_table_for_enrollments((!empty($dataArray['enrollment'])) ? $dataArray['enrollment'] : NULL);
      // Creating table for student's inquiry data.
      $dataArray['inquiry'] = _create_table_for_inquiry((!empty($dataArray['inquiry'])) ? $dataArray['inquiry'] : NULL);
      // Creating table for Student's basic info.
      $dataArray['studentBasicInfo'] = _create_table_for_student_basic_info((!empty($dataArray['studentBasicInfo'])) ? $dataArray['studentBasicInfo'] : NULL);

      // Creating table for lesson list.
      $dataArray['student'] = _create_table_for_lessons_list((!empty($dataArray['student'])) ? $dataArray['student'] : NULL);
      $dataArray['studentServices'] = _create_table_for_services_list((!empty($dataArray['studentServices'])) ? $dataArray['studentServices'] : NULL);

      // Return the total number of lesson available and
      // lesson used for current student.
      $lessonAvailableAndUsed = amt_dashboard_total_lessons_paid_ahead_taken();
      // Load Student total lessons enrolled.
      $dataArray['totalLessonEnrolled'] = amt_dashboard_load_student_total_lessons_enrolled($studentId);
      // Load Student Total Lessons Taken.
      $dataArray['totalLessonsTaken'] = amt_dashboard_student_total_lessons_taken($studentId);

      $currentDate = date('Y-m-d', time());
      $unPostedLessonscounter = 0;
      if (!empty($dataArray['student'])) {
        foreach ($dataArray['student']['#rows'] as $row) {
          $date = explode('-', $row[0]['data']);
          $status = (is_array($row[5]) ? $row[5]['data'] : $row[5]);

          // reverse the date string to reformat.
          $date = $date[2] . "/" . $date[0] . "/" . $date[1];

          // Make a date object, to be able to compare.
          $date = date("Y-m-d", strtotime($date));

          // This will be apply to passed lessons only.
          $isEventOld = strtotime($date) < strtotime($currentDate);

          if ($isEventOld && $status == 'Pending Status') {
            $unPostedLessonscounter++;
          }
        }
      }

      $currentDate = date('Y-m-d', time());
      $unPostedServicescounter = 0;
      if (!empty($dataArray['studentServices'])) {
        foreach ($dataArray['studentServices']['#rows'] as $row) {
          $date = explode('-', $row[0]['data']);
          $status = (is_array($row[5]) ? $row[5]['data'] : $row[5]);

          // reverse the date string to reformat.
          $date = $date[2] . "/" . $date[0] . "/" . $date[1];

          // Make a date object, to be able to compare.
          $date = date("Y-m-d", strtotime($date));

          // This will be apply to passed lessons only.
          $isEventOld = strtotime($date) < strtotime($currentDate);

          if ($isEventOld && $status == 'Pending Status') {
            $unPostedServicescounter++;
          }
        }
      }

      // Get past lessons without a status (due to no lessons paid ahead).
      $dataArray['totalLessonsPassedUnposted'] = $unPostedLessonscounter;
      return [
        '#theme'            => 'amt_student_dashboard',
        '#studentBasicInfo' => $dataArray['studentBasicInfo'],
        '#inquiry'          => $dataArray['inquiry'],
        '#enrollment'       => $dataArray['enrollment'],
        '#student'          => $dataArray['student'],
        '#studentServices'          => $dataArray['studentServices'],
        // Remove the user id from the name
        '#studentName' => explode(' - ', $basicInfo['fullName'])[0],
        '#studentId'        => $studentId,
        '#studentDepartment'     => $dataArray['student_department'],
        '#totalLessonsEnrolled'  => $dataArray['totalLessonEnrolled'],
        '#totalFutureLessons'    => $dataArray['futureLessonsCount'],
        '#totalLessonsPaidAhead' => $lessonAvailableAndUsed['paid_ahead'],
        '#totalLessonsPassedUnposted' => $dataArray['totalLessonsPassedUnposted'],
        '#totalLessonsTaken'     => $dataArray['totalLessonsTaken'],
        '#totalLessonsRemaining'     => $dataArray['totalLessonsRemaining'],
        '#studentLessonsStatus'     => $dataArray['lesson'],
        '#studentServicesStatus'     => $dataArray['services'],
        '#studentGLessonsStatus'     => $dataArray['group_lesson'],
        '#lesson_status_list'  => $dataArray['lesson_status_list'],
        '#service_status_list'  => $dataArray['service_status_list'],
        '#attached' => [
          'library' => [
            'amt_dashboard/amt_scripts',
            'amt_dashboard/amt_filters',
            'amt_dashboard/amt_datatable',
            'amt_dashboard/amt_events_print',
            'amt_dayview/amt_sort_date'
          ],
        ],
      ];
    } else {
      return [
        '#theme'   => 'amt_student_dashboard',
        '#message' => "You should select student!",
      ];
    }
  }

  /**
   * Adjusting starting balance of paid ahead for students.
   */
  public function adjustStudentBalance()
  {

    $loop_index = 0;
    $balance = $_POST['balance'];
    $studentId = $_POST['id'];
    $path = \Drupal::request()->getSchemeAndHttpHost() . '/student-dashboard?id=' . $studentId;
    $repeat = false;

    // For Logging purpose. START
    $checkOldBalance = amt_dashboard_total_lessons_paid_ahead_taken($studentId);
    $oldPA = $checkOldBalance['paid_ahead'];
    // For Logging purpose. END

    do {

      // Get current balance.
      $loop_index++;
      $checkCurrentBalance = amt_dashboard_total_lessons_paid_ahead_taken($studentId);
      $currentPA = $checkCurrentBalance['paid_ahead'];
      $basicInfo = $this->get_student_info($studentId);

      // Check if we need to increase or decrease PA.
      if ($balance > $currentPA) {
        // This means we need to increase PA.

        // For increasing paid ahead we need to add an enrollment of type comp lesson in current date with lesson count of the given values), with defult ui info.
        $this->create_enrollment($basicInfo['studentId'], $basicInfo['legacyId'], ($balance - $currentPA));
      } else if ($balance != $currentPA) {
        // This means we need to decrease PA.

        // Loading the enrollments for this student.
        $enrollments = amt_dashboard_load_student_enrollments($studentId, $filterZeroCount = false, $moreFields = true);

        $removeBalance = $currentPA - $balance;
        // Filter those enrollment data that is needed.

        $data = [];
        foreach ($enrollments as $key => $val) {
          // ignore enrollment with lesson count 0 or with sundry or undefined category.
          if (!($val['lessonAvailable'] < 1 || ($val['category'] == 'Sundry' || $val['category'] == 'Uncategorized'))) {
            $data[$key] = $val['lessonAvailable'];
          }
        }

        // Check if there is an enrollment with this count.
        $found_index = array_keys($data)[0];

        if (!$found_index === false) {
          $field_lesson_used = 0;
          $field_lesson_available = 0;

          // if found remove this enrollment.
          if ($removeBalance <= $data[$found_index]) {

            // if we can decrease the amount by one time.
            $field_lesson_used = ($enrollments[$found_index]['lessonUsed'] + $removeBalance);
            $field_lesson_available = ($enrollments[$found_index]['lessonAvailable'] - $removeBalance);
            $repeat = false;
          } else {

            // if we need to decrease the amount by multiple time.
            $field_lesson_used = ($enrollments[$found_index]['lessonUsed'] + $enrollments[$found_index]['lessonAvailable']);
            $removeBalance -= $enrollments[$found_index]['lessonAvailable'];
            $field_lesson_available = 0;
            $repeat = true;
          }

          $this->update_enrollment($found_index, $field_lesson_used, $field_lesson_available);
        }
      }
    } while ($repeat == true && $loop_index < 8);

    // For Logging purpose. START
    $checkCurrentBalance = amt_dashboard_total_lessons_paid_ahead_taken($studentId);
    $currentPA = $checkCurrentBalance['paid_ahead'];

    $message = "Old Balance $oldPA ** Entered Balance $balance ** Studnet ID $studentId ** Final Balance $currentPA";
    \Drupal::logger('Manual Balance Set')->info($message);
    // For Logging purpose. END

    $response = new RedirectResponse($path, 302);
    $response->send();
    return new JsonResponse($response);
  }

  /**
   * Creating a new enrollment.
   *
   * We create a new enrollment for increasing paid ahead count..
   *
   * @param string $studentId
   *   The student id for creating enrollment for them.
   * @param string $legacyId
   *   The enrollment legacy id.
   *
   * @return object
   *   An object of the enrollment that we created.
   */
  public function create_enrollment($studentId, $legacyId, $lessonCount)
  {

    // Get definition of target entity type.
    $entityDefinition = \Drupal::entityManager()->getDefinition("packages");

    // Load up an array for creation.
    $entityData = [
      'title' => $legacyId . ' - PA Enrollment',
      'field_student' => ['target_id' => $studentId],
      'field_legacy_id' => ['value' => $legacyId],
      'field_category' => ['target_id' => _amt_feeds_find_single_eck_id('enrollment_type', 'Bonus/Comp Lesson')],
      'field_sale_date' => date('Y-m-d'),
      'field_enrollment_lesson_count' => $lessonCount,

      // Use entity definition to set the appropriate property for the bundle.
      $entityDefinition->getKeys()['bundle']  => "enrollment",
    ];

    // Create new entity by the data array that provided.
    $newEntity = \Drupal::entityManager()
      ->getStorage("packages")
      ->create($entityData);
    $result = $newEntity->save();

    return $newEntity;
  }

  public function update_enrollment($found_index, $field_lesson_used, $field_lesson_available)
  {

    $eventEntity = \Drupal::entityTypeManager()->getStorage('packages')->load($found_index);
    $eventEntity->set('field_lesson_used', $field_lesson_used);
    $eventEntity->set('field_lesson_available', $field_lesson_available);
    $result = $eventEntity->save();

    return $result;
  }

  /**
   * Loading Basic information for student
   *
   * @param string $studentId
   *   The student id for creating enrollment for them.
   *
   * @return array
   *   An array containing student account id and legacy id.
   */
  public function get_student_info($studentId)
  {
    $studentAccounts = \Drupal::entityQuery('student_accounts')->condition('field_contacts', $studentId)->execute();

    $accoutnID = array_keys($studentAccounts);

    $studentEntity = \Drupal::entityTypeManager()->getStorage('student_accounts')->loadMultiple($accoutnID);

    $data = [];
    foreach ($studentEntity as $key => $entity) {
      $data['legacyId'] = $entity->field_legacy_id->value;
      $data['studentId'] = $entity->id->value;
      $data['fullName'] = $entity->title->value;
    }
    return $data;
  }

  /**
   * Handling the update of student department.
   * 
   * This function is used to update the student
   * department for the current student whose
   * data being displayed in the student dashboard
   * page.
   */
  public function updateStudentDepartment()
  {

    // Getting the current student (contact) ID.
    $studentId = $_POST['id'];

    // Getting the new chosen deparment's id.
    $department_id = $_POST['student-department'];

    // Path for redirection.
    $path = \Drupal::request()->getSchemeAndHttpHost() . '/student-dashboard?id=' . $studentId;

    // Updating the department ID for the student.
    $result = _amt_dashboard_update_student_department_based_on_contact($studentId, $department_id);


    // Redirecting back to the student dashboard page.
    $response = new RedirectResponse($path, 302);
    $response->send();
    return new JsonResponse($response);
  }

  /**
   * Toggle the enrollment between hide and show.
   *
   */
  public function ajaxHideEnrollment()
  {

    // Calling the main function to query and filter the data.
    $enrollment_id = $_POST['enrollment_id'];

    // Update enrollment to opposite current value of the filed, since it is boolean.
    $packageEntity = \Drupal::entityManager()->getStorage('packages')->load($enrollment_id);
    $packageEntity->field_visibility->value = !$packageEntity->field_visibility->value;
    $packageEntity->save();

    return new JsonResponse('done');
  }
}
