<?php

namespace Drupal\os2forms_organisation\Helper;

use Drupal\os2forms_organisation\Exception\InvalidArgumentException;
use ItkDev\Serviceplatformen\Service\SF1500\AdresseService;
use ItkDev\Serviceplatformen\Service\SF1500\BrugerService;
use ItkDev\Serviceplatformen\Service\SF1500\Model\Bruger;
use ItkDev\Serviceplatformen\Service\SF1500\PersonService;
use ItkDev\Serviceplatformen\Service\SF1500\SF1500;
use ItkDev\Serviceplatformen\Service\SF1514\SF1514;
use ItkDev\Serviceplatformen\Service\SoapClient;

/**
 * Organisation Helper service.
 */
class OrganisationHelper {

  /**
   * The Settings.
   *
   * @var \Drupal\os2forms_organisation\Helper\Settings
   */
  private Settings $settings;

  /**
   * The Certificate locator.
   *
   * @var \Drupal\os2forms_organisation\Helper\CertificateLocatorHelper
   */
  private CertificateLocatorHelper $certificateLocator;

  /**
   * The SF1500 service.
   *
   * @var \ItkDev\Serviceplatformen\Service\SF1500\SF1500
   */
  private ?SF1500 $sf1500 = NULL;

  /**
   * Constructor.
   */
  public function __construct(CertificateLocatorHelper $certificateLocator, Settings $settings) {
    $this->certificateLocator = $certificateLocator;
    $this->settings = $settings;
  }

  /**
   * Gets SF1500 Service.
   */
  // phpcs:ignore
  private function getSF1500(): SF1500 {
    if (NULL === $this->sf1500) {
      $soapClient = new SoapClient([
        'cache_expiration_time' => explode(PHP_EOL, $this->settings->getCacheExpiration()),
      ]);

      $options = [
        'certificate_locator' => $this->certificateLocator->getCertificateLocator(),
        'authority_cvr' => $this->settings->getAuthorityCvr(),
        'sts_applies_to' => $this->settings->getOrganisationServiceEndpoint(),
        'test_mode' => $this->settings->getTestMode(),
      ];

      $sf1514 = new SF1514($soapClient, $options);

      unset($options['sts_applies_to']);

      $this->sf1500 = new SF1500($sf1514, $options);
    }

    return $this->sf1500;
  }

  /**
   * Gets Person name from bruger id.
   */
  public function getPersonName(string $brugerId): string {
    return $this->getSF1500()->getPersonName($brugerId);
  }

  /**
   * Gets Person Email from bruger id.
   */
  public function getPersonEmail(string $brugerId): string {
    return $this->getSF1500()->getPersonEmail($brugerId);
  }

  /**
   * Gets Person AZ ident from bruger id.
   */
  // phpcs:ignore
  public function getPersonAZIdent(string $brugerId): string {
    return $this->getSF1500()->getPersonAZIdent($brugerId);
  }

  /**
   * Gets Person Phone from bruger id.
   */
  public function getPersonPhone(string $brugerId): string {
    return $this->getSF1500()->getPersonPhone($brugerId);
  }

  /**
   * Gets Person Location from bruger id.
   */
  public function getPersonLocation(string $brugerId): string {
    return $this->getSF1500()->getPersonLocation($brugerId);
  }

  /**
   * Gets Organisation Funktioner from bruger id.
   *
   * @phpstan-return array<int, mixed>
   */
  public function getOrganisationFunktioner(string $brugerId): array {
    return $this->getSF1500()->getOrganisationFunktionerFromUserId($brugerId);
  }

  /**
   * Gets Organisation Funktionsnavn from funktions id.
   */
  public function getFunktionsNavn(string $funktionsId): string {
    return $this->getSF1500()->getFunktionsNavn($funktionsId);
  }

  /**
   * Gets Organisation Endhed from funktions id.
   */
  public function getOrganisationEnhed(string $funktionsId): string {
    return $this->getSF1500()->getOrganisationEnhed($funktionsId);
  }

  /**
   * Gets Organisation Address from funktions id.
   */
  public function getOrganisationAddress(string $funktionsId): string {
    return $this->getSF1500()->getOrganisationAddress($funktionsId);
  }

  /**
   * Gets Organisation Enhed Niveau To from funktions id.
   */
  public function getOrganisationEnhedNiveauTo(string $funktionsId): string {
    return $this->getSF1500()->getOrganisationEnhedNiveauTo($funktionsId);
  }

  /**
   * Gets Person Magistrat from funktions id.
   */
  public function getPersonMagistrat(string $funktionsId): string {
    return $this->getSF1500()->getPersonMagistrat($funktionsId);
  }

  /**
   * Gets manager info for user id.
   *
   * @phpstan-return array<int, mixed>
   */
  public function getManagerInfo(string $userId): array {

    $managerFunktionsTypeId =
      $this->settings->getTestMode()
        ? $this->settings->getOrganisationTestManagerRoleId()
        : $this->settings->getOrganisationProductionManagerRoleId();

    return $this->getSF1500()->getManagerBrugerAndFunktionsIdFromUserId($userId, $managerFunktionsTypeId);
  }

  /**
   * Search for adresser.
   *
   * @param array $query
   *   The search query.
   *
   * @return \ItkDev\Serviceplatformen\Service\SF1500\Model\Adresse[]
   *   The list of results.
   *
   * @phpstan-param array<string, mixed> $query
   * @phpstan-return array<string, mixed>
   */
  public function searchAdresse(array $query): array {
    /** @var \ItkDev\Serviceplatformen\Service\SF1500\AdresseService $service */
    $service = $this->getSF1500()->getService(AdresseService::class);
    $result = $service->soeg($query);

    return $result;
  }

  /**
   * Search for persons.
   *
   * @param array $query
   *   The search query.
   *
   * @return \Digitaliseringskataloget\SF1500\Organisation6\Person\Person[]
   *   The list of results.
   *
   * @phpstan-param array<string, mixed> $query
   * @phpstan-return array<string, mixed>
   */
  public function searchBruger(array $query): array {
    /** @var \ItkDev\Serviceplatformen\Service\SF1500\BrugerService $service */
    $service = $this->getSF1500()->getService(BrugerService::class);
    $result = $service->soeg($query, [
      Bruger::FIELD_EMAIL,
      Bruger::FIELD_MOBILTELEFON,
      Bruger::FIELD_LOKATION,
    ]);

    return $result;
  }

  /**
   * Search for persons.
   *
   * @param array $query
   *   The search query.
   *
   * @return \Digitaliseringskataloget\SF1500\Organisation6\Person\Person[]
   *   The list of results.
   *
   * @phpstan-param array<string, mixed> $query
   * @phpstan-return array<string, mixed>
   */
  public function searchPerson(array $query): array {
    /** @var \ItkDev\Serviceplatformen\Service\SF1500\PersonService $service */
    $service = $this->getSF1500()->getService(PersonService::class);
    $result = $service->soeg($query);

    return $result;
  }

  /**
   * Do search.
   *
   * @phpstan-param array<string, mixed> $query
   * @phpstan-return array<string, mixed>
   */
  public function search(string $type, array $query): array {
    switch ($type) {
      case 'adresse':
        return $this->searchAdresse($query);

      case 'bruger':
        return $this->searchBruger($query);

      case 'person':
        return $this->searchPerson($query);
    }

    throw new InvalidArgumentException(sprintf('Invalid search type: %s', $type));
  }

}
