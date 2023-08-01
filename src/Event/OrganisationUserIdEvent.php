<?php

namespace Drupal\os2forms_organisation\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Organisation user id event.
 */
class OrganisationUserIdEvent extends Event {
  const EVENT_NAME = 'organisation_user_id_event';

  /**
   * The id.
   *
   * @var string
   */
  private string $id;

  /**
   * The constructor.
   */
  public function __construct() {
    $this->id = '';
  }

  /**
   * Get id.
   *
   * @return string
   *   The id.
   */
  public function getId(): string {
    return $this->id;
  }

  /**
   * Set id.
   *
   * @param string $id
   *   The id.
   */
  public function setId(string $id): void {
    $this->id = $id;
  }

}
