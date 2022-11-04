<?php

namespace Drupal\amt_autocomplete;

use Drupal\Component\Utility\Html;
use Drupal\Component\Utility\Tags;
use Drupal\file\Entity\File;
use Drupal\user\Entity\User;


class EntityAutocompleteMatcher extends \Drupal\Core\Entity\EntityAutocompleteMatcher {

  /**
   * Gets matched labels based on a given search string.
   */
  public function getMatches($target_type, $selection_handler, $selection_settings, $string = '') {
    $matches = [];
    $options = [  
      'target_type'      => $target_type,
      'handler'          => $selection_handler,
      'handler_settings' => $selection_settings,
    ];
    $handler = $this->selectionManager->getInstance($options);

    // Instructor priority has diffrent logic.
    // So here specify that which type of priority should apply.
    $request = \Drupal::request();
    $referer = $request->headers->get('referer');

    // Load only packages that is not retired.
    if ((isset($selection_settings['target_bundles']['package']) && $selection_settings['target_bundles']['package'] == 'package')) {
      $this->loadByPackagesRetiredCondition($matches, $string);
    }

    // Is the request for an secondary instructor list.
    else if (stripos($referer, 'secondary_instructors') != FALSE) {
      $this->loadByPriorityInstructors($matches, 3, $string);      
    }
    // Or it is for a primary instructors list.
    elseif (stripos($referer, 'primary_instructors') != FALSE) {
      $this->loadByPriorityInstructors($matches, 78, $string);
    }
    // End custome instructor based on priority.
    // Default AutoComplete process.
    else if (isset($string)) {
      // Get an array of matching entities.
      $match_operator = !empty($selection_settings['match_operator']) ? $selection_settings['match_operator'] : 'CONTAINS';
      $entity_labels = $handler->getReferenceableEntities($string, $match_operator);

      // Loop through the entities and convert them into autocomplete output.
      $i = 0;
      foreach ($entity_labels as $values) {
        foreach ($values as $entity_id => $label) {

          $entity = \Drupal::entityTypeManager()->getStorage($target_type)->load($entity_id);
          $entity = \Drupal::entityManager()->getTranslationFromContext($entity);


          $type = !empty($entity->type->entity) ? $entity->type->entity->label() : $entity->bundle();
          $status = '';
          if ($entity->getEntityType()->id() == 'node') {
            $status = ($entity->isPublished()) ? ", Published" : ", Unpublished";
          }

          // Check if entity is user, it should be active user, ignore the inactive users.
          elseif ($entity->getEntityType()->id() == 'user') {
            $i += 1;
            if($entity->get('status')->value == 0){
              continue;
            }else if($i > 5){
              break;
            }
          }

          $key = $label . ' (' . $entity_id . ')';
          
          // Strip things like starting/trailing white spaces, line breaks and tags.
          $key = preg_replace('/\s\s+/', ' ', str_replace("\n", '', trim(Html::decodeEntities(strip_tags($key)))));
          // Names containing commas or quotes must be wrapped in quotes.
          $key = Tags::encode($key);
          
          // This is the part that we alter the output if
          // the autocomplete is for student account.
          if ($target_type == "student_accounts") {
            // Defining an array to save contacts array in.
            $contactsArray = [];
            foreach($entity->field_contacts as $contact) {
              $contactsArray[] = $contact->entity;
            }
            // Saving the student account title.
            // Later added to the end of the contacts list in autocomplete.
            $studentAccountTitle = $label;
            // Resetting the label.
            $label = '';
            $label .= "<div class='eachAccuntContacts'>";
            // Loop through each contact for every student account.
            foreach ($contactsArray as $contact) {
              $imgUrl = "";
              // Check if the student has image.
              if ($contact->field_student_image) {
                // Get the img id from contact.
                $imgId = $contact->field_student_image->target_id;
                // Load the image.
                $img = File::load($imgId);
                // generate the URL for the image.
                if ($img != NULL) {
                  // get the url of the image.
                  $imgUrl = $img->url();
                }
                else {
                  // Default image if the contact does not have a photo.
                  $imgUrl = "/sites/default/files/defaultContactImage.png";
                }
              }
              // If contacts does not have a photo.
              else {
                // Default image if the contact does not have a photo.
                $imgUrl = "/sites/default/files/defaultContactImage.png";
              }
              // Attaching contacts info like image and name
              // to the final result.
              $label .= "
              <span>
                <span>
                  <img src='" . $imgUrl . "' class='student-account-image' alt='NO IMAGE' width='50px' height='50px'>
                </span>
                <span>
                  <span>" . $contact->field_first_name->value . ' ' . $contact->field_last_name->value . "</span>
                </span>
              </span>";
            }
            // Adding the student account title at the end of each autocomplete row.
            $label .= ' <span style="font-weight: bold;">(' . $studentAccountTitle . ')</span>' . "</div>";
          }
          else {
            $label = '<span>' . $label .'</span>';
          }
          // Setting the keys and label to a variable to be passed.
          $matches[] = ['value' => $key, 'label' => $label];
        }
      }
    }
    return $matches;
  }

  /**
   * Instructor that appears in priority autocomplete result
   * need to seperate by teacher category and as well if they
   * selected befere don't add them again.
   *
   * @param array &$matches
   *   A reference value that will store the matches records.
   * @param int $category
   *   The id of primary or secondary insturctor terms.
   * @param string $string
   *   The search key words that user needed.
   */
  public function loadByPriorityInstructors(&$matches, $category, $string) {
    $matches = [];
    $client = \Drupal::httpClient();
    // Set base path.
    global $base_url;
    // Set Url.
    if ($category == 78) {
      // Call view rest to get instructor json data.
      $request = $client->get($base_url . '/primary-instructors-api?_format=json', ['http_errors' => FALSE]);
    }
    else if ($category == 3) {
      // Call view rest to get instructor json data.
      $request = $client->get($base_url . '/secondary-instructors-api?_format=json', ['http_errors' => FALSE]);
    }

    $instructorReservedId = [];
    foreach (json_decode($request->getBody()) as $value) {
      $instructorReservedId[] = $value->uid[0]->value;
    }

      // Load all instructors based on category.
      $instructorQuery = \Drupal::entityQuery('user')
        ->condition('roles', 'instructor')
        ->condition('field_teacher_category', $category);
      if ($instructorReservedId != NULL) {
          $instructorQuery->condition('uid', $instructorReservedId, 'NOT IN');
      }
      // Additional condition to search by first and last name.
      $searchTerm = $instructorQuery->orConditionGroup()
        ->condition('field_first_name', '%' . $string . '%', 'LIKE')
        ->condition('field_last_name', '%' . $string . '%', 'LIKE');

      $instructorsId = $instructorQuery
        ->condition($searchTerm)
        ->sort('field_first_name')
        ->sort('field_last_name')
        ->range(0, 5)
        ->execute();

      // Provide data for the autocomplete list.
      foreach (User::loadMultiple($instructorsId) as $user) {

        // Adding the student account title at the end of each autocomplete row.
        $label = $user->field_first_name->value . ' ' . $user->field_last_name->value;

        // Setting the keys and label to a variable to be passed.
        $matches[] = [
          'value' => $label . ' (' . $user->id() . ')',
          'label' => $label,
        ];
      }
  }

  /**
   * Only load packages that are not retired.
   *
   * @param array &$matches
   *   A reference value that will store the matches records.
   * @param string $string
   *   The search key words that user needed.
   */
  public function loadByPackagesRetiredCondition(&$matches, $string) {

    // Load all retired packages and ignore them from final list.
    $not_ids = \Drupal::entityQuery('packages')
      ->condition('type', 'package')
      ->condition('field_retired', 1, "=")
      ->execute();

    $query = \Drupal::entityQuery('packages')
      ->condition('type', 'package')
      ->condition('title', '%' . $string . '%', 'LIKE')
      ->sort('title')
      ->range(0, 5);

    if ($not_ids) {
      $query->condition('id', array_keys($not_ids), "NOT IN");
    }
    $ids = $query->execute();
    $packages = \Drupal::entityManager()->getStorage('packages')->loadMultiple($ids);
    // Provide data for the autocomplete list.
    foreach ($packages as $package) {
      $label = $package->title->getString();

      // Setting the keys and label to a variable to be passed.
      $matches[] = [
        'value' => $label . ' (' . $package->id() . ')',
        'label' => $label,
      ];
    }
  }
  
}
