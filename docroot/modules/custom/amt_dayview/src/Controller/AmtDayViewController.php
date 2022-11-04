<?php

namespace Drupal\amt_dayview\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Component\Serialization\Json;
use Drupal\taxonomy\Entity\Term;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\Core\Url;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\RedirectCommand;
use Drupal\Core\Ajax\AlertCommand;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\RedirectResponse;


/**
 * An amt_dayview controller.
 */
class AmtDayViewController extends ControllerBase {

  /**
   * Base function to load the view and its data for the route.
   *
   * @return array
   *   Return the view callback function and attachments.
   */
  public function basePageCallback() {
    return [
      '#theme' => 'amt_dayview_calendar',
      '#teacherCategory' => amt_dayview_teacher_category(),
      '#teachersList' => _amt_validation_replace_username_with_fullname_in_form('instructor'),
      '#lessonTypes' => _amt_dayview_load_all_lesson_types(),
      '#lesson_status_list'=> _amt_dayview_load_statuses('lesson_status'),
      '#service_status_list'=> _amt_dayview_load_statuses('service_status'),
      '#pageType' => 'day',
      '#userRole'  => User::load(\Drupal::currentUser()->id())->hasRole('instructor'),
      '#attached' => [
        'library' => ['amt_dayview/amt_scripts', 'amt_dayview/amt_dayview_script'],
      ],
    ];
  }

  /**
   * Base function to load the view and its data for the route.
   *
   * @return array
   *   Return the view callback function and attachments.
   */
  public function weekViewCallback() {  
    return [
      '#theme' => 'amt_dayview_calendar',
      '#teachersList' => _amt_validation_replace_username_with_fullname_in_form('instructor'),
      '#lessonTypes' => _amt_dayview_load_all_lesson_types(),
      '#pageType' => 'week',
      '#userRole'  => User::load(\Drupal::currentUser()->id())->hasRole('instructor'),
      '#attached' => [
        'library' => ['amt_dayview/amt_week_scripts'],
      ],
    ];
  }

  /**
   * Returns a render-able array for dayview page.
   */
  public function callbackAjaxLoadDayView() {
    // Get the start date from the reqest that is generted
    // by fullcalendar and change on change date.
    $startDate = explode("T", $_GET['start'])[0];
    $endDate = explode("T", $_GET['end'])[0];
    
    // Call the function to get information for
    // dayveiw according to the date that should
    // be set in the ajax request.
    $data = amt_dayview_load_events($startDate, $endDate);
    return new JsonResponse($data);
  }

  public function callbackAjaxDeleteEvent() {
      $event = \Drupal::entityTypeManager()->getStorage('events')->load($_POST['id']);
      if ($event != NULL) {
        $event->delete();
        return new JsonResponse("Done!");
      }else{
        return new JsonResponse('Failed!');
      }
  }

  /**
   * Returns a instractor ids as resources.
   */
  public function callbackAjaxLoadResources() {
    // Get the start date from the reqest that is generted
    // by fullcalendar and change on change date.
    $startDate = $_GET['start'];
    // Get instructor information for dayveiw resource
    // according to the date that should be set
    // in the ajax request and will use an culomn title.
    $data = amt_dayview_load_events_instructor($startDate);
    // var_dump(new JsonResponse($data));
    if (count($data) > 0) {
      return new JsonResponse($data);
    }
    else {
      // If the data not exist means there is no instractor
      // then it will show calendar without column, but it take the previouse
      // in the next empty data, here we need to check if instructor not
      // exist for selected date, then pass empty array that will fix issue
      // with old column name but if data exist pass the real array of data.
      return new JsonResponse([]);
    }
  }

  /**
   * Returns the table of Appontment Detials.
   */
  public function callbackAjaxPopup() {
    if (isset($_GET['type']) && $_GET['type'] == "delete_event") {
      $eventEntity = \Drupal::entityTypeManager()->getStorage('events')->load($_GET['id']);
      $entityTitle = $eventEntity->title->value;
      $eventEntity->delete();
      return new JsonResponse("'title': " . $entityTitle);
    }
    else{
      return new JsonResponse(_amt_dayview_popup_details($_GET['id']));
    }
  }

  /**
   * Return the Config data for dayview setting.
   */
  public function callbackAjaxConfiguration() {
    $data = _amt_dayview_full_calendar_settings();
    $data['times'] = _amt_dayview_full_calendar_start_session_settings();
    return new JsonResponse(Json::encode($data));
  }

  /**
   * Return the Config data for dayview session start time.
   */
  public function callbackAjaxConfigurationSessionStart() {
    $data = _amt_dayview_full_calendar_start_session_settings();
    $data = json_encode($data);
    return new JsonResponse($data);
  }

  /**
   * Returns the table of Appontment Detials.
   */
  public function callbackAjaxAutocomplete() {
    // Call public search function and assign required data
    // that comes from the ajax request.
    // Multiple bundle should be seperated by comma then it will explod it.

    $baseData = amt_dayview_autocomplete($_POST['eckType'], $_POST['term'], $_POST['bundle'], $_POST['field'], $_POST['excludeTeachers']);
    return new JsonResponse($baseData);
  }

  /**
   * This function is used to update the status of attendees from dayview popup.
   */
  public function updateStatus() {
    $result = _amt_dayview_update_attendees_status();
    if (!empty($result)) {
      $result = _amt_dayview_extract_failed_status_update($result);
      if (empty($result)) {
        return new JsonResponse('success');
      }
      else {
        return new JsonResponse($result);
      }
    }

    return new JsonResponse('Failed to update for unknown reason');
    
  }

  public function updateMultipleStatus() {
    $result = _amt_dayview_update_multiple_status();

    if (empty($result)) {
      return new JsonResponse('success');
    }
    else {
      return new JsonResponse($result);
    }
  }

  /**
   * This function is used to Cancel future appointments
   */
  public function cancelFutureStandingAppts() {
    
    // Store response during the process.
    $response = -1;

    // Get passed data.
    $request = \Drupal::request()->request;
    $eventID = $request->get('eventID');
    $parentID = $request->get('parentID');

    // If provided parent id is not zero, then that is parent, if it was 0, then the event itself is parent.
    $targetEntity = ($parentID != 0) ? $parentID : $eventID;

    // Delete future events.
    amt_validations_deletefutureevents($targetEntity, TRUE, $eventID);
    
    return new JsonResponse($response);
  }

    /**
   * This function is called from day-view when user close the service create popup manually from close button.
   *
   * @return array
   *   Return the view callback function and attachments.
   */
  public function unsetSessions() {
    // Set base path.
    global $base_url;

    $params = "date=";

    // Add more params for auto fill of service.
    unset($_SESSION['_sf2_attributes']['executive']);
    unset($_SESSION['_sf2_attributes']['student_account']);
    unset($_SESSION['_sf2_attributes']['student']);
    unset($_SESSION['_sf2_attributes']['lesson_date']);

    // Set Url.
    $response = new RedirectResponse($base_url . "/day-view?" . $params);
    $response->send();
  }

}
