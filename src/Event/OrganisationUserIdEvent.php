<?php

namespace Drupal\os2forms_organisation\Event;

use Drupal\Component\EventDispatcher\Event;

/**
 * Organisation user id event.
 */
class OrganisationUserIdEvent extends Event {

  /**
   * The use id.
   *
   * @var ?string
   */
  private ?string $userId = NULL;

  /**
   * Get user id.
   *
   * @return ?string
   *   The user id.
   */
  public function getUserId(): ?string {
    return $this->userId;
  }

  /**
   * Set user id.
   *
   * @param string $userId
   *   The user id.
   */
  public function setUserId(string $userId): void {
    $this->userId = $userId;
  }

}
