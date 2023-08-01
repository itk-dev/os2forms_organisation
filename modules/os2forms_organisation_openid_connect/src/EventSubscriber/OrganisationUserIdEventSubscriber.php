<?php

namespace Drupal\os2forms_organisation_openid_connect\EventSubscriber;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\os2forms_organisation\Event\OrganisationUserIdEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Organisation user id event subscriber.
 */
class OrganisationUserIdEventSubscriber implements EventSubscriberInterface {

  /**
   * The current user.
   *
   * @var \Drupal\Core\Session\AccountInterface
   */
  protected AccountInterface $currentUser;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * The constructor.
   */
  public function __construct(AccountInterface $currentUser, EntityTypeManagerInterface $entityTypeManager) {
    $this->currentUser = $currentUser;
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * Subscribed events.
   */
  public static function getSubscribedEvents(): array {
    return [
      OrganisationUserIdEvent::class => ['setOrganisationUserId', 50],
    ];
  }

  /**
   * Attempts settings organisation user id.
   */
  public function setOrganisationUserId(OrganisationUserIdEvent $event) {
    // Check if id has already been set.
    if (!empty($event->getId())) {
      return;
    }

    // Check if current user has field containing organisation user id.
    try {
      /** @var \Drupal\user\Entity\User $user */
      $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());

      if ($user->hasField('field_organisation_user_id') && !empty($user->get('field_organisation_user_id')->value)) {
        $event->setId($user->get('field_organisation_user_id')->value);
      }
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      return;
    }

  }

}
