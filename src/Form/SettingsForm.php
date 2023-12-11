<?php

namespace Drupal\os2forms_organisation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\os2forms_organisation\Helper\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface as OptionsResolverException;

/**
 * Organisation settings form.
 */
final class SettingsForm extends FormBase {
  use StringTranslationTrait;

  public const ORGANISATION_BASE_API_ENDPOINT = 'organisation_base_api_endpoint';
  public const TEST_MODE = 'test_mode';

  /**
   * The settings.
   *
   * @var \Drupal\os2forms_organisation\Helper\Settings
   */
  private Settings $settings;

  /**
   * Constructor.
   */
  public function __construct(Settings $settings) {
    $this->settings = $settings;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): SettingsForm {
    return new static(
      $container->get(Settings::class),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2forms_organisation_settings';
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   * @phpstan-return array<string, mixed>
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {

    $organisationApiEndpoint = $this->settings->getOrganisationApiEndpoint();
    $form[self::ORGANISATION_BASE_API_ENDPOINT] = [
      '#type' => 'textfield',
      '#title' => $this->t('Organisation base API endpoint'),
      '#description' => $this->t('Endpoint to local docker container, i.e. http://host.docker.internal:PORT/api/v1/'),
      '#required' => TRUE,
      '#default_value' => !empty($organisationApiEndpoint) ? $organisationApiEndpoint : NULL,
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save settings'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   */
  public function submitForm(array &$form, FormStateInterface $formState): void {

    try {
      $settings[self::ORGANISATION_BASE_API_ENDPOINT] = $formState->getValue(self::ORGANISATION_BASE_API_ENDPOINT);

      $this->settings->setSettings($settings);
      $this->messenger()->addStatus($this->t('Settings saved'));
    }
    catch (OptionsResolverException $exception) {
      $this->messenger()->addError($this->t('Settings not saved (@message)', ['@message' => $exception->getMessage()]));
    }

    $this->messenger()->addStatus($this->t('Settings saved'));

  }

}
