<?php

namespace Drupal\google_vision_test;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\google_vision\GoogleVisionAPI;
use Symfony\Component\DependencyInjection\ContainerInterface;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\RequestOptions;

class GoogleVisionAPIFake extends GoogleVisionAPI {

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
   * Construct a GoogleVisionAPIFake object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory.
   *
   * @param \GuzzleHttp\ClientInterface $http_client
   *   A Guzzle client object.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ClientInterface $http_client) {
    $this->configFactory = $config_factory;
    $this->httpClient = $http_client;
    $this->apiKey = $this->configFactory->get('google_vision.settings')
      ->get('api_key');
  }

  /**
   * Function to return the response showing the image contains explicit content.
   *
   * @param string $filepath .
   *
   * @return Array|bool.
   */
  public function safeSearchDetection($filepath) {
    if (!$this->apiKey) {
      return FALSE;
    }
    $response = array(
      'responses' => array(
        '0' => array(
          'safeSearchAnnotation' => array(
            'adult' => 'LIKELY',
            'spoof' => 'VERY_UNLIKELY',
            'medical' => 'POSSIBLE',
            'violence' => 'POSSIBLE'
          ),
        ),
      ),
    );
    return $response;
  }
}
