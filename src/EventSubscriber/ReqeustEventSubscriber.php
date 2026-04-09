<?php

namespace Drupal\rest_translation_util\EventSubscriber;

use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

/**
 * Event Subscriber Transaltion.
 */
class ReqeustEventSubscriber implements EventSubscriberInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The Drupal Logger Factory.
   *
   * @var \Drupal\Core\Logger\LoggerChannelFactoryInterface
   */
  protected $loggerFactory;

  /**
   * Constructs a new event subscriber object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Logger\LoggerChannelFactoryInterface $loggerFactory
   *   The Drupal Logger Factory.
   */
  public function __construct(EntityTypeManagerInterface $entityTypeManager, LoggerChannelFactoryInterface $loggerFactory) {
    $this->entityTypeManager = $entityTypeManager;
    $this->loggerFactory = $loggerFactory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('logger.factory')
    );
  }

  /**
   * Code that should be triggered on event specified.
   */
  public function onRequest(RequestEvent $event) {

    // Only preload on json/api requests.
    if ($event->getRequest()->getRequestFormat() == 'json' && $event->getRequest()->getMethod() == 'PATCH') {
      [, $language, $bundle, $path_part_3, $path_part_4] = explode('/', $event->getRequest()->getPathInfo());

      // Create translation only if POST request contains language param.
      if (!empty($language)) {

        // Need to load node and taxonomy term differently.
        if ($bundle == "node") {
          $nid = $path_part_3;
          $node = $this->entityTypeManager->getStorage('node')->load($nid);
          if (!$node->hasTranslation($language)) {
            $this->loggerFactory->get('rest_translation_util')->debug(
              "Node with ID @id has no '@lang' translation: create it!", [
                '@id' => $nid,
                '@lang' => $language,
              ]);
            $node->addTranslation($language, ['title' => $node->label()])->save();
          }
        }
        elseif ($bundle == "taxonomy") {
          $tid = $path_part_4;
          $term = $this->entityTypeManager->getStorage('taxonomy_term')->load($tid);
          if (!$term->hasTranslation($language)) {
            $this->loggerFactory->get('rest_translation_util')->debug(
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
