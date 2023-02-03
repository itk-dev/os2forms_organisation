<?php

namespace Drupal\os2forms_organisation\Helper;

use Drupal\Core\KeyValueStore\KeyValueFactoryInterface;
use Drupal\Core\KeyValueStore\KeyValueStoreInterface;
use Drupal\os2forms_organisation\Exception\InvalidSettingException;
use Drupal\os2forms_organisation\Form\SettingsForm;

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
   * The setting keys.
   *
   * @var array|string[]
   */
  private array $keys = [
    SettingsForm::TEST_MODE,
    SettingsForm::AUTHORITY_CVR,
    SettingsForm::CERTIFICATE,
    SettingsForm::CACHE_EXPIRATION,
    SettingsForm::ORGANISATION_SERVICE_ENDPOINT_REFERENCE,
    SettingsForm::ORGANISATION_TEST_LEDER_ROLLE_UUID,
    SettingsForm::ORGANISATION_PROD_LEDER_ROLLE_UUID,
  ];

  /**
   * The constructor.
   */
  public function __construct(KeyValueFactoryInterface $keyValueFactory) {
    $this->store = $keyValueFactory->get($this->collection);
  }

  /**
   * Gets all settings.
   *
   * @phpstan-return array<string, mixed>
   */
  public function getAll(): array {
    $values = $this->store->getMultiple(array_map([$this, 'buildKey'], $this->keys));

    $vals = [];
    foreach ($values as $key => $value) {
      $vals[$this->unbuildKey($key)] = $value;
    }

    return $vals;
  }

  /**
   * Get setting keys.
   *
   * @phpstan-return array<int, mixed>
   */
  public function getKeys(): array {
    return $this->keys;
  }

  /**
   * Get setting.
   *
   * @phpstan-param mixed $default
   * @phpstan-return mixed
   */
  public function get(string $key, $default = NULL) {
    return $this->store->get($this->buildKey($key), $default);
  }

  /**
   * Set setting.
   *
   * @phpstan-param mixed $value
   */
  public function set(string $key, $value): void {
    $this->store->set($this->buildKey($key), $value);
  }

  /**
   * Build key.
   */
  private function buildKey(string $key): string {
    if (!in_array($key, $this->keys, TRUE)) {
      throw new InvalidSettingException(sprintf('Invalid setting: %s', $key));
    }
    return $this->collection . $key;
  }

  /**
   * Unbuild key.
   */
  private function unbuildKey(string $key): string {
    return 0 === strpos($key, $this->collection) ? substr($key, strlen($this->collection)) : $key;
  }

}
