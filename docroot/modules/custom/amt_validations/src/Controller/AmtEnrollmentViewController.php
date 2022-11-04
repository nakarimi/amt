<?php

namespace Drupal\amt_validations\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * An amt_validations controller.
 */
class AmtEnrollmentViewController extends ControllerBase {

  /**
   * Returns return the Enrollment List.
   *
   * @param int $studentId
   *   ID of Instructor.
   * @param int $isEvent
   *   Shows that the request come from event page or not.
   */
  public function enrollmentlist($studentId, $isEvent, $isPayment) {
    // Call public search function and assign required data
    // that comes from the ajax request.
    $enrollmentList = amt_validations_get_student_enrolment_list($studentId, $isEvent, $isPayment);
    return new JsonResponse($enrollmentList);
  }

  /**
   * Returns a HTML box information from Enrollment Errors.
   *
   * @param int $lessontype
   *   Lessontype Selected.
   * @param int $studentaccountid
   *   Student Account Selected.
   * @param int $enrollmentid
   *   Enrollment ID.
   */
  public function enrollmentcheck($lessontype, $studentaccountid, $enrollmentid) {
    $enrollmentErorrs = amt_validations_check_enrollment($lessontype, $studentaccountid, $enrollmentid);
    $response = new Response(
      $enrollmentErorrs,
      Response::HTTP_OK,
      ['content-type' => 'text/html']
    );
    return $response;
  }

  public function timeslotsCheck() {
    $instructor = (isset($_POST['instructor']))? $_POST['instructor']: '';
    $duration = (isset($_POST['duration']))? $_POST['duration']: '';
    $date = (isset($_POST['date']))? $_POST['date']: '';
    $time = (isset($_POST['time']))? $_POST['time']: '';

    // Filter the event time created and not available for booking events.
    if ($time == 'time') {
      $dataArray = _amt_validations_events_timeslot_conflict($instructor, $duration, $date);
      echo json_encode($dataArray);
    }
    else {
      echo _amt_validations_check_available_time($instructor, $duration, $date, $time);
    }
    die();
  }
  public function studentAccountContact()
  {
    $accountsContacts = _amt_validations_attendance_auto_data($_GET['id']);
    echo $accountsContacts;
    die();
  }
}
