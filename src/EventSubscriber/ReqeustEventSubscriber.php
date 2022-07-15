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
use Drupal\taxonomy\Entity\Term;

/**
 * Event Subscriber Transaltion.
 */
class ReqeustEventSubscriber implements EventSubscriberInterface {


  /**
   * Code that should be triggered on event specified
   */
  public function onRequest(GetResponseEvent $event) {

    // Only preload on json/api requests.
    if ($event->getRequest()->getRequestFormat() == 'json' && $event->getRequest()->getMethod() == 'PATCH') {
      $default_language = \Drupal::languageManager()->getDefaultLanguage()->getId();
      list(,$language,$bundle,$path_part_3,$path_part_4) = explode('/', $event->getRequest()->getPathInfo());

      if (empty($language)) {
        $language = $default_language;
      }

      // Create translation only if POST request contains language param
      if ($language != $default_language) {

        // Need to load node and taxonomy term differently
        if ($bundle == "node") {
          $nid = $path_part_3;
          $node = Node::load($path_part_3);
          if (!$node->hasTranslation($language)) {
            \Drupal::logger('rest_translation_util')->debug(
              "Node with ID @id has no '@lang' translation: create it!", [
                '@id' => $nid,
                '@lang' => $language,
            ]);
            $node->addTranslation($language, ['title' => $node->label()])->save();
          }
        } else if ($bundle == "taxonomy") {
          $tid = $path_part_4;
          $term = Term::load($tid);
          if (!$term->hasTranslation($language)) {
            \Drupal::logger('rest_translation_util')->debug(
              "Term with ID @id has no '@lang' translation: create it!", [
                '@id' => $tid,
                '@lang' => $language,
            ]);
            $term->addTranslation($language, ['name' => $term->label()])->save();
          }
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

