<?php

namespace Drupal\amt_staff;

use Datetime;
use Drupal\config_pages\Entity\ConfigPages;

/**
 * Provid the functionality of sending report to API.
 *
 * @package Drupal\amt_staff\SendReportsToAPI
 */
class SendReportsToAPI {

  /**
   * Get new access token from client AMI.
   *
   * @return string
   *   Return the access token in string format.
   */
  private static function accessToken() {
    // Getting AMI info entered in ami token settings page:
    $amiConfigs = ConfigPages::config('ami_token_settings');
    $apiUrl = $amiConfigs->field_ami_address->uri;
    $clientId = $amiConfigs->field_client_id->value;
    $clientSecret = $amiConfigs->field_clientecret->value;
    $grandType = $amiConfigs->field_grant_type->value;
    $userName = $amiConfigs->field_user->value;
    $password = $amiConfigs->field_password->value;

    // Preparing request url to get the access token:
    $requestUrl = $apiUrl . "/oauth/v2/token?client_id=" . $clientId . "&client_secret=" . $clientSecret . "&grant_type=" . $grandType . "&username=" . $userName . "&password=" . $password;
    $client = \Drupal::httpClient();
    $request = $client->get($requestUrl, ['http_errors' => FALSE]);
    $response = json_decode($request->getBody());

    return !empty($response->access_token) ? $response->access_token : NULL;
  }

  /**
   * Send a Curl Post to $url.
   *
   * @param object $postArray
   *   Post Data as array.
   *
   * @return array
   *   Return an array with two sub elements - Message and Message type.
   */
  public static function sendReports($postArray) {
    $urlData = '';
    // Getting AMI info entered in ami token settings page:
    $amiConfigs = ConfigPages::config('ami_token_settings');
    $apiUrl = $amiConfigs->field_ami_address->uri;
    if (empty($apiUrl)) {
      $mainRes['message'] = '<strong>Invalid AMI Configs!</strong> Please make sure the <a href="' . base_path() . 'admin/config/ami-token-settings"> AMI token settings </a> are correct.';
      $mainRes['message_type'] = 'warning';
      return $mainRes;
    }
    $url = "$apiUrl/api/v1/reports";
    $token = SendReportsToAPI::accessToken();
    if (empty($token)) {
      $mainRes['message'] = '<strong>Failed to Get Access Token!</strong> Please make sure the <a href="' . base_path() . 'admin/config/ami-token-settings"> AMI token settings </a> are correct.';
      $mainRes['message_type'] = 'warning';
      return $mainRes;
    }
    // $postArray = array();
    if (count($postArray)) {
      $post = http_build_query($postArray);
      $header = ['Authorization: Bearer ' . $token, 'Accept: application/json'];
      $curl = curl_init();
      curl_setopt($curl, CURLOPT_HTTPHEADER, $header);
      curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);

      curl_setopt($curl, CURLOPT_URL, $url . $urlData);
      curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
      $content = trim(curl_exec($curl));
      $info = curl_getinfo($curl);
      $content = json_decode($content, TRUE);

      // Log the data for other debugging.
      if($_GET['logger'] == TRUE) {
        try {
          \Drupal::logger('AMI_REPORT')->notice('<pre>@details</pre>', ['@details' => json_encode($postArray)]);
        } catch (\Throwable $th) {
        }
      }else{
        \Drupal::logger('AMI_REPORT')->notice('Use &logger=ture in url to track data.');
      }

      // Check report need update or import to api.
      if ($_GET['operation'] == "Update API" &&  $_GET['year'] == date("Y")) {
        $urlData = '?type=' . $postArray['type'] . '&week_number=' . $postArray['week_number'] . '&week_year=' . $postArray['week_year'];
        curl_setopt($curl, CURLOPT_URL, $url . $urlData);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "GET");
        $content = trim(curl_exec($curl));
        $info = curl_getinfo($curl);
        $content = json_decode($content, TRUE);
        // Check the content is not empty and we have the id of report.
        if ($content != NULL) {
          // Prepare and send put request to api for update.
          curl_setopt($curl, CURLOPT_URL, $url . '/' . $content[0]['id']);
          curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
          curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
          // Send the request & save response to $resp.
          $resp = curl_exec($curl);
          $info = curl_getinfo($curl);
          // If the response was not null and code was 400 show the
          // validation and null required field.
          if ($resp != NULL) {
            $response = json_decode($resp, TRUE);
            if ($info['http_code'] == 400) {
              // Check errors on each invalid or null data of reports.
              $mainRes['message'] = '<strong>' . $response['message'] . '</strong>';
              foreach ($response['errors']['children']['line_items']['children'] as $errors) {
                foreach ($errors['children'] as $key => $error) {
                  if (array_key_exists('errors', $error)) {
                    // Concatenate errors of all fields.
                    $msg = '<br>' . $key . ' => ' . $error['errors'][0];
                    if (strstr($mainRes['message'], $msg) == FALSE) {
                      $mainRes['message'] .= $msg;
                    }
                  }
                }
              }
              $mainRes['message'] .= "<br><strong>Code:</strong>" . $response['code'];
            }
            elseif ($response['error'] != NULL) {
              $mainRes['message'] = '<strong>' . $response['error']['message'] . '</strong>';
              $mainRes['message'] .= '<br><strong>Code: </strong>' . $response['error']['code'];
            }
            $mainRes['message_type'] = 'warning';
            return $mainRes;
          }
          elseif ($info['http_code'] == 204) {
            // If it was successfully it show the success message.
            $mainRes['message'] = '<b>' . $postArray['type'] . '</b> has been <b> Updated</b>' . ' with this <b>' . $content[0]['id'] . '</b> ID.';
            $mainRes['message_type'] = 'info';
            return $mainRes;
          }
        }
        else {
          // It show the report is not in api.
          $mainRes['message'] = '<b>' . $postArray['type'] . '</b> Report not exist in API.';
          $mainRes['message_type'] = 'warning';
          return $mainRes;
        }
      }
      elseif ($_GET['operation'] == "Update API") {
        $mainRes['message'] = 'Only the current year <b>' . $postArray['type'] . ' Reports</b> are updatable.';
        $mainRes['message_type'] = 'warning';
        return $mainRes;
      }
      // Send post request in api for submiting the report.
      if ($_GET['operation'] == "Send API") {
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        // Send the request & save response to $resp.
        $resp = curl_exec($curl);
        $info = curl_getinfo($curl);
      }
    }
    else {
      $mainRes['message'] = '<strong>No Data Passed to API</strong>: Please try to enter valid data or contact Administrator<br />';
      $mainRes['message_type'] = 'error';
      return $mainRes;
    }
    // Check if there is any Error from header or curl.
    if ((($resp == NULL or empty($resp)) and ($info['http_code'] != 200 or $info['http_code'] != 201)) or curl_errno($curl)) {
      $err = curl_error($curl);
      $re = [];
      $re['error'] = 'Error ' . $info['http_code'];
      $re['error_description'] = $err . ' <br/> URL: ' . $info['url'] . ' <br/>If this Message did not help please contact Server Administrator';
      $response = $re;
    }
    else {
      $response = json_decode($resp, TRUE);
      if ($response['errors']['errors'] != NULL) {
        $mainRes['message'] = '<strong>Error</strong><br/>';

        $errorCause = isset($response['errors']['errors'][1]) ? $response['errors']['errors'][1] : '';
        $mainRes['message'] .= $response['errors']['errors'][0] . " " . $errorCause;
        $mainRes['message'] .= '<br/><strong>Code: </strong>' . $response['code'];
        $mainRes['message_type'] = 'warning';
        return $mainRes;
      }
      // This condition check if the report not send successfully
      // it show the validation are require.
      elseif ($response['code'] == 400) {
        // If it is royalty report, check errors on each null data.
        $mainRes['message'] = '<strong>Invalid report:</strong>';
        foreach ($response['errors']['children']['line_items']['children'] as $errors) {
          foreach ($errors['children'] as $key => $error) {
            if (array_key_exists('errors', $error)) {
              // Concatenate errors of all fields.
              $msg = '<br>' . $key . ' => ' . $error['errors'][0] . '<br>';
              if (strstr($mainRes['message'], $msg) == FALSE) {
                $mainRes['message'] .= $msg;
              }
            }
          }
        }
        $mainRes['message_type'] = 'warning';
        return $mainRes;
      }
    }
    if (array_key_exists('message', $response)) {
      $mainRes['message'] = "<strong>" . $response['message'] . "</strong>: " . $response['errors']['errors'][0] . "<br /> Code: " . $response['code'];
      $mainRes['message_type'] = SendReportsToAPI::scodeType($response['code']);
    }
    elseif (array_key_exists('error', $response)) {
      $mainRes['message'] = '<strong>' . $response['error'] . '</strong>: ' . $response['error_description'];
      $mainRes['message_type'] = 'error';
    }
    elseif (isset($response['type'])) {
      $mainRes['message'] = '<strong>' . $response['type'] . ' has been Imported</strong> which was prepared by:' . $response['prepared_by'] . ' with this ' . $response['id'] . ' ID.';
      $mainRes['message_type'] = 'info';
      // Create report log based on response data.
      return $mainRes;
    }
    else {
      $mainRes['message'] = '<strong>Unknow Error</strong>: Please Contact Administrtor<br /> Error: ' . serialize($response) . ' <br/> Passed Data:' . $postArray;
      $mainRes['message_type'] = 'error';
    }
    curl_close($curl);
    return $mainRes;
    // Close request to clear up some resources.
  }

  /**
   * Take $code and return String which type of Error.
   *
   * @param string $code
   *   Header Code.
   *
   * @return string
   *   String (info,warning,error).
   */
  private static function scodeType($code) {
    $t = 'info';
    if ($code >= 300 or $code <= 399) {
      $t = 'warning';
    }
    elseif ($code >= 400) {
      $t = 'error';
    }
    return $t;
  }

  /**
   * Provide a start and end date from week number and year.
   *
   * @param int $week
   *   What week number you want to find dates.
   * @param int $year
   *   Which year you want to check the dates.
   *
   * @return array
   *   Return any array contain week_start and week_end dates.
   */
  public static function convertStartEndDate($week, $year) {
    // $date = new DateTime();
    // // Get the first day of the week and when
    // // we set 1 it show the Monday.
    // $date->setISODate($year, $week, 1);
    // $ret['week_start'] = $date->format('Y-m-d');
    // // Get the last date of the week and
    // // it show 7 day after the start of the week (next Sunday).
    // $date->setISODate($year, $week, 7);
    // $ret['week_end'] = $date->format('Y-m-d');

    // Based on the request there need to be a more customized version of this range date:
    // The cline has informed me that for reporting purposes, week 1 should end on January 2nd where currently it seems a week has been skipped
    $date = new DateTime();
    // Get the first day of the week and when
    $date->setISODate($year, $week);
    // get first saturday and calculate first end date.
    $ret['week_end'] = date('Y-m-d', strtotime("first sat of january $year"));
    // convert to object date.
    $date = new DateTime($ret['week_end']);
    $date2 = clone $date;

    // Calculate start date.
    $ret['week_start'] = $date2->modify('-6 days')->format('Y-m-d');

    // a counter for while.
    $counter = 1;
    while($counter <= ($week - 1) && $week > 1) {

      // Each week, we calculate both dates.
      $ret['week_end'] =  $date->modify('+1 week')->format('Y-m-d');
      $date2 = clone $date;
      $ret['week_start'] = $date2->modify('-6 days')->format('Y-m-d');
      
      $counter++;
    }

    // week_start index in the array should come first and then week_end,
    // that's why, I revered the array here.

    // This is to consider time section, so this includes the end date prcisely.
    $ret['week_end'] .= 'T13:00:00'; 
    return array_reverse($ret);
  }

  /**
   * Provide a header for all report by the type of report.
   *
   * @param string $reportType
   *   Which report type you want get header.
   *
   * @return array
   *   Return any array contain header of reports.
   */
  public static function getReportHeaders($reportType) {
    return [
      "type"       => $reportType,
      "prepared_by" => \Drupal::currentUser()->getUsername(),
      "week_number" => (int) $_GET['week'],
      "week_year"   => (int) $_GET['year'],
    ];
  }

}
