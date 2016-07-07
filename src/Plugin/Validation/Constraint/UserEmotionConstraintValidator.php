<?php

namespace Drupal\google_vision\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\file\FileInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\google_vision\GoogleVisionAPI;
use Drupal\Core\File\FileSystemInterface;

/**
 * Validates the UserEmotion constraint.
 */
class UserEmotionConstraintValidator extends ConstraintValidator implements ContainerInjectionInterface {

  /**
   * The Google Vision API.
   *
   * @var \Drupal\google_vision\GoogleVisionAPI.
   */
  protected $googleVisionAPI;

  /**
   * The file system.
   *
   * @var \Drupal\Core\File\FileSystemInterface.
   */
  protected $fileSystem;

  /**
   * Constructs a UserEmotionConstraintValidator object.
   *
   * @param \Drupal\google_vision\GoogleVisionAPI $googleVisionAPI
   *  The Google Vision API object.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *  The File System object.
   */
  public function __construct(GoogleVisionAPI $google_vision, FileSystemInterface $file_system) {
    $this->googleVisionAPI = $google_vision;
    $this->fileSystem = $file_system;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($data, Constraint $constraint) {
    $field_def = $data->getFieldDefinition();
    $settings = $field_def->getThirdPartySettings('google_vision');
    // If the Emotion detection is on.
    if (!empty($settings['emotion_detect'])) {
      $value = $data->getValue('target_id');
      if (!empty($value)) {
        // Retrieve the file uri.
        $file_uri = $data->entity->getFileUri();
        if ($filepath = $this->fileSystem->realpath($file_uri)) {
          $result = $this->googleVisionAPI->faceDetection($filepath);
          if (!empty($result['responses'][0]['faceAnnotations'])) {
            $joy = $result['responses'][0]['faceAnnotations'][0]['joyLikelihood'];
            $likelihood = array('UNLIKELY', 'VERY_UNLIKELY');
            // If the image is not a happy one.
            if (in_array($joy, $likelihood)) {
              drupal_set_message($constraint->message, 'warning');
            }
          }
        }
      }
    }
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static (
      $container->get('google_vision.api'),
      $container->get('file_system')
    );
  }
}
