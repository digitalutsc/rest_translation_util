<?php

/**
 * @file
 * Contains Drupal\rest_translation_util\EventSubscriber\RequestEventSubscriber.
 */

namespace Drupal\rest_translation_util\EventSubscriber;

use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Drupal\node\Entity\Node;

/**
 * Event Subscriber Transaltion.
 */
class ReqeustEventSubscriber implements EventSubscriberInterface {


  /**
   * Code that should be triggered on event specified
   */
  public function onRequest(GetResponseEvent $event) {

    // Only preload on json/api requests.
    if ($event->getRequest()->getRequestFormat() == 'json') {
      $default_language = \Drupal::languageManager()->getDefaultLanguage()->getId();

      list(,$language,,$nid) = explode('/', $event->getRequest()->getPathInfo());

      if (empty($language)) {
        $language = $default_language;
      }

      if ($language != $default_language) {
        $node = Node::load($nid);
        if (!$node->hasTranslation($language)) {
          \Drupal::logger('rest_translation_util')->debug(
            "Node with ID @id has no '@lang' translation: create it!", [
              '@id' => $nid,
              '@lang' => $language,
          ]);
          $node->addTranslation($language, ['title' => $node->label()])->save();
        }
      }


    }
  }

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Set a high priority so it is executed before routing.
    $events[KernelEvents::REQUEST][] = ['onRequest', 1000];
    return $events;
  }

}

