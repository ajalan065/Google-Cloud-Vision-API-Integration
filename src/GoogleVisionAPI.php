<?php

namespace Drupal\google_vision;

use Drupal\Component\Utility\Html;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Component\Serialization\Json;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;

class GoogleVisionAPI {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The HTTP client to fetch the feed data with.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  protected $httpClient;

  /**
   * Stores the API key.
   *
   * @var int
   */
  protected $apiKey;

  /**
   * The base url of the Google Cloud Vision API.
   */
  const APIEndpoint = 'https://vision.googleapis.com/v1/images:annotate?key=';

  /**
   * Construct a GoogleVisionAPI object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   A Guzzle client object.
   *
   * @todo Throw the exception when the api key is not set on status page.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client) {
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
    $this->apiKey = $this->configFactory->get('google_vision.settings')
      ->get('api_key');
  }

  /**
   * Encode image to send it to the Google Vision API.
   */
  private function encodeImage($filepath) {
    // It looks pretty dirty. I hope that in future it will be implemented in Google SDK
    // and we will be able to avoid this approach.
    $encoded_image = base64_encode(file_get_contents($filepath));
    return $encoded_image;
  }

  /**
   * Function to make request through httpClient service.
   *
   * @param $data .
   *  The object to be passed during the API call.
   *
   * @return An array obtained in response from the API call.
   */
  protected function postRequest($data) {
    $url = static::APIEndpoint . $this->apiKey;
    try {
      $response = $this->httpClient->post($url, [
        RequestOptions::JSON => $data,
        RequestOptions::HEADERS => ['Content-Type' => 'application/json'],
      ]);
      return Json::decode($response->getBody());
    }
    catch (\Exception $e) {
      if ($e->getCode() == '403') {
        drupal_set_message(t('The Google Vision API could not be successfully reached. Please check your API credentials and try again. The raw error message from the server is shown below.'), 'warning');
      }
      drupal_set_message(Html::escape($e->getMessage()), 'warning');
      return ['error' => $e->getMessage()];
    }
  }

  /**
   * Function to retrieve labels for given image.
   *
   * @param string $filepath .
   *
   * @return Array|bool.
   */
  public function labelDetection($filepath) {
    if (!$this->apiKey) {
      return FALSE;
    }

    // Prepare JSON.
    $data = [
      'requests' => [
        [
          'image' => [
            'content' => $this->encodeImage($filepath),
          ],
          'features' => [
            [
              'type' => 'LABEL_DETECTION',
              'maxResults' => 5
            ],
          ],
        ],
      ],
    ];

    $result = $this->postRequest($data);
    if (empty($result['error'])) {
      return $result;
    }
    return FALSE;

  }

  /**
   * Function to detect landmarks within a given image.
   *
   * @param string $filepath .
   *
   * @return Array|bool.
   */
  public function landmarkDetection($filepath) {
    if (!$this->apiKey) {
      return FALSE;
    }

    // Prepare JSON.
    $data = [
      'requests' => [
        [
          'image' => [
            'content' => $this->encodeImage($filepath),
          ],
          'features' => [
            [
              'type' => 'LANDMARK_DETECTION',
              'maxResults' => 2
            ],
          ],
        ],
      ],
    ];

    $result = $this->postRequest($data);
    if (empty($result['error'])) {
      return $result;
    }
    return FALSE;

  }

  /**
   * Function to detect logos of famous brands within a given image.
   *
   * @param string $filepath .
   *
   * @return Array|bool.
   */
  public function logoDetection($filepath) {
    if (!$this->apiKey) {
      return FALSE;
    }

    // Prepare JSON.
    $data = [
      'requests' => [
        [
          'image' => [
            'content' => $this->encodeImage($filepath),
          ],
          'features' => [
            [
              'type' => 'LOGO_DETECTION',
              'maxResults' => 2
            ],
          ],
        ],
      ],
    ];

    $result = $this->postRequest($data);
    if (empty($result['error'])) {
      return $result;
    }
    return FALSE;

  }

  /**
   * Function to detect explicit content within a given image.
   *
   * @param string $filepath .
   *
   * @return Array|bool.
   */
  public function safeSearchDetection($filepath) {
    if (!$this->apiKey) {
      return FALSE;
    }

    // Prepare JSON.
    $data = [
      'requests' => [
        [
          'image' => [
            'content' => $this->encodeImage($filepath),
          ],
          'features' => [
            [
              'type' => 'SAFE_SEARCH_DETECTION',
              'maxResults' => 1
            ],
          ],
        ],
      ],
    ];

    $result = $this->postRequest($data);
    if (empty($result['error'])) {
      return $result;
    }
    return FALSE;

  }

  /**
   * Function to retrieve texts for given image.
   *
   * @param string $filepath .
   *
   * @return Array|bool.
   */
  public function opticalCharacterRecognition($filepath) {
    if (!$this->apiKey) {
      return FALSE;
    }

    // Prepare JSON.
    $data = [
      'requests' => [
        [
          'image' => [
            'content' => $this->encodeImage($filepath),
          ],
          'features' => [
            [
              'type' => 'TEXT_DETECTION',
              'maxResults' => 10
            ],
          ],
        ],
      ],
    ];

    $result = $this->postRequest($data);
    if (empty($result['error'])) {
      return $result;
    }
    return FALSE;

  }

  /**
   * Function to fetch faces from a given image.
   *
   * @param string $filepath .
   *
   * @return Array|bool.
   */
  public function faceDetection($filepath) {
    if (!$this->apiKey) {
      return FALSE;
    }

    // Prepare JSON.
    $data = [
      'requests' => [
        [
          'image' => [
            'content' => $this->encodeImage($filepath),
          ],
          'features' => [
            [
              'type' => 'FACE_DETECTION',
              'maxResults' => 25
            ],
          ],
        ],
      ],
    ];

    $result = $this->postRequest($data);
    if (empty($result['error'])) {
      return $result;
    }
    return FALSE;

  }

  /**
   * Function to retrieve image attributes for given image.
   *
   * @param string $filepath .
   *
   * @return Array|bool.
   */
  public function imageAttributesDetection($filepath) {
    if (!$this->apiKey) {
      return FALSE;
    }

    // Prepare JSON.
    $data = [
      'requests' => [
        [
          'image' => [
            'content' => $this->encodeImage($filepath),
          ],
          'features' => [
            [
              'type' => 'IMAGE_PROPERTIES',
              'maxResults' => 5
            ],
          ],
        ],
      ],
    ];

    $result = $this->postRequest($data);
    if (empty($result['error'])) {
      return $result;
    }
    return FALSE;

  }
}
