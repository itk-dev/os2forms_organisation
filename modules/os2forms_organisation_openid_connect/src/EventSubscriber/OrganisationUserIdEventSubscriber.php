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
   * The constructor.
   */
  public function __construct(private readonly AccountInterface $currentUser, private readonly EntityTypeManagerInterface $entityTypeManager) {
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
    if (!empty($event->getUserId())) {
      return;
    }

    // Check if current user has field containing organisation user id.
    try {
      /** @var \Drupal\user\Entity\User $user */
      $user = $this->entityTypeManager->getStorage('user')->load($this->currentUser->id());

      if ($user->hasField('field_organisation_user_id') && !empty($user->get('field_organisation_user_id')->value)) {
        $event->setUserId($user->get('field_organisation_user_id')->value);
      }
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException $e) {
      return;
    }

  }

}
