<?php

namespace Drupal\os2forms_organisation\Commands;

use Drupal\Component\Utility\NestedArray;
use Drupal\os2forms_organisation\Helper\OrganisationApiHelper;
use Drush\Commands\DrushCommands;
use Symfony\Component\Console\Exception\InvalidArgumentException;
use Symfony\Component\Yaml\Yaml;

/**
 * A drush command file for commands related to os2forms_organisation.
 */
class Commands extends DrushCommands {

  /**
   * The organisation helper.
   *
   * @var \Drupal\os2forms_organisation\Helper\OrganisationApiHelper
   */
  private OrganisationApiHelper $helper;

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
  public function __construct(OrganisationApiHelper $helper) {
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

    $brugerInformation = $this->helper->getBrugerInformationer($brugerId);

    if (isset($brugerInformation['navn'])) {
      $this->output()->writeln('Name: ' . $brugerInformation['navn']);
    }
    else {
      $this->output()->writeln(sprintf('Could not find user with id: %s', $brugerId));
    }
  }

  /**
   * Read data.
   *
   * @param string $type
   *   The object type to read (bruger).
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
      case 'bruger':
        $data = $this->readBruger($uuid);

        $this->output()->writeln(Yaml::dump($data, PHP_INT_MAX));
        break;

      default:
        throw new InvalidArgumentException(sprintf('Unknown type: %s', $type));
    }
  }

  /**
   * Read bruger.
   *
   * @phpstan-return array<string, mixed>
   */
  private function readBruger(string $uuid, int $level = 0): array {
    $maxLevel = $this->readOptions['manager-levels'] ?? 1;
    if ($level > $maxLevel) {
      return [];
    }

    $brugerInformation = $this->helper->getBrugerInformationer($uuid);

    $data = [
      'PersonName' => $brugerInformation['navn'] ?? '',
      'PersonAZIdent' => $brugerInformation['az'] ?? '',
      'PersonEmail' => $brugerInformation['email'] ?? '',
      'PersonPhone' => $brugerInformation['telefon'] ?? '',
      'PersonLocation' => $brugerInformation['lokation'] ?? '',
      'Id' => $uuid,
    ];

    $funktioner = $this->helper->getFunktionInformationer($uuid);

    foreach ($funktioner as $funktion) {

      $organisationPath = $this->helper->getOrganisationPath($funktion['id']);

      $organisationEnhedNiveauTo = &NestedArray::getValue(
        $organisationPath,
        [1, 'enhedsnavn']
      );

      $personMagistrat = &NestedArray::getValue(
        $organisationPath,
        [count($organisationPath) - 2, 'enhedsnavn']
      );

      $data['OrganisationFunktioner'][$funktion['id']] = [
        'FunktionsNavn' => $funktion['funktionsnavn'] ?? '',
        'OrganisationEnhed' => $funktion['enhedsnavn'] ?? '',
        'OrganisationAddress' => $funktion['adresse'] ?? '',
        'OrganisationEnhedNiveauTo' => $organisationEnhedNiveauTo ?? '',
        'PersonMagistrat' => $personMagistrat ?? '',
        'Id' => $funktion['id'] ?? '',
      ];
    }

    $manager = $this->helper->getManagerInformation($uuid);
    if (isset($manager['id'])) {
      $manager = $this->readBruger($manager['id'], $level + 1);
      if (!empty($manager)) {
        $data['Manager'][] = $manager;
      }
    }

    return $data;
  }

  /**
   * Search organisation data.
   *
   * @param string $name
   *   The name to search for.
   *
   * @command os2forms_organisation:search:bruger
   *
   * @usage os2forms_organisation:search --help
   * @usage os2forms_organisation:search 'Anders And'
   *
   * @phpstan-param string $name
   */
  public function search(string $name): void {
    $result = $this->helper->searchBruger($name);
    $this->output()->writeln(Yaml::dump(json_decode(json_encode($result), TRUE), PHP_INT_MAX));
  }

}
