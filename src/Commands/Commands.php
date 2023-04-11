<?php

namespace Drupal\os2forms_organisation\Commands;

use Drupal\os2forms_organisation\Helper\OrganisationHelper;
use Drush\Commands\DrushCommands;
use ItkDev\Serviceplatformen\Service\Exception\SoapException;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;

/**
 * A drush command file for commands related to os2forms_organisation.
 */
class Commands extends DrushCommands {

  /**
   * The organisation helper.
   *
   * @var \Drupal\os2forms_organisation\Helper\OrganisationHelper
   */
  private OrganisationHelper $helper;

  /**
   * The read options.
   *
   * @var array
   * @phpstan-var array<string, mixed>
   */
  private array $readOptions = [];

  /**
   * Constructor.
   */
  public function __construct(OrganisationHelper $helper) {
    parent::__construct();
    $this->helper = $helper;
  }

  /**
   * Lookup provided bruger id.
   *
   * @param string $brugerId
   *   string Argument with organisation bruger id.
   *
   * @command os2forms_organisation:lookup
   *
   * @usage os2forms_organisation:lookup
   */
  public function lookup(string $brugerId): void {
    $this->output()->writeln('Name: ' . $this->helper->getPersonName($brugerId));
  }

  /**
   * Read data.
   *
   * @param string $type
   *   The object type to read (user, person, …).
   * @param string $uuid
   *   The UUID.
   * @param array $options
   *   The options.
   *
   * @option manager-levels
   *   Levels of managers to read
   *
   * @command os2forms_organisation:read
   *
   * @usage os2forms_organisation:read --help
   *
   * @phpstan-param array<string, mixed> $options
   */
  public function read(string $type, string $uuid, array $options = [
    'manager-levels' => 1,
  ]): void {
    $this->readOptions = $options;

    switch ($type) {
      case 'person':
        $data = $this->readPerson($uuid);

        $this->output()->writeln(Yaml::dump($data, PHP_INT_MAX));
        break;

      default:
        throw new \InvalidArgumentException(sprintf('Unknown type: %s', $type));
    }
  }

  /**
   * Read person.
   *
   * @phpstan-return array<string, mixed>
   */
  private function readPerson(string $uuid, int $level = 0): array {
    $maxLevel = $this->readOptions['manager-levels'] ?? 1;
    if ($level > $maxLevel) {
      return [];
    }

    $data = [
      'PersonName' => $this->helper->getPersonName($uuid),
      'PersonAZIdent' => $this->helper->getPersonAZIdent($uuid),
      'PersonEmail' => $this->helper->getPersonEmail($uuid),
      'PersonPhone' => $this->helper->getPersonPhone($uuid),
      'PersonLocation' => $this->helper->getPersonLocation($uuid),
      'Id' => $uuid,
    ];

    $funktioner = $this->helper->getOrganisationFunktioner($uuid);
    foreach ($funktioner as $funktionsId) {
      $data['OrganisationFunktioner'][$funktionsId] = [
        'FunktionsNavn' => $this->helper->getFunktionsNavn($funktionsId),
        'OrganisationEnhed' => $this->helper->getOrganisationEnhed($funktionsId),
        'OrganisationAddress' => $this->helper->getOrganisationAddress($funktionsId),
        'OrganisationEnhedNiveauTo' => $this->helper->getOrganisationEnhedNiveauTo($funktionsId),
        'PersonMagistrat' => $this->helper->getPersonMagistrat($funktionsId),
        'Id' => $funktionsId,
      ];
    }

    $managers = $this->helper->getManagerInfo($uuid);
    foreach ($managers as $manager) {
      $managerId = $manager['brugerId'] ?? NULL;
      if (NULL !== $managerId && $managerId !== $uuid) {
        $manager = $this->readPerson($managerId, $level + 1);
        if (!empty($manager)) {
          $data['Manager'][] = $manager;
        }
      }
    }

    return $data;
  }

  /**
   * Search organisation data.
   *
   * @param string $type
   *   The object type to search for (user, person, …).
   * @param string $query
   *   The search query (JSON)
   *
   * @command os2forms_organisation:search
   *
   * @usage os2forms_organisation:search --help
   * @usage os2forms_organisation:search person '{"navntekst": "Anders And"}'
   * @usage os2forms_organisation:search bruger '{"brugernavn": "user"}'
   * @usage os2forms_organisation:search adresse '{"adressetekst":"rimi@aarhus.dk"}'
   */
  public function search(string $type, string $query): void {
    try {
      $query = json_decode($query, TRUE, 512, JSON_THROW_ON_ERROR);
    }
    catch (\JsonException) {
      throw new InvalidArgumentException(sprintf('Invalid JSON: %s', $query));
    }
    try {
      $result = $this->helper->search($type, $query);

      $this->output()->writeln(Yaml::dump(json_decode(json_encode($result), TRUE), PHP_INT_MAX));
    }
    catch (\Throwable $throwable) {
      if ($throwable instanceof SoapException) {
        $this->output->writeln([
          '',
          'Request',
          '',
          $throwable->getRequest() ?? '',
          '',
        ]);
        $this->output->writeln([
          'Response',
          '',
          $throwable->getResponse() ?? '',
          '',
        ]);
      }

      throw $throwable;
    }
  }

}