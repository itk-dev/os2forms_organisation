<?php

namespace Drupal\os2forms_organisation\Helper;

use ItkDev\Serviceplatformen\Service\SF1500\SF1500;
use ItkDev\Serviceplatformen\Service\SF1500\SF1500XMLBuilder;
use ItkDev\Serviceplatformen\Service\SF1514\SF1514;
use ItkDev\Serviceplatformen\Service\SoapClient;
use Symfony\Component\PropertyAccess\PropertyAccessor;

/**
 * Organisation Helper service.
 */
class OrganisationHelper {
  /**
   * The PropertyAccessor.
   *
   * @var \Symfony\Component\PropertyAccess\PropertyAccessor
   */
  private PropertyAccessor $propertyAccessor;

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
  public function __construct(PropertyAccessor $propertyAccessor, CertificateLocatorHelper $certificateLocator, Settings $settings) {
    $this->propertyAccessor = $propertyAccessor;
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

      $sf1500XMLBuilder = new SF1500XMLBuilder();

      unset($options['sts_applies_to']);

      $this->sf1500 = new SF1500($soapClient, $sf1514, $sf1500XMLBuilder, $this->propertyAccessor, $options);
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

}
