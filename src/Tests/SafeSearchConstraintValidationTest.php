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
use Drupal\file\Entity\File;
use Drupal\file\FileInterface;
use Drupal\comment\Entity\CommentType;
use Drupal\comment\Entity\Comment;
use Drupal\comment\Plugin\Field\FieldType\CommentItemInterface;
use Drupal\comment\Tests\CommentTestTrait;
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
  public static $modules = [
    'google_vision',
    'node',
    'image',
    'field_ui',
    'field',
    'google_vision_test',
    'comment',
  ];

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
    $contentType = $this->drupalCreateContentType(array(
      'type' => 'test_images',
      'name' => 'Test Images'
    ));
    // Creates administrative user.
    $this->adminUser = $this->drupalCreateUser(array(
        'administer google vision',
        'create test_images content',
        'access content',
        'access administration pages',
        'administer node fields',
        'administer nodes',
        'administer node display',
        'administer comments',
        'administer comment types',
        'administer comment fields',
        'administer comment display',
        'access comments',
        'post comments',
      )
    );
    $this->drupalLogin($this->adminUser);
    //Check whether the api key is set.
    $this->drupalGet(Url::fromRoute('google_vision.settings'));
    $this->assertNotNull('api_key', 'The api key is set');
  }

  /**
   * Create a new image field.
   *
   * @param string $name
   *   The name of the new field (all lowercase), exclude the "field_" prefix.
   * @param string $entity_type
   *   The entity type that this field will be added to.
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
  public function createImageField($name, $entity_type, $type_name, $storage_settings = array(), $field_settings = array(), $widget_settings = array(), $formatter_settings = array(), $description = '') {
    FieldStorageConfig::create(array(
      'field_name' => $name,
      'entity_type' => $entity_type,
      'type' => 'image',
      'settings' => $storage_settings,
      'cardinality' => 1,
    ))->save();

    $field_config = FieldConfig::create([
      'field_name' => $name,
      'label' => $name,
      'entity_type' => $entity_type,
      'bundle' => $type_name,
      'settings' => $field_settings,
      'description' => $description,
    ])->addConstraint('SafeSearch');
    $field_config->save();

    entity_get_form_display($entity_type, $type_name, 'default')
      ->setComponent($name, array(
        'type' => 'image_image',
        'settings' => $widget_settings,
      ))
      ->save();

    entity_get_display($entity_type, $type_name, 'default')
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
  public function getImageFieldId($entity_type, $type) {
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
    $field = $this->createImageField('images', $entity_type, $type, $storage_settings, $field_settings, $widget_settings);

    $field_id = $field->id();
    return $field_id;
  }

  /**
   * Create a node of type test_images and also upload an image.
   */
  public function createNodeWithImage() {
    //Get an image.
    $images = $this->drupalGetTestFiles('image');

    $edit = array(
      'title[0][value]' => $this->randomMachineName(),
      'files[images_0]' => drupal_realpath($images[0]->uri),
    );

    $this->drupalPostForm('node/add/test_images', $edit, t('Save and publish'));

    // Add alt text.
    $this->drupalPostForm(NULL, ['images[0][alt]' => $this->randomMachineName()], t('Save and publish'));

    // Retrieve ID of the newly created node from the current URL.
    $matches = array();
    preg_match('/node\/([0-9]+)/', $this->getUrl(), $matches);
    return isset($matches[1]) ? $matches[1] : FALSE;
  }

  /**
   * Creates a comment comment type (bundle).
   *
   * @param string $label
   *   The comment type label.
   *
   * @return \Drupal\comment\Entity\CommentType
   *   Created comment type.
   */
  public function createCommentType($label) {
    $bundle = CommentType::create(array(
      'id' => $label,
      'label' => $label,
      'description' => '',
      'target_entity_type_id' => 'node',
    ));
    $bundle->save();
    return $bundle;
  }

  /**
   * Adds the default comment field to an entity.
   *
   * Attaches a comment field named 'comment' to the given entity type and
   * bundle. Largely replicates the default behavior in Drupal 7 and earlier.
   *
   * @param string $entity_type
   *   The entity type to attach the default comment field to.
   * @param string $bundle
   *   The bundle to attach the default comment field to.
   * @param string $field_name
   *   (optional) Field name to use for the comment field. Defaults to
   *     'comment'.
   * @param int $default_value
   *   (optional) Default value, one of CommentItemInterface::HIDDEN,
   *   CommentItemInterface::OPEN, CommentItemInterface::CLOSED. Defaults to
   *   CommentItemInterface::OPEN.
   * @param string $comment_type_id
   *   (optional) ID of comment type to use. Defaults to 'comment'.
   */
  public function addCommentField($entity_type, $bundle, $field_name = 'comment', $default_value = CommentItemInterface::OPEN, $comment_type_id = 'test_comment') {
    FieldStorageConfig::create(array(
      'entity_type' => $entity_type,
        'field_name' => $field_name,
        'type' => 'comment',
        'translatable' => TRUE,
        'settings' => array(
          'comment_type' => $comment_type_id,
        ),
      ))->save();

    $field_config = FieldConfig::create(array(
      'label' => 'Test Comments',
        'description' => '',
        'field_name' => $field_name,
        'entity_type' => $entity_type,
        'bundle' => $bundle,
        'required' => 1,
        'default_value' => array(
          array(
            'status' => $default_value,
            'cid' => 0,
            'last_comment_name' => '',
            'last_comment_timestamp' => 0,
            'last_comment_uid' => 0,
          ),
        ),
      ));
    $field_config->save();
    entity_get_form_display($entity_type, $bundle, 'default')
        ->setComponent($field_name, array(
          'type' => 'comment_default',
          'weight' => 20,
        ))
        ->save();
    entity_get_display($entity_type, $bundle, 'default')
        ->setComponent($field_name, array(
          'label' => 'above',
          'type' => 'comment_default',
          'weight' => 20,
        ))
        ->save();
  }

  /**
   * Create the comment with image field.
   *
   * @param integer $nid
   *   The id of the node.
   */
  public function createCommentWithImage($nid) {
    $images = $this->drupalGetTestFiles('image');

    $edit = array(
      'subject[0][value]' => $this->randomMachineName(),
      'files[images_0]' => drupal_realpath($images[0]->uri),
    );
    $this->drupalPostForm('node/' . $nid, $edit, t('Save'));
    $this->drupalPostForm(NULL, ['images[0][alt]' => $this->randomMachineName()], t('Save'));
  }

  /**
   * Test to ensure explicit content is detected when Safe Search is enabled.
   */
  public function testSafeSearchConstraintForNodes() {
    //Get the image field id.
    $field_id = $this->getImageFieldId('node', 'test_images');

    //Enable the Safe Search.
    $edit = array(
      'safe_search' => 1,
    );
    $this->drupalPostForm("admin/structure/types/manage/test_images/fields/$field_id", $edit, t('Save settings'));

    //Ensure that the safe search is enabled.
    $this->drupalGet("admin/structure/types/manage/test_images/fields/$field_id");

    // Save the node.
    $node_id = $this->createNodeWithImage();

    //Assert the constraint message.
    $this->assertText('This image contains explicit content and will not be saved.', 'Constraint message found');
    //Assert that the node is not saved.
    $this->assertFalse($node_id, 'The node has not been saved');
  }

  /**
   * Test to ensure no explicit content is detected when Safe Search is disabled.
   */
  public function testNoSafeSearchConstraintForNodes() {
    // Get the image field id.
    $field_id = $this->getImageFieldId('node', 'test_images');

    //Ensure that the safe search is disabled.
    $this->drupalGet("admin/structure/types/manage/test_images/fields/$field_id");

    // Save the node.
    $node_id = $this->createNodeWithImage();

    //Assert that no constraint message appears.
    $this->assertNoText('This image contains explicit content and will not be saved.', 'No Constraint message found');
    //Display the node.
    $this->drupalGet('node/' . $node_id);
  }

  /**
   * Test to ensure that explicit content is detected in comments when Safe Search enabled.
   */
  public function testSafeSearchConstraintForComments() {
    // Creating a new comment type.
    $type = $this->createCommentType('test_comment');

    //Ensure that the comment type is created and we get proper response.
    $this->drupalGet('admin/structure/comment/manage/' . $type->id());
    $this->assertResponse(200);

    //Get the field id of the image field added to the comment type.
    $field_id = $this->getImageFieldId('comment', $type->id());
    $this->drupalGet('admin/structure/comment/manage/' . $type->id() . '/fields');

    //Add the comment field to test_images.
    $this->addCommentField('node', 'test_images');

    $this->drupalGet("admin/structure/types/manage/test_images/fields");
    //Enable the safe search detection feature.
    $edit = array(
      'safe_search' => 1,
    );
    $this->drupalPostForm("admin/structure/comment/manage/test_comment/fields/$field_id", $edit, t('Save settings'));

    //Ensure that Safe Search is on.
    $this->drupalGet("admin/structure/comment/manage/test_comment/fields/$field_id");

    // Create and save a node.
    $this->getImageFieldId('node', 'test_images');
    $node_id = $this->createNodeWithImage();

    // Add a comment to the node.
    $this->createCommentWithImage($node_id);

    //Assert the constraint message.
    $this->assertText('This image contains explicit content and will not be saved.', 'Constraint message found');
  }

  /**
   * Test to ensure that explicit content is detected in comments when Safe Search disabled.
   */
  public function testNoSafeSearchConstraintForComments() {
    // Creating a new comment type.
    $type = $this->createCommentType('test_comment');

    //Ensure that the comment type is created and we get proper response.
    $this->drupalGet('admin/structure/comment/manage/' . $type->id());
    $this->assertResponse(200);

    //Get the field id of the image field added to the comment type.
    $field_id = $this->getImageFieldId('comment', $type->id());
    $this->drupalGet('admin/structure/comment/manage/' . $type->id() . '/fields');

    //Add the comment field to test_images.
    $this->addCommentField('node', 'test_images');
    $this->drupalGet("admin/structure/comment/manage/test_comment/fields/$field_id");

    // Create and save a node.
    $this->getImageFieldId('node', 'test_images');
    $node_id = $this->createNodeWithImage();

    // Add a comment to the node.
    $this->createCommentWithImage($node_id);

    //Assert the constraint message.
    $this->assertNoText('This image contains explicit content and will not be saved.', 'No Constraint message found');
    $this->drupalGet('node/' . $node_id);
  }
}
