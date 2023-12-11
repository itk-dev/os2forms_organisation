<?php

namespace Drupal\os2forms_organisation\Helper;

use Symfony\Component\HttpClient\CurlHttpClient;

/**
 * Organisation API helper class.
 */
class OrganisationApiHelper {

  /**
   * Curl Client.
   *
   * @var \Symfony\Component\HttpClient\CurlHttpClient
   */
  private ?CurlHttpClient $client = NULL;

  /**
   * The Settings.
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
   * Get curl client.
   */
  private function getCurlClient(): CurlHttpClient {
    if ($this->client) {
      return $this->client;
    }
    else {
      $this->client = new CurlHttpClient();

      return $this->client;
    }
  }

  /**
   * Get bruger informationer for bruger.
   *
   * @phpstan-return array<string, mixed>
   */
  public function getBrugerInformationer(string $brugerId): array {
    $client = $this->getCurlClient();

    $this->settings->getOrganisationApiEndpoint();

    $response = $client->request('GET', $this->settings->getOrganisationApiEndpoint() . 'bruger/' . $brugerId);

    if (200 === $response->getStatusCode()) {
      return $response->toArray();
    }
    else {
      return [];
    }
  }

  /**
   * Get funktion informationer for bruger.
   *
   * @phpstan-return array<string, mixed>
   */
  public function getFunktionInformationer(string $brugerId): array {
    $client = $this->getCurlClient();
    $this->settings->getOrganisationApiEndpoint();

    $response = $client->request('GET', $this->settings->getOrganisationApiEndpoint() . 'bruger/' . $brugerId . '/funktioner');

    if (200 === $response->getStatusCode()) {

      $responseArray = $response->toArray();

      $funktioner = $responseArray['hydra:member'] ?? [];

      $result = [];
      foreach ($funktioner as $funktion) {
        $result[$funktion['id']] = $funktion;
      }

      return $result;
    }
    else {
      return [];
    }
  }

  /**
   * Get organisation path for funktion.
   *
   * @phpstan-return array<string, mixed>
   */
  public function getOrganisationPath(string $funktionsId): array {
    $client = $this->getCurlClient();

    $response = $client->request('GET', $this->settings->getOrganisationApiEndpoint() . 'funktion/' . $funktionsId . '/organisation-path');

    if (200 === $response->getStatusCode()) {

      $responseArray = $response->toArray();

      return $responseArray['hydra:member'] ?? [];
    }
    else {
      return [];
    }
  }

  /**
   * Get manager information for bruger.
   *
   * @phpstan-return array<string, mixed>
   */
  public function getManagerInformation(string $brugerId): array {
    $client = $this->getCurlClient();

    $response = $client->request('GET', $this->settings->getOrganisationApiEndpoint() . 'bruger/' . $brugerId . '/leder');

    if (200 === $response->getStatusCode()) {

      $managerIds = $response->toArray()['hydra:member'];

      // Select first manager if more than one.
      if (count($managerIds) >= 1) {
        $managerId = reset($managerIds);
      }
      else {
        return [];
      }

      return $this->getBrugerInformationer($managerId);
    }
    else {
      return [];
    }
  }

  /**
   * Get (first) manager id for bruger.
   */
  public function getManagerId(string $brugerId): string {
    $client = $this->getCurlClient();

    $response = $client->request('GET', $this->settings->getOrganisationApiEndpoint() . 'bruger/' . $brugerId . '/leder');

    if (200 === $response->getStatusCode()) {

      $managerIds = $response->toArray()['hydra:member'];

      // Select first manager if more than one.
      if (count($managerIds) >= 1) {
        return reset($managerIds);
      }
      else {
        return '';
      }

    }
    else {
      return '';
    }
  }

  /**
   * Search for bruger.
   *
   * @phpstan-return array<string, mixed>
   */
  public function searchBruger(string $query): array {
    $client = $this->getCurlClient();

    $this->settings->getOrganisationApiEndpoint();

    $response = $client->request('GET', $this->settings->getOrganisationApiEndpoint() . 'bruger?page=1&navn=' . $query);

    if (200 === $response->getStatusCode()) {
      return $response->toArray()['hydra:member'];
    }
    else {
      return [];
    }
  }

}
