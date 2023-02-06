<?php

namespace Drupal\os2forms_organisation\Commands;

use Drupal\os2forms_organisation\Helper\OrganisationHelper;
use Drush\Commands\DrushCommands;

/**
 * A drush command file for commands related to os2forms_organisation.
 */
class LookupCommands extends DrushCommands {

  /**
   * The organisation helper.
   *
   * @var \Drupal\os2forms_organisation\Helper\OrganisationHelper
   */
  private OrganisationHelper $helper;

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

}
