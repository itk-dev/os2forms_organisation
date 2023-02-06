<?php

namespace Drupal\os2forms_organisation\Helper;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\os2forms_organisation\Exception\InvalidSettingException;
use Drupal\os2forms_organisation\Form\SettingsForm;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * General settings for os2forms_organisation.
 */
final class Settings {
  /**
   * The store.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueStoreInterface
   */
  private KeyValueStoreInterface $store;

  /**
   * The key value collection name.
   *
   * @var string
   */
  private $collection = 'os2forms_organisation.';

  /**
   * The constructor.
   */
  public function __construct(KeyValueFactoryInterface $keyValueFactory) {
    $this->store = $keyValueFactory->get($this->collection);
  }

  /**
   * Get test mode.
   */
  public function getTestMode(): bool {
    return (bool) $this->get(SettingsForm::TEST_MODE, TRUE);
  }

  /**
   * Get authority cvr.
   */
  public function getAuthorityCVR(): string {
    return $this->get(SettingsForm::AUTHORITY_CVR, '');
  }

  /**
   * Get certificate.
   */
  public function getCertificate(): array {
    $value = $this->get(SettingsForm::CERTIFICATE);
    return is_array($value) ? $value : [];
  }

  /**
   * Get cache expiration.
   */
  public function getCacheExpiration(): string {
    return $this->get(SettingsForm::CACHE_EXPIRATION, '');
  }

  /**
   * Get organisation service endpoint.
   */
  public function getOrganisationServiceEndpoint(): string {
    return $this->get(SettingsForm::ORGANISATION_SERVICE_ENDPOINT_REFERENCE, '');
  }

  /**
   * Get organisation test manager role id.
   */
  public function getOrganisationTestManagerRoleId(): string {
    return $this->get(SettingsForm::ORGANISATION_TEST_LEDER_ROLLE_UUID, '');
  }

  /**
   * Get organisation production manager role id.
   */
  public function getOrganisationProductionManagerRoleId(): string {
    return $this->get(SettingsForm::ORGANISATION_PROD_LEDER_ROLLE_UUID, '');
  }

  /**
   * Get a setting value.
   *
   * @param string $key
   *   The key.
   * @param mixed|null $default
   *   The default value.
   *
   * @return mixed
   *   The setting value.
   */
  private function get(string $key, $default = NULL) {
    $resolver = $this->getSettingsResolver();
    if (!$resolver->isDefined($key)) {
      throw new InvalidSettingException(sprintf('Setting %s is not defined', $key));
    }

    return $this->store->get($key, $default);
  }

  /**
   * Set settings.
   *
   * @throws \Symfony\Component\OptionsResolver\Exception\ExceptionInterface
   *
   * @phpstan-param array<string, mixed> $settings
   */
  public function setSettings(array $settings): self {
    $settings = $this->getSettingsResolver()->resolve($settings);
    foreach ($settings as $key => $value) {
      $this->store->set($key, $value);
    }

    return $this;
  }

  /**
   * Get settings resolver.
   */
  private function getSettingsResolver(): OptionsResolver {
    return (new OptionsResolver())
      ->setDefaults([
        SettingsForm::TEST_MODE => TRUE,
        SettingsForm::AUTHORITY_CVR => '',
        SettingsForm::CERTIFICATE => [],
        SettingsForm::CACHE_EXPIRATION => '',
        SettingsForm::ORGANISATION_SERVICE_ENDPOINT_REFERENCE => '',
        SettingsForm::ORGANISATION_TEST_LEDER_ROLLE_UUID => '',
        SettingsForm::ORGANISATION_PROD_LEDER_ROLLE_UUID => '',
      ]);
  }

}
