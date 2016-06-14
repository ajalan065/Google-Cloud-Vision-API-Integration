<?php

namespace Drupal\google_vision\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\google_vision\GoogleVisionAPI;
use Drupal\Core\Field\FieldItemListInterface;
use Drupal\file\FileInterface;
use Drupal\node\NodeInterface;
use Drupal\field\FieldConfigInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;

/**
 * Validates the SafeSearch constraint.
 */
class SafeSearchConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($data, Constraint $constraint) {
    foreach(\Drupal::service('entity_field.manager')->getFieldDefinitions('node', $data->getType()) as $field_name => $field_def) {
      // if field is image field.
      if($field_def->getType() == 'image') {
        $settings = $field_def->getThirdPartySettings('google_vision');
        // if the Safe Search detection is on.
        if(!empty($settings['google_vision'])) {
          // if the image is uploaded.
          if(!empty($data->get($field_name)->target_id)) {
            // Retrieve the file uri.
            $file_uri = $data->get($field_name)->entity->getFileUri();
            if($filepath = \Drupal::service('file_system')->realpath($file_uri)) {
              $result = \Drupal::service('google_vision.api')->safeSearchDetection($filepath);
              if(!empty($result['responses'][0]['safeSearchAnnotation'])) {
                $adult = $result['responses'][0]['safeSearchAnnotation']['adult'];
                $likelihood = array('LIKELY', 'VERY_LIKELY');
                // if the image has explicit content.
                if(in_array($adult, $likelihood)) {
                  $this->context->addViolation($constraint->message, array('%title' => $data->get($field_name)->entity->getFilename()));
                }
              }
            }
          }
        }
      }
    }
  }
}
