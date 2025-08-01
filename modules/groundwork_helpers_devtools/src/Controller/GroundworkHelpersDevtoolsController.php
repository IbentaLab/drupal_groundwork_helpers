<?php

namespace Drupal\groundwork_helpers_devtools\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Psr\Log\LoggerInterface;

class GroundworkHelpersDevtoolsController extends ControllerBase {

  /**
   * The config factory service.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The current user service.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * The logger service.
   *
   * @var \Psr\Log\LoggerInterface
   */
  protected $logger;

  /**
   * Constructs the controller.
   */
  public function __construct(ConfigFactoryInterface $configFactory, AccountProxyInterface $currentUser, LoggerInterface $logger) {
    $this->configFactory = $configFactory;
    $this->currentUser = $currentUser;
    $this->logger = $logger;
  }

  /**
   * Dependency injection factory method.
   */
  public static function create(ContainerInterface $container): static {
    return new static(
      $container->get('config.factory'),
      $container->get('current_user'),
      $container->get('logger.factory')->get('groundwork_helpers_devtools')
    );
  }

  /**
   * Clears all caches.
   */
  public function clearCaches(AccountInterface $account): JsonResponse {
    if (!$account->hasPermission('clear all caches')) {
      return new JsonResponse([
        'status' => false,
        'error' => 'Access denied.',
      ], 403);
    }

    try {
      // Flush all caches.
      drupal_flush_all_caches();

      // Log the action.
      $this->logger->notice('All caches cleared by @user.', ['@user' => $account->getAccountName()]);

      return new JsonResponse([
        'status' => true,
        'message' => 'All caches cleared successfully.',
      ]);
    }
    catch (\Exception $e) {
      // Log the error.
      $this->logger->error('Cache clearing failed: @message', ['@message' => $e->getMessage()]);

      return new JsonResponse([
        'status' => false,
        'error' => 'Cache clearing failed: ' . $e->getMessage(),
      ], 500);
    }
  }

}
