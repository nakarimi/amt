<?php

namespace Drupal\amt_general\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Drupal\entityqueue\Entity\EntitySubqueue;

/**
 * Provides route responses for the Example module.
 */
class amtGeneralController extends ControllerBase {

  /**
   * A function for updating intructors sort on entityqueue(these are terms of entityqueue)
   */
  public function insturctorSortUpdate() {

    $queueMachineName = ($_POST['queuetype'] == 'primari') ? 'primary_instructors' : 'secondary_instructors';
    
    // load the entityqueue
    $entity_subqueue = EntitySubqueue::load($queueMachineName);
    $items = [];

    // Store all ids as items.
    for ($i=0; $i < sizeof($_POST['ids']); $i++) { 
      if ((int)$_POST['ids'][$i] > 0) {
        $items[] = ['target_id' => (int)$_POST['ids'][$i]];
      }
    }
    
    // Restore itmes for queue entity in the new order. 
    $entity_subqueue->set('items', $items);
    // Save.
    $entity_subqueue->save();
    
    return new JsonResponse($resp);
  }

  public function getLessonTypeLength()
  {
    $field = (strpos($_GET['type'], 'Dance Evaluation') !== false) ? 'field_dance_evaluation' : 'field_pre_originals_length';
    $data = _amt_general_get_event_length($field);
    return new JsonResponse($data);  
  }

  /**
   * Callback function for updating an event.
   */
  public function event_update() {

    $result = '';
    // This is for updating service event status.
    // This is different than other lesson status, since they work based on attendance status unlike services
    // that works based on their own status.
    if (isset($_POST['action']) && $_POST['action'] == "serviceStatusUpdate") {
      $eventId = $_POST['eventId'] ?? 'undefined';
      $eventStatus = $_POST['eventStatus'] ?? 'undefined';

      if ($eventId != 'undefined' && $rsDate != 'undefined' && $rsTime != 'undefined') {
  
        $event = \Drupal::entityTypeManager()->getStorage('events')->load($eventId);

        if ($event->bundle() == 'services') {
          $event->set('field_status', $eventStatus);
          $result = $event->save();
        }
        else {
          $result = 'event_not_supported';
        }
      }
      else {
        $result = 'incomplete_values';
      }
    }
    else {

      // reschedule ajax request from Cancel/RS popup. 
      // The  duplicates the event for the new schedule date and sets the reschedule date for the event.
      $eventId = $_POST['eventId'] ?? 'undefined';
      $rsDate = $_POST['rsDate'] ?? 'undefined';
      $rsTime = $_POST['rsTime'] ?? 'undefined';
  
      if ($eventId != 'undefined' && $rsDate != 'undefined' && $rsTime != 'undefined') {
  
        $event = \Drupal::entityTypeManager()->getStorage('events')->load($eventId);
        $entityBundle = $event->bundle();
        if ($entityBundle == 'lesson' || $entityBundle == 'group_lesson') {
          
          // Format the schedule date and time to the way drupal accepts for storing in the DB.
          $newScheduleDate = date_create($_POST['rsDate'] . ' ' . $_POST['rsTime'])->format('Y-m-d\TH:i:s');
          
          // Duplicate the event for new schedule
          
          $newEvent = $event->createDuplicate();
          
          // Set the event's reschedule date to display it on the dayview.
          $event->field_reschedule_date->value = $newScheduleDate;
          $event->save();
          
          $newEvent->field_date_and_time->value = $newScheduleDate;
          
          // Get the default value of attendee's status field and set it to new event's attendeeS' status.
          $attendees = $entityBundle == 'lesson' ? $event->field_student : $event->field_students;
          $attendees = $attendees->referencedEntities();
          $defaultStatusId = \Drupal::service('entity_field.manager')->getFieldDefinitions('attendees', 'attendance')['field_status']->getDefaultValue($attendees[0])[0]['target_id'];
  
          $attendeIds = [];
          foreach($attendees as $attendee) {
            $newAttendee = $attendee->createDuplicate();
            $newAttendee->field_status->target_id = $defaultStatusId;
            $newAttendee->save();
            
            $attendeIds[] = ['target_id' => $newAttendee->id()];
          }
          $newEvent->field_student = $attendeIds;
          // Save() result can be 1(means saved as new entity), 2(means updated current entity)
          // or error exception that means failed to save.
          $result = $newEvent->save();
        }
        else {
          $result = 'event_not_supported';
        }
      }
      else {
        $result = 'incomplete_values';
      }
    }
    
    return new JsonResponse($result);
  }
}
