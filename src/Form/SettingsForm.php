<?php

namespace Drupal\os2forms_organisation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\os2forms_organisation\Helper\OrganisationApiHelper;
use Drupal\os2forms_organisation\Helper\Settings;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\OptionsResolver\Exception\ExceptionInterface as OptionsResolverException;

/**
 * Organisation settings form.
 */
final class SettingsForm extends FormBase {
  use StringTranslationTrait;

  public const ORGANISATION_API_ENDPOINT = 'organisation_api_endpoint';

  /**
   * The settings.
   *
   * @var \Drupal\os2forms_organisation\Helper\Settings
   */
  private Settings $settings;

  /**
   * Organisation API helper.
   *
   * @var \Drupal\os2forms_organisation\Helper\OrganisationApiHelper
   */
  private OrganisationApiHelper $helper;

  /**
   * Constructor.
   */
  public function __construct(Settings $settings, OrganisationApiHelper $helper) {
    $this->settings = $settings;
    $this->helper = $helper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): SettingsForm {
    return new static(
      $container->get(Settings::class),
      $container->get(OrganisationApiHelper::class),
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
    $form[self::ORGANISATION_API_ENDPOINT] = [
      '#type' => 'textfield',
      '#title' => $this->t('Organisation API endpoint'),
      '#required' => TRUE,
      '#default_value' => !empty($organisationApiEndpoint) ? $organisationApiEndpoint : NULL,
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save settings'),
    ];

    $form['actions']['testApi'] = [
      '#type' => 'submit',
      '#name' => 'testApi',
      '#value' => $this->t('Test API'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   */
  public function submitForm(array &$form, FormStateInterface $formState): void {
    $triggeringElement = $formState->getTriggeringElement();
    if ('testApi' === ($triggeringElement['#name'] ?? NULL)) {
      $this->testApi();
      return;
    }

    try {
      $settings[self::ORGANISATION_API_ENDPOINT] = $formState->getValue(self::ORGANISATION_API_ENDPOINT);

      $this->settings->setSettings($settings);
      $this->messenger()->addStatus($this->t('Settings saved'));
    }
    catch (OptionsResolverException $exception) {
      $this->messenger()->addError($this->t('Settings not saved (@message)', ['@message' => $exception->getMessage()]));
    }

    $this->messenger()->addStatus($this->t('Settings saved'));

  }

  /**
   * Test certificate.
   */
  private function testApi(): void {
    try {
      $this->helper->searchBruger('Admin Jensen', TRUE);
      $this->messenger()->addStatus($this->t('API successfully tested'));
    }
    catch (\Exception $exception) {
      $message = $this->t('Error testing API: %message', ['%message' => $exception->getMessage()]);
      $this->messenger()->addError($message);
    }
  }

}
