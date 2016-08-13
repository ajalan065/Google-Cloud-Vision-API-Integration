<?php

namespace Drupal\google_vision\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Form\ConfigFormBase;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Configure site information settings for this site.
 */
class GoogleVisionSettingsForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'google_vision_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['google_vision.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('google_vision.settings');

    $form['api_key'] = [
      '#type' => 'textfield',
      '#title' => t('Google Vision API key'),
      '#required' => TRUE,
      '#description' => t(
        'To create API key <ol>
            <li>Visit <a href="@url">Google Console</a> and create a project to use.</li>
            <li>Enable the Cloud Vision API.</li>
            <li>Generate API key with type "Browser key" under the Credentials tab.</li></ol>',
        [
          '@url' => 'https://cloud.google.com/console'
        ]
      ),
      '#default_value' => $config->get('api_key')
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $this->config('google_vision.settings')
      ->set('api_key', $form_state->getValue('api_key'))
      ->save();

    parent::submitForm($form, $form_state);
  }

}
