<?php

namespace Drupal\amt_copypaste\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * An amt_copypaste controller.
 */
class AmtCopyPasteController extends ControllerBase {

    /**
     * Move or replicate the selected event.
     */
    function CopyPasteReplicateThisEvent() {
        // Concaticating date and time from given array.
        $newDateString = $_GET['newDate'][0] . '-' . $_GET['newDate'][1] . '-' . $_GET['newDate'][2] . ' ' . $_GET['newDate'][3] . ':' . $_GET['newDate'][4] . ':' . $_GET['newDate'][5];

        // Creating date and time object.
        $_GET['datetime'] = date('Y-m-d\TH:i:s', strtotime($newDateString));
        return new JsonResponse(amt_copypaste_endpoint());
    }
}
