<?php

namespace Drupal\google_vision\Tests;

use Drupal\Core\Url;
use Drupal\node\Entity\Node;
use Drupal\field\Entity\FieldConfig;
use Drupal\Core\Field\FieldConfigBase;
use Drupal\simpletest\WebTestBase;
use Drupal\field\Entity\FieldStorageConfig;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Field\FieldItemListInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Drupal\google_vision\GoogleVisionAPI;

/**
 * Tests to verify whether the Safe Search Constraint Validation works
 * correctly.
 *
 * @group google_vision
 */
class SafeSearchConstraintValidationTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = ['google_vision', 'node', 'image', 'field_ui', 'field'];

  /**
   * A user with permission to create content and upload images.
   *
   * @var object
   */
  protected $adminUser;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    //Create custom content type.
    $contentType = $this->drupalCreateContentType(array('type' => 'test_images', 'name' => 'Test Images'));
    // Creates administrative user.
    $this->adminUser = $this->drupalCreateUser(array('administer google vision','create test_images content', 'access content',  'access administration pages', 'administer node fields', 'administer nodes', 'administer node display')
    );
    $this->drupalLogin($this->adminUser);

    //Set the API Key.
    \Drupal::configFactory()->getEditable('google_vision.settings')->set('api_key', 'AIzaSyAtuc_LOB70imSYsT0TXFUNnNlMqiDoyP8')->save();
    $this->drupalGet(Url::fromRoute('google_vision.settings'));
    $this->assertResponse(200);
    $this->assertFieldByName('api_key', 'AIzaSyAtuc_LOB70imSYsT0TXFUNnNlMqiDoyP8', 'The key has been saved');
  }

  /**
   * Create a new image field.
   *
   * @param string $name
   *   The name of the new field (all lowercase), exclude the "field_" prefix.
   * @param string $type_name
   *   The node type that this field will be added to.
   * @param array $storage_settings
   *   A list of field storage settings that will be added to the defaults.
   * @param array $field_settings
   *   A list of instance settings that will be added to the instance defaults.
   * @param array $widget_settings
   *   Widget settings to be added to the widget defaults.
   * @param array $formatter_settings
   *   Formatter settings to be added to the formatter defaults.
   * @param string $description
   *   A description for the field.
   */
  public function createImageField($name, $type_name, $storage_settings = array(), $field_settings = array(), $widget_settings = array(), $formatter_settings = array(), $description = '') {
    FieldStorageConfig::create(array(
      'field_name' => $name,
      'entity_type' => 'node',
      'type' => 'image',
      'settings' => $storage_settings,
      'cardinality' => 1,
    ))->save();

    $field_config = FieldConfig::create([
      'field_name' => $name,
      'label' => $name,
      'entity_type' => 'node',
      'bundle' => $type_name,
      'settings' => $field_settings,
      'description' => $description,
    ])->addConstraint('SafeSearch');
    $field_config->save();

    entity_get_form_display('node', $type_name, 'default')
      ->setComponent($name, array(
        'type' => 'image_image',
        'settings' => $widget_settings,
      ))
      ->save();

    entity_get_display('node', $type_name, 'default')
      ->setComponent($name, array(
        'type' => 'image',
        'settings' => $formatter_settings,
      ))
      ->save();

    return $field_config;
  }

  /**
   * Get the field id of the image field formed.
   */
  public function getImageFieldId() {
    // Create an image field and add an field to the custom content type.
    $storage_settings['default_image'] = array(
      'uuid' => 1,
      'alt' => '',
      'title' => '',
      'width' => 0,
      'height' => 0,
    );
    $field_settings['default_image'] = array(
      'uuid' => 1,
      'alt' => '',
      'title' => '',
      'width' => 0,
      'height' => 0,
    );
    $widget_settings = array(
      'preview_image_style' => 'medium',
    );
    $field = $this->createImageField('images', 'test_images', $storage_settings, $field_settings, $widget_settings);

    $field_id = $field->id();
    return $field_id;
  }

  /**
   * Upload an image to the node of type test_images and create the node.
   *
   * @param The image file $image
   *   A file object representing the image to upload.
   */
  function uploadImageFile($image) {
    $edit = array(
      'title[0][value]' => $this->randomMachineName(),
      'files[images_0]' => drupal_realpath($image->getFileUri()),
    );

    $this->drupalPostForm('node/add/test_images' , $edit, t('Save and publish'));

    // Add alt text.
    $this->drupalPostForm(NULL, ['images[0][alt]' => $this->randomMachineName()], t('Save and publish'));

    // Retrieve ID of the newly created node from the current URL.
    $matches = array();
    preg_match('/node\/([0-9]+)/', $this->getUrl(), $matches);
    return isset($matches[1]) ? $matches[1] : FALSE;
  }

  /**
   * Test to ensure explicit content is detected when Safe Search is enabled.
   */
  public function testSafeSearchConstraint() {
    //Get the image field id.
    $field_id = $this->getImageFieldId();

    //Enable the Safe Search.
    $edit = array(
      'safe_search' => 1,
    );
    $this->drupalPostForm("admin/structure/types/manage/test_images/fields/$field_id", $edit, t('Save settings'));

    //Ensure that the safe search is enabled.
    $this->drupalGet("admin/structure/types/manage/test_images/fields/$field_id");

    // Get an image with explicit content from web.
    $image = file_get_contents('http://www.menshealth.com/sites/menshealth.com/files/articles/2015/12/man-snoring.jpg'); // string
    $file = file_save_data($image, 'public://explicit.jpg', FILE_EXISTS_REPLACE);

    // Save the node.
    $node_id = $this->uploadImageFile($file);
    //Assert the constraint message.
    $this->assertText('This image contains explicit content and will not be saved.', 'Constraint message found');
    //Assert that the node is not saved.
    $this->assertFalse($node_id, 'The node has not been saved');
  }

  /**
   * Test to ensure no explicit content is detected when Safe Search is disabled.
   */
  public function testNoSafeSearchConstraint() {
    // Get the image field id.
    $field_id = $this->getImageFieldId();

    //Ensure that the safe search is disabled.
    $this->drupalGet("admin/structure/types/manage/test_images/fields/$field_id");

    // Get an image with explicit content from web.

    $image = file_get_contents('http://www.menshealth.com/sites/menshealth.com/files/articles/2015/12/man-snoring.jpg'); // string
    $file = file_save_data($image, 'public://explicit.jpg', FILE_EXISTS_REPLACE);

    // Save the node.
    $node_id = $this->uploadImageFile($file);
    //Assert the constraint message.
    $this->assertNoText('This image contains explicit content and will not be saved.', 'No Constraint message found');
    //Display the node.
    $this->drupalGet('node/' . $node_id);
  }
}
