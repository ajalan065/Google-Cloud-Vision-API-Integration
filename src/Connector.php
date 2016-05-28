<?php

/**
 * @file
 * Google vision connector.
 */

namespace Drupal\google_vision;

use Drupal\Component\Serialization\Json;

class Connector {

  /**
   * Function to retrieve labels for given image.
   */
  public static function makeRequestForLabels($filepath) {
    $config = \Drupal::getContainer()->get('config.factory')->getEditable('google_vision.settings');
    $api_key = $config->get('api_key');
    if (!$api_key) {
      return FALSE;
    }

    // It looks pretty dirty. I hope that in future it will be implemented in Google SDK
    // and we will be able to avoid this approach.
    $encoded_image = base64_encode(file_get_contents($filepath));

    // Prepare JSON.
    $data = '{
      "requests":[
        {
          "image":{
            "content":"' . $encoded_image . '"
          },
          "features":[
            {
              "type":"LABEL_DETECTION",
              "maxResults":5
            }
          ]
        }
      ]
    }';

    $ch = curl_init('https://vision.googleapis.com/v1/images:annotate?key=' . $api_key);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json',
        'Content-Length: ' . strlen($data),
      )
    );

    $result = Json::decode(curl_exec($ch));
    if (empty($result['error'])) {
      return $result;
    }
    return FALSE;
  }
}
