<?php

namespace Drupal\os2forms_organisation\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Link;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use ItkDev\Serviceplatformen\Service\SF1500\AdresseService;
use ItkDev\Serviceplatformen\Service\SF1500\BrugerService;
use ItkDev\Serviceplatformen\Service\SF1500\PersonService;

/**
 * Search form.
 */
final class SearchForm extends FormBase {
  use StringTranslationTrait;

  private const TYPE_ADRESSE = 'adresse';
  private const TYPE_BRUGER = 'bruger';
  private const TYPE_PERSON = 'person';

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'os2forms_organisation_search';
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   * @phpstan-return array<string, mixed>
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $request = $this->getRequest();
    $parameters = $request->query->all();

    $form['type'] = [
      '#type' => 'select',
      '#title' => 'type',
      '#options' => [
        self::TYPE_ADRESSE => $this->t('Adresse'),
        self::TYPE_BRUGER => $this->t('Bruger'),
        self::TYPE_PERSON => $this->t('Person'),
      ],
      '#default_value' => $parameters['type'] ?? self::TYPE_PERSON,
    ];

    $form['query'] = [
      '#type' => 'container',
      '#tree' => TRUE,

      AdresseService::FILTER_ADRESSETEKST => [
        '#states' => [
          'visible' => [
            ':input[id="edit-type"]' => ['value' => self::TYPE_ADRESSE],
          ],
        ],

        '#type' => 'textfield',
        '#title' => AdresseService::FILTER_ADRESSETEKST,
        '#default_value' => $parameters['query'][AdresseService::FILTER_ADRESSETEKST] ?? NULL,
      ],

      BrugerService::FILTER_BRUGERNAVN => [
        '#states' => [
          'visible' => [
            ':input[id="edit-type"]' => ['value' => self::TYPE_BRUGER],
          ],
        ],

        '#type' => 'textfield',
        '#title' => BrugerService::FILTER_BRUGERNAVN,
        '#default_value' => $parameters['query'][BrugerService::FILTER_BRUGERNAVN] ?? NULL,
      ],

      BrugerService::FILTER_LEDER => [
        '#states' => [
          'visible' => [
            ':input[id="edit-type"]' => ['value' => self::TYPE_BRUGER],
          ],
        ],

        '#type' => 'select',
        '#title' => BrugerService::FILTER_LEDER,
        '#options' => [
          'true' => $this->t('Yes'),
          'false' => $this->t('No'),
        ],
        '#empty_value' => '',
        '#empty_option' => '',
        '#default_value' => $parameters['query'][BrugerService::FILTER_LEDER] ?? NULL,
      ],

      PersonService::FILTER_NAVNTEKST => [
        '#states' => [
          'visible' => [
            ':input[id="edit-type"]' => ['value' => self::TYPE_PERSON],
          ],
        ],

        '#type' => 'textfield',
        '#title' => PersonService::FILTER_NAVNTEKST,
        '#default_value' => $parameters['query'][PersonService::FILTER_NAVNTEKST] ?? NULL,
      ],
    ];

    $form['actions']['#type'] = 'actions';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Search'),
      '#attributes' => [
        'formmethod' => 'get',
        'name' => '',
      ],
    ];

    $form['result'] = ['#weight' => 1000] + $this->renderResult($parameters);
    $form['#after_build'][] = $this->afterBuild(...);

    return $form;
  }

  /**
   * Render result.
   *
   * @phpstan-param array<string, mixed> $parameters
   * @phpstan-return array<string, mixed>
   */
  private function renderResult(array $parameters): array {
    if (!isset($parameters['type'])) {
      return [];
    }

    $type = $parameters['type'];
    $query = $this->filterQuery([
      'type' => $type,
      'query' => $this->filterQuery(match ($type) {
        self::TYPE_ADRESSE => [
          AdresseService::FILTER_ADRESSETEKST => $parameters['query'][AdresseService::FILTER_ADRESSETEKST] ?? NULL,
        ],
        self::TYPE_BRUGER => [
          BrugerService::FILTER_BRUGERNAVN => $parameters['query'][BrugerService::FILTER_BRUGERNAVN] ?? NULL,
          BrugerService::FILTER_LEDER => match ($parameters['query'][BrugerService::FILTER_LEDER] ?? NULL) {
            'true' => TRUE,
            'false' => FALSE,
            default => NULL
          },
        ],
        self::TYPE_PERSON => [
          PersonService::FILTER_NAVNTEKST => $parameters['query'][PersonService::FILTER_NAVNTEKST] ?? NULL,
        ],
        default => []
      }),
    ]);
    $url = Url::fromRoute('os2forms_organisation.jsonapi_search', $query);

    return [
      'url' => Link::fromTextAndUrl(json_encode($query), $url)->toRenderable(),

      'result' => [
        '#type' => 'html_tag',
        '#tag' => 'iframe',
        '#attributes' => [
          'id' => 'search-result',
          'src' => $url->toString(TRUE)->getGeneratedUrl(),
          'style' => 'width: 100%; height: 500px',
        ],
      ],
    ];
  }

  /**
   * Filter query.
   *
   * @phpstan-param array<string, mixed> $query
   * @phpstan-return array<string, mixed>
   */
  private function filterQuery(array $query): array {
    return array_filter(
      $query,
      static fn (mixed $value) => NULL !== $value
    );
  }

  /**
   * Form after build handler.
   *
   * @phpstan-param array<string, mixed> $form
   * @phpstan-return array<string, mixed>
   */
  private function afterBuild(array $form): array {
    // Remove form fields we don't want in the query string.
    unset($form['form_token'], $form['form_build_id'], $form['form_id']);

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   */
  public function submitForm(array &$form, FormStateInterface $formState): void {
  }

}
