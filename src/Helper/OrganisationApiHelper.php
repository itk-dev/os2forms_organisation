<?php

namespace Drupal\os2forms_organisation\Helper;

use Drupal\os2forms_organisation\Exception\ApiException;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

/**
 * Organisation API helper class.
 */
class OrganisationApiHelper {

  /**
   * Constructor.
   */
  public function __construct(private readonly Settings $settings, private readonly ClientInterface $httpClient) {
  }

  /**
   * Get bruger informationer for bruger.
   *
   * @phpstan-return array<string, mixed>
   *
   * @throws \Drupal\os2forms_organisation\Exception\ApiException
   *   API exception.
   */
  public function getBrugerInformationer(string $brugerId): array {
    $response = $this->get('bruger/' . $brugerId);

    return $this->getResponseContentsAsArray($response);
  }

  /**
   * Get funktion informationer for bruger.
   *
   * @phpstan-return array<string, mixed>
   *
   * @throws \Drupal\os2forms_organisation\Exception\ApiException
   *   API exception.
   */
  public function getFunktionInformationer(string $brugerId, bool $findManagerIds = false): array {
    $path = $findManagerIds ? 'bruger/' . $brugerId . '/leder-funktioner' : 'bruger/' . $brugerId . '/funktioner';
    $response = $this->get($path);

    $funktioner = $this->getResponseContentsAsArray($response)['hydra:member'] ?? [];

    $result = [];

    foreach ($funktioner as $funktion) {
      $result[$funktion['id']] = $funktion;
    }

    return $result;
  }

  /**
   * Get organisation path for funktion.
   *
   * @phpstan-return array<string, mixed>
   *
   * @throws \Drupal\os2forms_organisation\Exception\ApiException
   *   API exception.
   */
  public function getOrganisationPath(string $funktionsId): array {
    $response = $this->get('funktion/' . $funktionsId . '/organisation-path');

    $responseArray = $this->getResponseContentsAsArray($response);

    return $responseArray['hydra:member'] ?? [];
  }

  /**
   * Get manager information for bruger.
   *
   * @phpstan-return array<string, mixed>
   *
   * @throws \Drupal\os2forms_organisation\Exception\ApiException
   *   API exception.
   */
  public function getManagerInformation(string $brugerId): array {
    $response = $this->get('bruger/' . $brugerId . '/leder');

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

  /**
   * Get (first) manager id for bruger.
   *
   * @throws \Drupal\os2forms_organisation\Exception\ApiException
   *   API exception.
   */
  public function getManagerId(string $brugerId): string {
    $response = $this->get('bruger/' . $brugerId . '/leder');

    $managerIds = $this->getResponseContentsAsArray($response)['hydra:member'] ?? [];

    // Select first manager if more than one.
    if (count($managerIds) >= 1) {
      return reset($managerIds);
    }

    return '';
  }

  /**
   * Search for bruger.
   *
   * @phpstan-return array<string, mixed>
   *
   * @throws \Drupal\os2forms_organisation\Exception\ApiException
   *   API exception.
   */
  public function searchBruger(string $query): array {
    $response = $this->get('bruger', [
      'query' => [
        'page' => 1,
        'navn' => $query,
      ],
    ]);

    return $this->getResponseContentsAsArray($response)['hydra:member'] ?? [];
  }

  /**
   * Do get request.
   *
   * @phpstan-param array<string, mixed> $options
   *
   * @throws \Drupal\os2forms_organisation\Exception\ApiException
   *   API exception.
   */
  private function get(string $path, array $options = []): ResponseInterface {
    try {
      return $this->httpClient->request('GET', $this->settings->getOrganisationApiEndpoint() . $path, $options);
    }
    catch (GuzzleException $e) {
      throw new ApiException($e->getMessage(), $e->getCode(), $e);
    }
  }

  /**
   * Get response contents as array.
   *
   * @phpstan-return array<string, mixed>
   */
  private function getResponseContentsAsArray(ResponseInterface $response): array {
    return json_decode($response->getBody()->getContents(), TRUE);
  }

}
