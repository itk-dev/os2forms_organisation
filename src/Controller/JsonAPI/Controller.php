<?php

namespace Drupal\os2forms_organisation\Controller\JsonAPI;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Http\RequestStack;
use Drupal\os2forms_organisation\Helper\OrganisationHelper;
use ItkDev\Serviceplatformen\Service\SF1500\Model\AbstractModel;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * JSON:API controller.
 */
class Controller extends ControllerBase {

  /**
   * Constructor.
   */
  public function __construct(readonly private RequestStack $requestStack, readonly private OrganisationHelper $organisationHelper) {
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get(OrganisationHelper::class)
    );
  }

  /**
   * Search action.
   */
  public function search(): Response {
    $request = $this->requestStack->getCurrentRequest();

    $type = $request->get('type', 'bruger');
    $query = $request->get('query', []);

    try {
      $items = $this->organisationHelper->search($type, $query);
      $data = array_map(
        static fn (AbstractModel $item) => [
          'type' => $type,
          'id' => $item->id,
          'properties' => $item->getData(),
        ],
        $items
      );

      return new JsonResponse(['data' => $data], Response::HTTP_BAD_REQUEST);
    }
    catch (\Throwable $throwable) {
      // @see https://jsonapi.org/format/#errors
      $result = [
        'errors' => [
          'status' => $throwable->getCode(),
          'title' => $throwable->getMessage(),
        ],
      ];

      return new JsonResponse($result, Response::HTTP_BAD_REQUEST);
    }

  }

}
