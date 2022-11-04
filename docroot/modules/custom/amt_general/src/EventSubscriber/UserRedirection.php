<?php

namespace Drupal\amt_general\EventSubscriber;

use Drupal\Core\Url;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * Redirects requests when required.
 *
 * Redirects anonymous-users to the login-page.
 */
class UserRedirection implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::REQUEST][] = ['handleRedirection'];
    return $events;
  }

  /**
   * Redirects users to login-page when required.
   *
   * @param \Symfony\Component\HttpKernel\Event\GetResponseEvent $event
   *   Accepts response for requests.
   *
   * @see getSubscribedEvents()
   */
  public function handleRedirection(GetResponseEvent $event) {
    if (\Drupal::currentUser()->isAnonymous()) {
      $frontPagePath = Url::fromRoute('<front>')->toString();
      if ($event->getRequest()->getRequestUri() == $frontPagePath) {
        $event->setResponse(new RedirectResponse(
          Url::fromRoute('user.login')
            ->toString()
        ));
      }
    }
  }

}
