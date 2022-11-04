<?php

namespace Drupal\amt_feeds\EventSubscriber;

use Drupal\feeds\Event\FeedsEvents;
use Drupal\feeds\Event\ParseEvent;
use Drupal\feeds\Feeds\Item\ItemInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\amt_general\LogMessage;
use Drupal\feeds\Event\ImportFinishedEvent;

/**
 * Class AmtFeedsEventsSubscriber.
 *
 * @package Drupal\amt_feeds\EventSubscriber
 */
class AmtFeedsEventsSubscriber implements EventSubscriberInterface {

  /**
   * ParseEvent The event being reacted to.
   *
   * @var object
   */
  public $event;

  /**
   * The feeds type ID for this feed.
   *
   * @var string
   */
  public $feedType;

  /**
   * The number of Items skiped when feeds import.
   *
   * @var int
   */
  public $countSkipType = 0;

  /**
   * The number of Items skiped when feeds import.
   *
   * @var int
   */
  public $lessonTypeSkiped = '';

  /**
   * The number of Group lesson when feeds import.
   *
   * @var int
   */
  public $groupLessonSkipped = 0;

  /**
   * This Is An Invalid value for Feed, we use it to Skip the current Item.
   *
   * @var int
   */
  const SKIPEVALUE = 44;

  /**
   * Constructor.
   */
  public function __construct() {
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events = [];
    $events[FeedsEvents::PARSE][] = ['afterParse', FeedsEvents::AFTER];
    // $events[FeedsEvents::IMPORT_FINISHED][] = ['afterFinish', FeedsEvents::AFTER];
    return $events;
  }

  /**
   * Modify events after existing parser.
   *
   * @param \Drupal\feeds\Event\ParseEvent $event
   *   The parse event.
   */
  public function afterParse(ParseEvent $event) {

    // Store the event and grab the feed type.
    $this->event = $event;
    $this->feedType = $event->getFeed()->getType()->id();

    // Get the result of feed item parser.
    $result = $event->getParserResult();
    $itemCount = $result->count();
    $callbacks = $this->getCallbackMapping();

    for ($i = 0; $i < $itemCount; $i++) {
      if (!$result->offsetExists($i)) {
        break;
      }

      // Get the item from event and do process.
      $item = $result->offsetGet($i);
      if (!empty($callbacks)) {
        foreach ($callbacks as $callback) {
          $this->{$callback}($item);
        }
      }
    }

    if ($this->countSkipType) {
      LogMessage::showMessage($this->countSkipType . ' items not imported, ' . $this->feedType . ' type not matched!', 'warning', TRUE);
      LogMessage::showMessage('<ul>' . $this->lessonTypeSkiped . '</ul>', 'warning', TRUE);
    }
    if ($this->groupLessonSkipped) {
      LogMessage::showMessage($this->groupLessonSkipped . ' items detected as group lesson!', 'warning', TRUE);
    }
  }

  /**
   * Convert idance inqmethod text to new system text.
   *
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to make modifications on.
   */
  protected function inquiryMethodConvert(ItemInterface $item) {
    // Get the inquiry method conversion mapping.
    $inquiryMethodEquivalents = $this->getInquiryMethodMapping();

    // Get the legacy method and set the default current method.
    $inqmethod = $item->get('inqmethod');
    $convertedMethod = '';

    // Check the legacy method against each section of mappings.
    foreach ($inquiryMethodEquivalents as $conversion => $legacyVersions) {
      // If the legacy method is found in a mapping, then use the equivalent.
      if (in_array($inqmethod, $legacyVersions)) {
        $convertedMethod = $conversion;
      }
    }

    // Actually set the converted method.
    $item->set('inqmethod', $convertedMethod);
  }

  /**
   * Alters a single item Packages items.
   *
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to make modifications on.
   */
  protected function alterPackagesItem(ItemInterface $item) {
    $item->set('title', $item->get('code'));

    $ren = ["REN1", "TRAN-OUT", "REN", "TRAN-IN", "tte", "G", "IVE"];
    $pori = ["PO", "RI", "GIFT", "RECEIVE", "BONUS", "PORI1", "PORI", "GIVE"];
    $ori = ["ORI1", "ORI"];
    $ext = ["EXT", "EXT1", "PRIVATE", "REXT"];
    $misc = ["SPONSOR", "OUTSIDE", "CD", "CLASS",
      "CORE", "DOR/SANCT.", "PARTYTIME", "SHOWCASE",
    ];
    $sundry = ["SHOE", "OLDSUN", "sun"];
    $ptr = ["PTR"];
    $misc_07 = ["SHOWCASE"];
    $misc_14 = ["SPONSOR", "OUTSIDE", "CD", "CLASS",
      "CORE", "DOR/SANCT.", "PARTYTIME",
    ];
    // Get the specific data from uploaded file.
    $salesCode = $item->get('salecode');
    $sps = "";
    $packageCode = "";
    // Check if the value match the array values then it will convert
    // it to specific value.
    if (in_array($salesCode, $ren)) {
      $sps = "REN";
    }
    elseif (in_array($salesCode, $pori)) {
      $sps = "PORI";
    }
    elseif (in_array($salesCode, $ori)) {
      $sps = "ORI";
    }
    elseif (in_array($salesCode, $ext)) {
      $sps = "EXT";
    }
    elseif (in_array($salesCode, $misc)) {
      $sps = "MISC";
    }
    elseif (in_array($salesCode, $sundry)) {
      $sps = "SUNDRY";
    }
    if (in_array($salesCode, $misc_07)) {
      $packageCode = "MISC_07";
    }
    elseif (in_array($salesCode, $misc_14)) {
      $packageCode = "MISC_14";
    }
    // Set the "salecode" to converted value.
    $item->set('salecode', $sps);
    // Set the "packagecode" to converted value.
    $item->set('packagecode', $packageCode);
  }

  /**
   * Convert the service type.
   *
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to make modifications on.
   */
  protected function serviceTypeMethodConverter(ItemInterface $item) {
    $service = $item->get('service');

    // Default to not Found.
    $serviceConverted = "Not Found!";
    $serviceMapping = $this->getServiceMapping();

    // Check the mapping against the imported value. If the imported service is
    // matched, then provide the new value.
    foreach ($serviceMapping as $newValue => $originalValues) {

      if (is_array($originalValues) && in_array($service, $originalValues)) {
        $serviceConverted = $newValue;
        break;
      }
    }

    $item->set('service', $serviceConverted);
  }

  /**
   * Convert idance lesson type text to new system lesson type.
   *
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to make modifications on.
   */
  protected function lessonTypeMethodConvert(ItemInterface $item) {
    $service = $item->get('service');
    $convertedService = "";

    // Provide and associative array contains the lesson types.
    $lessonTypes = [
      "BPRI"           => "Front Department Lesson (FD)",
      "PRI"            => "Front Department Lesson (FD)",
      "SOLO"           => "Front Department Lesson (FD)",
      "Black Tie"      => "Front Department Lesson (FD)",
      "GAP"            => "Front Department Lesson (FD)",
      // "COACH"          => "Coach Lesson (C)",
      // "Coaching"       => "Coaching Lesson",  // Coaching lesson is now considered as a service.
      "BDYLSN"         => "Back Dept Lesson (BD)",
      "Buddy Less"     => "Back Dept Lesson (BD)",
      "MCLS"           => "Master Class",
      "Party Sess"     => "Practice Party",
      "Party Session"  => "Practice Party",
      "FPRI"           => "Front Department Lesson (FD)",
      "GC"             => "Front Department Lesson (FD)",
      "F1/4"           => "Front Department Lesson (FD)",
      "GRPNDC"         => "Front Department Lesson (FD)",
      "GRPN"           => "Front Department Lesson (FD)",
      "Gift Certi"     => "Front Department Lesson (FD)",
      "F4/4"           => "Front Department Lesson (FD)",
      "F3/4"           => "Front Department Lesson (FD)",
      "F2/4"           => "Front Department Lesson (FD)",
      "F7/8"           => "Front Department Lesson (FD)",
      "F1/6"           => "Front Department Lesson (FD)",
      "F1/8"           => "Front Department Lesson (FD)",
      "F4/8"           => "Front Department Lesson (FD)",
      "F2/8"           => "Front Department Lesson (FD)",
      "F5/8"           => "Front Department Lesson (FD)",
      "F3/6"           => "Front Department Lesson (FD)",
      "F3/8"           => "Front Department Lesson (FD)",
      "F2/6"           => "Front Department Lesson (FD)",
      "F6/8"           => "Front Department Lesson (FD)",
      "F5/6"           => "Front Department Lesson (FD)",
      "Wedding 0"      => "Front Department Lesson (FD)",
      "F8/8"           => "Front Department Lesson (FD)",
      "F4/6"           => "Front Department Lesson (FD)",
      "F6/6"           => "Front Department Lesson (FD)",
      "FPRI25"         => "Front Department Lesson (FD)",
      "DEF"            => "Dance Evaluation (DE)",
      "ST-CMP"         => "Bonus/Comp Lesson",
      "FESTPRI"        => "Bonus/Comp Lesson",
      "WED0"           => "Wedding Lesson",
      "WED1"           => "Wedding Lesson",
      "WED2"           => "Wedding Lesson",
      "WED3"           => "Wedding Lesson",
      "WED4"           => "Wedding Lesson",
      "WED5"           => "Wedding Lesson",
      "WED6"           => "Wedding Lesson",
      "WED7"           => "Wedding Lesson",
      "WED8"           => "Wedding Lesson",
      "Wedding 1"      => "Wedding Lesson",
      "Wedding 2"      => "Wedding Lesson",
      "Wedding 3"      => "Wedding Lesson",
      "Wedding 4"      => "Wedding Lesson",
      "PCMP"           => "Bonus/Comp Lesson",
      "PRI1"           => "Front Department Lesson (FD)",
      "PRI2"           => "Back Dept Lesson (BD)",
      "CMP"            => "Bonus/Comp Lesson",
      // "NPRI"           => "Coach Lesson (C)",
      "PRI3"           => "Middle Dept Lesson",
      "NCLS"           => "Master Class",
      "PRT"            => "Practice Party",
      "Comp Lesson"    => "Bonus/Comp Lesson",
      "Middle Dept Lesson"    => "Front Dept Lesson",

    ];

    // Search in the methods list and find the lesson types.
    if (array_key_exists($service, $lessonTypes)) {
      $convertedService = $lessonTypes[$service];
    }
    else {
      $convertedService = "Not Found!";
    }
    $item->set('service', $convertedService);
  }

  /**
   * Alters a single item enrollments items.
   *
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to make modifications on.
   */
  protected function alterEnrollmentsItem(ItemInterface $item) {

    $item->set('_title_', $item->get('docid'));
    $item->set('sale_date', strtotime($item->get('sale_date')));

    // Get map of package coed and enrollment category.
    $packagesAndCategories = $this->getPackageEnrollmentCategoryMapping();

    // Map the enrollment type (category) to new mapping terms.
    // If old value didn't match any new term or was empty, the enrollment
    // category for that enrollment is set to empty.
    if ($packagesAndCategories[$item->get('category')] && $packagesAndCategories[$item->get('category')] != null) {
      $item->set('category', $packagesAndCategories[$item->get('category')]);
    }
    else {
      $item->set('category', '');
    }

    LogMessage::showMessage($this->feedType . ' importer', 'info', TRUE);
  }

  /**
   * Alters a single item for staff users.
   *
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to make modifications on.
   */
  protected function alterStaffUserItem(ItemInterface $item) {
    LogMessage::showMessage($this->feedType . ' importer', 'info', TRUE);
    $this->amtFeedsAlterRole($item);
  }

  /**
   * Alters staffs role based on CSV file.
   *
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to make modifications on.
   */
  protected function amtFeedsAlterRole(ItemInterface $item) {

    if ($item->get('first_name_') == NULL || $item->get('last_name_') == NULL) {
      $item->set('first_name_', $item->get('name'));
      $item->set('last_name_', $item->get('name'));
    }
    $item->set('scategory', 'instructor');
  }

  /**
   * Alters a single item for Student records.
   *
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to make modifications on.
   */
  protected function alterPayment(ItemInterface $item) {
    $item->set('_title_', $item->get('docid'));

    // If amountpaid is inside () it will set it as negative value.
    if (strpos('(', $item->get('amountpaid'))) {
      $item->set('amountpaid', '-' . intval($item->get('amountpaid')));
    }

    // Setting the default status to be paid for every imported payment.
    $item->set('paid_status', 'Paid');
    LogMessage::showMessage($this->feedType . ' importer', 'info', TRUE);
  }

  /**
   * Alters a single item for Square.
   *
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to make modifications on.
   */
  protected function alterSquare(ItemInterface $item) {
    // Check for if it has values and not null.
    if ($item->get('gross_sales') != "") {
      $grossSales = $item->get('gross_sales');
      // Remove the '$' sign from numbers.
      $grossSales = str_replace('$', '', $grossSales);
      // Remove the ',' sign from numbers.
      $grossSales = str_replace(',', '', $grossSales);

      // Check if number has ')' sign.
      if (strpos($grossSales, ')')) {
        // Remove the brackets.
        preg_match_all('!\d+!', $grossSales, $matches);
        // Get the result from inside of the brackets.
        $tuition = $matches[0][0];
        // Set the number to -number like "-400".
        $item->set('gross_sales', '-' . $tuition);
      }
      else {
        $item->set('gross_sales', (float) $grossSales);
      }

      $time = $item->get('Time');
      $time = date('h:i:s a', strtotime($time));
      $dateAndTime = $item->get('date') . " " . $time;
      $item->set('date_and_time', strtotime($dateAndTime));
      LogMessage::showMessage($this->feedType . ' importer', 'info', TRUE);
    }
  }

  /**
   * Split the user name according to the passed delimiter.
   *
   * @param string $name
   *   The name of user from CSV.
   * @param string $delimiter
   *   Different Delimiter should be use for diffrent user name style.
   *
   * @return array
   *   An array contains the new student names with last and first index return.
   */
  protected function amtFeedsSplitName($name, $delimiter) {
    // Delimiter based on comma will use.
    if ($delimiter == ',') {
      // Provide an array of name that explode with comma.
      $exName = explode($delimiter, $name);
      // If the number of name that is 1 or 2 then simply determine name.
      if (count($exName) <= 2) {
        $finalName[0]['last'] = trim($exName[0]);
        $finalName[0]['first'] = trim($exName[1]);
      }
      // If the number of name that is more than 2 then determine name.
      if (count($exName) > 2) {
        $finalName[0]['last'] = trim($exName[0]);
        $finalName[0]['first'] = trim($exName[1]);
        // Check if the third name has space, devide it to first and last name.
        if (strpos(trim($exName[2]), ' ') > -1) {
          $secondName = explode(' ', trim($exName[2]));
          $finalName[1]['last'] = trim($secondName[1]);
          $finalName[1]['first'] = trim($secondName[0]);
        }
        else {
          // If the third name was single then get the first name as last name.
          $finalName[1]['last'] = trim($exName[0]);
          $finalName[1]['first'] = trim($exName[2]);
        }
      }
    }
    else {
      // Totally if delimiter was and or & do this.
      if (strpos($name, $delimiter) != FALSE) {
        // Explode default name base on pased delimiter.
        $exName = explode($delimiter, $name);
        // Loop base on divided names base on 'and' or '&' that may has comma.
        foreach ($exName as $key => $name) {
          $name = explode(',', $name);
          // If delimiter based on comma provide 1 array name.
          if (count($name) < 2) {
            // If the name don't has last name, use the first stu last name.
            $finalName[$key]['last'] = $finalName[$key - 1]['last'];
            $finalName[$key]['first'] = trim($name[0]);
          }
          else {
            $finalName[$key]['last'] = trim($name[0]);
            $finalName[$key]['first'] = trim($name[1]);
          }
        }
      }
    }
    return $finalName;
  }

  /**
   * Generate the correct name according to mapped names.
   *
   * @param array $arrayName
   *   The names that mapped from user name of CSV.
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   This is the reference data that should modify.
   */
  protected function amtFeedsDetermineName(array $arrayName, ItemInterface $item) {
    // Concatenate the user name based on the array that pased.
    $studentAccountName = '';
    $studentAccountContacts = '';
    $explodeEmail = explode(', ', $item->get('email'));

    foreach ($arrayName as $key => $name) {
      $determineName = $name['first'] . ' ' . $name['last'];
      $thisEmail = (count($explodeEmail) > 0) ? isset($explodeEmail[$key]) ? $explodeEmail[$key] : $explodeEmail[$key - 1] : '';
      // Check that this email and title not set for any other.
      $thisData = _amt_feeds_check_entity_student_record($thisEmail, $determineName);
      if (count($thisData) > 0) {
        // Call the Create student Record function.
        $studentAccountContacts .= reset($thisData) . ', ';
      }
      else {

        $addressRecord = [
          'address_line1'      => $item->get('address1'),
          'address_line2'      => $item->get('address2'),
          'locality'           => $item->get('city'),
          'dependent_locality' => $item->get('administrative_area'),
          'postal_code'        => $item->get('zip'),
        ];
        // Change the main source array to update the name correctly.
        $studentRecord = [
          'title'            => $determineName,
          'field_first_name' => $name['first'],
          'field_last_name'  => $name['last'],
          'field_cell_phone' => $item->get('cphone'),
          'field_gender'     => ($item->get('sex') == 'F') ? '0' : '1',
          'field_home_phone' => $item->get('hphone'),
          'field_email'      => $thisEmail,
        ];
        // Call the Create student Record function.
        $studentAccountContacts .= _amt_feeds_create_entity_stu_record($studentRecord, $addressRecord) . ', ';
      }
      // Generate the mainingful title for this Student account.
      $titleDelimiter = ($studentAccountName != '') ? ' & ' : '';
      $studentAccountName = $studentAccountName . $titleDelimiter . $determineName;
    }
    // Remove extra comma-space from the end of the student account contacts.
    // This is a sting value like : 12, 23, 45.
    $studentAccountContacts = substr($studentAccountContacts, 0, -2);
    $item->set('contacts', $studentAccountContacts);
    $item->set('title', $studentAccountName);
  }

  /**
   * Start the name maping and split process based on the delimiter.
   *
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   And array of readed CSV file that will change and used.
   */
  protected function amtFeedsMainSplit(ItemInterface $item) {
    // Run the code according to the delimiter that will match the name.
    $finalName = $this->amtFeedsSplitName($item->get('sname'), ',');
    if (strpos($item->get('sname'), '&') > -1) {
      $finalName = $this->amtFeedsSplitName($item->get('sname'), '&');
    }
    elseif (strpos($item->get('sname'), ' and ') > -1) {
      $finalName = $this->amtFeedsSplitName($item->get('sname'), ' and ');
    }
    $this->amtFeedsDetermineName($finalName, $item);
  }

  /**
   * Alters a single item for lesson.
   *
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to make modifications on.
   */
  protected function amtFeedsAlterLesson(ItemInterface $item) {
    $item->set('bundle_type', 'lesson');
    _amt_feeds_attendees_scode($item);

  }

  /**
   * Alters a single item for Group lesson.
   *
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to make modifications on.
   */
  protected function amtFeedsAlterGroupLesson(ItemInterface $item) {
    $item->set('bundle_type', 'group_lesson');
    _amt_feeds_attendees_scode($item);

  }

  /**
   * Alters a single item for Services.
   *
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to make modifications on.
   */
  protected function amtFeedsAlterService(ItemInterface $item) {
    $item->set('bundle_type', 'services');
    _amt_feeds_attendees_scode($item);

  }

  /**
   * Alters the date and time for Services and concatenate them together.
   *
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to make modifications on.
   */
  protected function amtFeedsAlterDateAndTime(ItemInterface $item) {
    // System may receive date or date_and_time field.
    // So check any thing that passed.
    if ($item->get('date') == NULL) {
      $date = $item->get('date_and_time');
    }
    else {
      $date = $item->get('date');
    }
    // Check that time passed.
    if (is_numeric($item->get('time'))) {
      // Get the time field from uploaded file,
      // then divide it by '60' to get the hours.
      $time = $item->get('time') / 60;
      // Because time return a decimal number we should separate
      // the Hours and Minutes Individually.
      $explodedTime = explode('.', $time);
      // Get the available minutes then round it to show first two digits.
      $min = substr(60 * ($explodedTime[1] / 10), 0, 2);
      $time = strtotime($explodedTime[0] . ':' . $min);
      $time = date('h:i a', $time);
      $dateAndTime = $date . " " . $time;
      $dateAndTime = date('Y-m-d\TH:i:s', strtotime($dateAndTime));
      $item->set('date_and_time', $dateAndTime);
    }
    else {
      // Provide valid date and time stracture for date and time fields.
      $dateAndTime = date('Y-m-d\TH:i:s+0000', strtotime($date . " " . $item->get('time')));
      // Set the generated dateTime to items for import.
      $item->set('date_and_time', $dateAndTime);

    }
  }

  /**
   * Alters duration of csv file based on allow values in field.
   *
   * FEED type to import.
   */
  protected function amtFeedsAlterDuration(ItemInterface $item) {
    // Converts the duration value to Time format.
    $durations = ["15", "30", "45", "60", "75", "90",
      "105", "120", "135", "150", "165",
    ];
    if (in_array($item->get('duration'), $durations)) {
      $item->set('duration', date('H:i', mktime(0, $item->get('duration'))));
    }
    else {
      $item->set('duration', '03:00');
    }
  }

  /**
   * Skip Items from imports in add feed lesson which includes (Group) string.
   *
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to make modifications on.
   */
  protected function amtFeedsLesson(ItemInterface $item) {
    // Store the main service loaded from csv file to use if import fails.
    $mainService = $item->get('service');
    $this->lessonTypeMethodConvert($item);
    if (preg_match('/\bGroup\b/', $item->get('sname')) && $item->get('scode') === '0') {
      // Set Duration to an Invalid Value for feed to skip this item.
      $item->set('duration', $this::SKIPEVALUE);
      $this->groupLessonSkipped++;
    }
    else {
      if ($item->get('service') == "Not Found!") {
        $this->countSkipType++;
        // Set Duration to an Invalid Value for feed to skip this item.
        $item->set('duration', $this::SKIPEVALUE);
        // Track all services that not matched with lesson types.
        $this->lessonTypeSkiped .= '<li>' . $mainService . '</li>';
      }
      else {
        $this->amtFeedsAlterDateAndTime($item);
        $this->amtFeedsAlterDuration($item);
        $this->amtFeedsAlterLesson($item);
      }
    }
  }

  /**
   * Skip Items from imports in add feed group lesson.
   *
   * Which not includes (Group) string.
   *
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   The item to make modifications on.
   */
  protected function amtFeedsGroupLesson(ItemInterface $item) {

    if ($item->get('service') == "GRP" || $item->get('service') == "BC" || $item->get('service') == "GRP1") {
      $item->set('service', 'Group');
      // If it is a group lesson then continue and import it as group lesson.
      $this->amtFeedsAlterDateAndTime($item);
      $this->amtFeedsAlterDuration($item);
      $this->amtFeedsAlterGroupLesson($item);
    }
    else {
      // Set Duration to an Invalid Value for feed to skip this item.
      $item->set('duration', $this::SKIPEVALUE);
      $this->countSkipType++;
      $this->lessonTypeSkiped .= '<li>' . $item->get('sname') . ' (' . $item->get('scode') . ')</li>';
    }
  }

  /**
   * Skip Items from imports in add feed service.
   *
   * If the type not match with service.
   *
   * @param \Drupal\feeds\Feeds\Item\ItemInterface $item
   *   It have the records and use for altering importing data.
   */
  protected function amtFeedsService(ItemInterface $item) {
    // Store the main service loaded from csv file to use if import fails.
    $mainService = $item->get('service');
    $this->serviceTypeMethodConverter($item);

    if ($item->get('service') == "Not Found!") {

      // If servise not found skip this item to import.
      $item->set('duration', $this::SKIPEVALUE);
      $this->lessonTypeSkiped .= '<li>' . $mainService . '</li>';
      $this->countSkipType++;
    }

    else {
      $this->amtFeedsAlterDateAndTime($item);
      $this->amtFeedsAlterDuration($item);
      $this->amtFeedsAlterService($item);
    }
  }

  /**
   * Retrieves the mapping for a feed based off of it's type.
   */
  private function getCallbackMapping() {

    // Define the mapping of feed types to their callbacks.
    $mapping = [
      'packages'        => ['alterPackagesItem'],
      'enrollments'     => ['alterEnrollmentsItem'],
      'staff_user'      => ['alterStaffUserItem'],
      'payment'         => ['alterPayment'],
      'lesson'          => ['amtFeedsLesson'],
      'group_lesson'    => ['amtFeedsGroupLesson'],
      'square'          => ['alterSquare'],
      'student_account' => ['inquiryMethodConvert', 'amtFeedsMainSplit'],
      'services'        => ['amtFeedsService'],
    ];

    // If a mapping can't be found, then return false.
    $feedMap = FALSE;

    // If a mapping is found for this feed type, then return only that mapping.
    if (!empty($mapping[$this->feedType])) {
      $feedMap = $mapping[$this->feedType];
    }

    return $feedMap;
  }

  /**
   * Returns the inqury mapping for legacy methods to their new version.
   */
  private function getInquiryMethodMapping() {
    return [
      'Internet' => [
        'IN',
        'Woofu',
        'Woof',
        'u',
      ],
      'Phone' => [
        'TEL',
      ],
      'Walk-in' => [
        'W',
        'WIN',
      ],
      'Guest' => [
        'GST',
      ],
      'Other' => [
        'EMA',
        'RL',
        'NON',
        'EXC',
        'WEDNGHTMRKT',
        'GIF',
        'WCB',
        'ALOCAL',
        'Chamber',
        'AUCTION',
        '8',
        '12',
        '1',
        '5',
        '14',
        '15',
        'EVENT',
        's',
        '7',
        '2',
        '13',
        '6',
        'How',
        '3',
        '0',
      ],
    ];
  }

  /**
   * Returns the service mapping for services to their new equivalents.
   */
  private function getServiceMapping() {
    return [
      'Internal Use' => [
        'TTEA', 'CCAUTH', 'SPNSR', 'NEWCOMER',
        'HERE', 'HLDOVR', 'Clean Up', 'Over Time',
        'In', 'fpri', 'HOL', 'bp', 'DST', 'SCHSTD',
        'choreo', 'TIMECARD', 'ASTCHT', 'BUDDYCHT',
        'SNAP??', 'Holdover', 'Foundation'
      ],
      ' ' => ['0', 'GST', 'Hold', 'LOCK UP', 'Tuition No', 'CG'],
      'Meeting'                 => ['MTG', 'ADM MTG', 'Exec', 'INT', 'MKTG'],
      'Misc'               => ['CHAT', 'SHOW', 'GRPN',
        'MISC', 'FESTIVAL', 'GALA', 'OH', 'Grad Chat'
      ],
      'Renewal Chat'            => ['ren', 'REN', 'REN (R)', 'COMM'],
      'Service Chat'            => ['service', 'MBCHT', 'SV', 'LSA'],
      'Graduation/Checkout'              => ['GRAD'],
      'Dance Demo'              => ['CP'],
      'Out (Regular Schedule)'  => ['OUT', 'out'],
      'Out (Time off Request)' => ['OUT RQ'],
      'Meal Break'              => ['DNR'],
      'Dance Session'           => ['DS', 'Practice'],
      'Break'                   => ['brk'],
      'Read Me'                 => ['README'],
      'Training Class'          => ['TC'],
      'Schedule'                => ['SCHD'],
      'One on One'              => ['121'],
      'Out (Sick)'                => ['SICK'],
      'Assist Lesson'           => ['ASSTCL', 'ASPRI'],
      'Coach Lesson (C)'         => ['PROCOCH', 'NPRI', 'Coaching'],
      'Lesson Package Chat'     => ['LSNPKG', 'Lesson pkg'],
      'Dream Sheet'             => ['DREAM'],
      'Outside Event'            => ['Event Out'],
      'Service Visit'           => ['Drive', 'GOALS', 'SNAP📸', 'Visit', 'Video'],
      'Dance O Rama Chat'       => ['DOR'],
      'Progress Check'          => ['PRG'],
      'Benefit Sheet'           => ['BENST'],
      'Out by Time Off Request' => ['AWAY'],
      'Grad Chat'               => ['GRDCHT', 'MDL'],
      'Showcase Chat'           => ['SHWCSE'],
      'Extension Chat'          => ['EXT'],
      'Calls'                   => ['CALL', 'C/BOOK', 'C/FU', 'EM'],
      'Desk'                    => ['DESK', 'desk', 'Duty'],
      'Original Chat'           => ['ORI', 'ORI (R)'],
      'Tuition'                 => ['Tuition', 'CC AUTH'],
    ];
  }

  /**
   * Returns the correct enrollment category.
   *
   * In this function the key in each array index is representing
   * legacy system's enrollment types provided by the user, and
   * the values are the new mappings in this system.
   */
  private function getPackageEnrollmentCategoryMapping() {
    return [
      'REW' => 'Rewrite',
      'ORI' => 'Original',
      'ORI1' => 'Re-original',
      'NSR-ORI' => 'NSR - 2nd original',
      'PORI' => 'Pre original',
      'PORI1' => 'Promo certificate',
      'NSR-PORI' => 'NSR - 1st original',
      'GIFT' => 'Gift certificate',
      'EXT' => 'Extension',
      'EXT1' => 'Extension type 1',
      'NSR-EXT' => 'NSR - extension',
      'REXT' => 'Re-extension',
      'REN' => 'Renewal',
      'REN1' => 'Renewal type 1',
      'NSR-REN' => 'NSR - renewal',
      'MISC' => 'Miscellanious service',
      'NUNIT' => 'Non-unit service',
      'SUN' => 'Sundry',
      'BONUS' => 'Bonus /Prize',
      'EXP-PFR' => 'Paid finish return - extension',
      'REN-PFR' => 'Paid finish return - renewal',
      'TRAN-IN' => 'Transfer in',
      'TRAN-OUT' => 'Transfer out',
      'PORI~D' => 'Pre original drop',
      'ORI~D' => 'Original drop',
      'EXT~D' => 'Extension drop',
      'REXT~D' => 'Re-extension drop',
      'REN~D' => 'Renewal drop',
      'MISC~D' => 'Miscellanious service drop',
      'NUNIT~D' => 'Non-unit service drop',
      'SUN~D' => 'Sundry drop',
      'GIVE' => 'Transfer lessons to another student',
      'RECEIVE' => 'Get lessons from another student',
      'PTR' => 'Promise to return'
    ];
  }

  /**
   * Identifying standings and creating them.
   *
   * This function is called right after the imports of lessons are finished.
   * It is going to identify all the standing lesson from the imported
   * events try put the related ones in the same and single standing lesson.
   * @param object $eventsAfterFinish
   *   The import object that is just imported.
   */
  public function afterFinish(ImportFinishedEvent $eventsAfterFinish) {
    // Check if the import was the lessons.
    if ($eventsAfterFinish->getFeed()->type->target_id == 'lesson') {
      amt_feeds_create_the_standings();
    }
  }

}
