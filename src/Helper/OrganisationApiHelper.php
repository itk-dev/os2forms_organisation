<?php

namespace Drupal\os2forms_organisation\Helper;

use Drupal\os2forms_organisation\Exception\ApiException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Organisation API helper class.
 */
class OrganisationApiHelper {

  /**
   * The HTTP client.
   *
   * @var \GuzzleHttp\ClientInterface
   */
  private ClientInterface $httpClient;

  /**
   * The Settings.
   *
   * @var \Drupal\os2forms_organisation\Helper\Settings
   */
  private Settings $settings;

  /**
   * Constructor.
   */
  public function __construct(Settings $settings, ClientInterface $httpClient) {
    $this->settings = $settings;
    $this->httpClient = $httpClient;
  }

  /**
   * Get bruger informationer for bruger.
   *
   * @phpstan-return array<string, mixed>
   */
  public function getBrugerInformationer(string $brugerId): ?array {
    try {
      $response = $this->get('bruger/' . $brugerId);

      if (Response::HTTP_OK != $response->getStatusCode()) {
        return [];
      }

      return $this->getResponseContentsAsArray($response);

    }
    catch (ApiException $e) {
      return NULL;
    }
  }

  /**
   * Get funktion informationer for bruger.
   *
   * @phpstan-return array<string, mixed>
   */
  public function getFunktionInformationer(string $brugerId): ?array {
    try {
      $response = $this->get('bruger/' . $brugerId . '/funktioner');

      if (Response::HTTP_OK != $response->getStatusCode()) {
        return [];
      }

      $funktioner = $this->getResponseContentsAsArray($response)['hydra:member'] ?? [];

      $result = [];

      foreach ($funktioner as $funktion) {
        $result[$funktion['id']] = $funktion;
      }

      return $result;

    }
    catch (ApiException $e) {
      return NULL;
    }
  }

  /**
   * Get organisation path for funktion.
   *
   * @phpstan-return array<string, mixed>
   */
  public function getOrganisationPath(string $funktionsId): ?array {
    try {
      $response = $this->get('funktion/' . $funktionsId . '/organisation-path');

      if (Response::HTTP_OK != $response->getStatusCode()) {
        return [];
      }

      $responseArray = $this->getResponseContentsAsArray($response);

      return $responseArray['hydra:member'] ?? [];

    }
    catch (ApiException $e) {
      return NULL;
    }
  }

  /**
   * Get manager information for bruger.
   *
   * @phpstan-return array<string, mixed>
   */
  public function getManagerInformation(string $brugerId): ?array {
    try {
      $response = $this->get('bruger/' . $brugerId . '/leder');

      if (Response::HTTP_OK != $response->getStatusCode()) {
        return [];
      }

      $managerIds = $this->getResponseContentsAsArray($response)['hydra:member'] ?? [];

      // Select first manager if more than one.
      if (count($managerIds) >= 1) {
        $managerId = reset($managerIds);
      }
      else {
        return [];
      }

      return $this->getBrugerInformationer($managerId);

    }
    catch (ApiException $e) {
      return NULL;
    }
  }

  /**
   * Get (first) manager id for bruger.
   */
  public function getManagerId(string $brugerId): ?string {
    try {
      $response = $this->get('bruger/' . $brugerId . '/leder');

      if (Response::HTTP_OK != $response->getStatusCode()) {
        return '';
      }

      $managerIds = $this->getResponseContentsAsArray($response)['hydra:member'] ?? [];

      // Select first manager if more than one.
      if (count($managerIds) >= 1) {
        return reset($managerIds);
      }

      return '';

    }
    catch (ApiException $e) {
      return NULL;
    }
  }

  /**
   * Search for bruger.
   *
   * @phpstan-return array<string, mixed>
   */
  public function searchBruger(string $query): ?array {
    try {
      $response = $this->get('bruger?page=1&navn=' . $query);

      if (Response::HTTP_OK != $response->getStatusCode()) {
        return [];
      }

      return $this->getResponseContentsAsArray($response)['hydra:member'] ?? [];

    }
    catch (ApiException $e) {
      return NULL;
    }

  }

  /**
   * Do get request.
   */
  private function get(string $path): ResponseInterface {
    try {
      return $this->httpClient->request('GET', $this->settings->getOrganisationApiEndpoint() . $path);
    }
    catch (GuzzleException $e) {
      throw new ApiException($e->getMessage());
    }
  }

  /**
   * Get response contents as array.
   */
  private function getResponseContentsAsArray(ResponseInterface $response): array {
    return json_decode($response->getBody()->getContents(), TRUE);
  }

}
