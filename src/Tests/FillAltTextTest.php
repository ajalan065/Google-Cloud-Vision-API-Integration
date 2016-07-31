<?php

namespace Drupal\google_vision\Tests;

use Drupal\Core\Url;
use Drupal\simpletest\WebTestBase;
use Drupal\file_entity\Entity\FileEntity;

/**
 * Test to verify whether the Alt Text field of the image file gets correctly
 * filled or not.
 *
 * @group google_vision
 */
class FillAltTextTest extends WebTestBase {

  /**
   * {@inheritdoc}
   */
  public static $modules = [
    'google_vision',
    'image',
    'field_ui',
    'field',
    'file',
    'google_vision_test',
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

    // Creates administrative user.
    $this->adminUser = $this->drupalCreateUser([
      'administer google vision',
      'administer files',
      'edit any image files',
      'create files',
      'administer file types',
      'administer file fields',
    ]);
    $this->drupalLogin($this->adminUser);
    //Check whether the api key is set.
    $this->drupalGet(Url::fromRoute('google_vision.settings'));
    $this->assertNotNull('api_key', 'The api key is set');
  }

  /**
   * Uploads an image file and saves it.
   *
   * @return integer
   *  The file id of the newly created image file.
   */
  public function uploadImageFile() {
    $images = $this->drupalGetTestFiles('image');
    $edit = [
      'files[upload]' => \Drupal::service('file_system')
        ->realpath($images[0]->uri),
    ];
    $this->drupalPostForm('file/add', $edit, t('Next'));
    $this->drupalPostForm(NULL, array(), t('Next'));
    $this->drupalPostForm(NULL, array(), t('Save'));
    return (int) db_query('SELECT MAX(fid) FROM {file_managed}')->fetchField();
  }

  /**
   * Test to ensure that the Alt Text remains empty by default.
   */
  public function testNoOptionSelected() {
    // Create an image file.
    $file_id = $this->uploadImageFile();
    // Ensure that the Alt Text is empty.
    $this->drupalGet('file/' . $file_id . '/edit');
    $this->assertFieldByName('field_image_alt_text[0][value]', '', 'Alt Text is empty');
  }

  /**
   * Test to ensure that Label Detection feature fills the Alt Text correctly.
   */
  public function testFillAltTextByLabels() {
    // Set the Label Detection option to fill the alt text.
    $edit = [
      'alt_auto_filling' => 'labels'
    ];
    $this->drupalPostForm('admin/structure/file-types/manage/image/edit/fields/file.image.field_image_alt_text', $edit, t('Save settings'));
    //Ensure that Label Detection option is set.
    $this->drupalGet('admin/structure/file-types/manage/image/edit/fields/file.image.field_image_alt_text');
    // Create an image file.
    $file_id = $this->uploadImageFile();
    //Ensure that the Alt Text is filled properly.
    $this->drupalGet('file/' . $file_id . '/edit');
    $this->assertFieldByName('field_image_alt_text[0][value]', 'This will be filled with Labels.', 'Alt Text is properly filled with Labels');
  }

  /**
   * Test to ensure that Landmark Detection feature fills the Alt Text correctly.
   */
  public function testFillAltTextByLandmark() {
    // Set the Landmark Detection option to fill the alt text.
    $edit = [
      'alt_auto_filling' => 'landmark'
    ];
    $this->drupalPostForm('admin/structure/file-types/manage/image/edit/fields/file.image.field_image_alt_text', $edit, t('Save settings'));
    //Ensure that Landmark Detection option is set.
    $this->drupalGet('admin/structure/file-types/manage/image/edit/fields/file.image.field_image_alt_text');
    // Create an image file.
    $file_id = $this->uploadImageFile();
    //Ensure that the Alt Text is filled properly.
    $this->drupalGet('file/' . $file_id . '/edit');
    $this->assertFieldByName('field_image_alt_text[0][value]', 'This will be filled with Landmarks.', 'Alt Text is properly set with Landmarks');
  }

  /**
   * Test to ensure that Logo Detection feature fills the Alt Text correctly.
   */
  public function testFillAltTextByLogo() {
    // Set the Logo Detection option to fill the alt text.
    $edit = [
      'alt_auto_filling' => 'logo'
    ];
    $this->drupalPostForm('admin/structure/file-types/manage/image/edit/fields/file.image.field_image_alt_text', $edit, t('Save settings'));
    //Ensure that Logo Detection option is set.
    $this->drupalGet('admin/structure/file-types/manage/image/edit/fields/file.image.field_image_alt_text');
    // Create an image file.
    $file_id = $this->uploadImageFile();
    //Ensure that the Alt Text is filled properly.
    $this->drupalGet('file/' . $file_id . '/edit');
    $this->assertFieldByName('field_image_alt_text[0][value]', 'This will be filled with Logos.', 'Alt Text is properly set with Logos');
  }

  /**
   * Test to ensure that Optical Character Detection feature fills the Alt Text correctly.
   */
  public function testFillAltTextByOCR() {
    // Set the Optical Character Detection option to fill the alt text.
    $edit = [
      'alt_auto_filling' => 'ocr'
    ];
    $this->drupalPostForm('admin/structure/file-types/manage/image/edit/fields/file.image.field_image_alt_text', $edit, t('Save settings'));
    //Ensure that Optical Character Detection option is set.
    $this->drupalGet('admin/structure/file-types/manage/image/edit/fields/file.image.field_image_alt_text');
    // Create an image file.
    $file_id = $this->uploadImageFile();
    //Ensure that the Alt Text is filled properly.
    $this->drupalGet('file/' . $file_id . '/edit');
    $this->assertFieldByName('field_image_alt_text[0][value]', 'This will be filled with Optical Characters.', 'Alt Text is properly set with Optical Characters');
  }

  /**
   * Test to ensure that Alt Text is empty when "None" is selected.
   */
  public function testFillAltTextByNone() {
    // Set the None option to fill the alt text.
    $edit = [
      'alt_auto_filling' => 'none'
    ];
    $this->drupalPostForm('admin/structure/file-types/manage/image/edit/fields/file.image.field_image_alt_text', $edit, t('Save settings'));
    //Ensure that None option is set.
    $this->drupalGet('admin/structure/file-types/manage/image/edit/fields/file.image.field_image_alt_text');
    // Create an image file.
    $file_id = $this->uploadImageFile();
    //Ensure that the Alt Text is empty.
    $this->drupalGet('file/' . $file_id . '/edit');
    $this->assertFieldByName('field_image_alt_text[0][value]', '', 'Alt Text is empty');
  }
}
