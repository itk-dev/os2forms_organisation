<?php

namespace Drupal\os2forms_organisation\Plugin\WebformElement;

use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\os2forms_organisation\Event\OrganisationUserIdEvent;
use Drupal\os2forms_organisation\Exception\ApiException;
use Drupal\os2forms_organisation\Exception\InvalidSettingException;
use Drupal\os2forms_organisation\Helper\OrganisationApiHelper;
use Drupal\os2forms_organisation\Helper\Settings;
use Drupal\os2web_audit\Service\Logger;
use Drupal\os2web_nemlogin\Service\AuthProviderService;
use Drupal\webform\Element\WebformMessage;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\WebformSubmissionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Provides mine organisation data element.
 *
 * @WebformElement(
 *   id = "mine_organisations_data_element",
 *   label = @Translation("Mine organisation data"),
 *   description = @Translation("Provides a form element to collect organisation data."),
 *   category = @Translation("Organisation"),
 *   multiline = TRUE,
 *   composite = TRUE,
 *   states_wrapper = TRUE,
 * )
 */
class MineOrganisationsData extends WebformCompositeBase {
  use MessengerTrait;

  const DATA_DISPLAY_OPTION_CURRENT_USER = 'current_user';
  const DATA_DISPLAY_OPTION_MANAGER = 'manager';
  const DATA_DISPLAY_OPTION_SEARCH = 'search';
  const DATA_DISPLAY_OPTION_INHERIT = 'inherit';

  private const FUNKTION_DATA_KEYS = [
    'stillingsbetegnelse',
    'organisation_enhed',
    'organisation_adresse',
    'organisation_niveau_2',
    'magistrat',
  ];

  private const ORGANISATION_PATH_KEYS = [
    'organisation_niveau_2',
    'magistrat',
  ];

  /**
   * The form state.
   *
   * @var \Drupal\Core\Form\FormStateInterface|null
   */
  private ?FormStateInterface $formState = NULL;

  /**
   * Organisation Settings.
   *
   * @var \Drupal\os2forms_organisation\Helper\Settings
   */
  protected Settings $settings;

  /**
   * Organisation Helper.
   *
   * @var \Drupal\os2forms_organisation\Helper\OrganisationApiHelper
   */
  protected OrganisationApiHelper $organisationHelper;

  /**
   * Property accessor.
   *
   * @var \Symfony\Component\PropertyAccess\PropertyAccessor
   */
  protected PropertyAccessor $propertyAccessor;

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  private RouteMatchInterface $routeMatch;

  /**
   * Event dispatcher.
   *
   * @var \Symfony\Contracts\EventDispatcher\EventDispatcherInterface
   */
  private EventDispatcherInterface $eventDispatcher;

  /**
   * The OS2Web Nemlogin authorization provider service.
   *
   * @var \Drupal\os2web_nemlogin\Service\AuthProviderService
   */
  private AuthProviderService $authProvider;

  /**
   * Audit logger.
   *
   * @var \Drupal\os2web_audit\Service\Logger
   */
  private Logger $auditLogger;

  /**
   * Bruger information.
   *
   * @var array|null
   *
   * @phpstan-var array<string, mixed>|null
   */
  private ?array $brugerInformation = NULL;

  /**
   * Funktion information.
   *
   * @var array|null
   *
   * @phpstan-var array<string, mixed>|null
   */
  private ?array $funktionInformation = NULL;

  /**
   * Manager information.
   *
   * @var array|null
   *
   * @phpstan-var array<string, mixed>|null
   */
  private ?array $managerInformation = NULL;

  /**
   * Organisation path information.
   *
   * @var array|null
   *
   * @phpstan-var array<string, mixed>|null
   */
  private ?array $organisationInformation = NULL;

  /**
   * Search bruger information.
   *
   * @var array|null
   *
   * @phpstan-var array<string, mixed>|null
   */
  private ?array $searchInformation = NULL;

  /**
   * Weborm id.
   *
   * @var string|null
   *
   * @phpstan-var string|null
   */
  private ?string $webformId = NULL;

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $configuration
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): MineOrganisationsData {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->settings = $container->get(Settings::class);
    $instance->organisationHelper = $container->get(OrganisationApiHelper::class);
    $instance->propertyAccessor = PropertyAccess::createPropertyAccessor();
    $instance->routeMatch = $container->get('current_route_match');
    $instance->eventDispatcher = $container->get('event_dispatcher');
    $instance->authProvider = $container->get('os2web_nemlogin.auth_provider');
    $instance->auditLogger = $container->get('os2web_audit.logger');

    return $instance;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $element
   * @phpstan-param array<string, mixed> $options
   * @phpstan-return array<string, mixed>
   */
  protected function formatHtmlItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []): array {
    return $this->formatTextItemValue($element, $webform_submission, $options);
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-return array<string, mixed>
   */
  protected function defineDefaultProperties(): array {
    return [
      'data_type' => 'user',
    ] + parent::defineDefaultProperties();
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $form
   * @phpstan-return array<string, mixed>
   */
  public function form(array $form, FormStateInterface $form_state): array {
    $form = parent::form($form, $form_state);

    WebformArrayHelper::insertBefore($form['composite'], 'element', 'data_type', [
      '#type' => 'select',
      '#title' => $this->t('Show data for'),
      '#required' => TRUE,
      '#options' => [
        self::DATA_DISPLAY_OPTION_CURRENT_USER => $this->t('Logged in user'),
        self::DATA_DISPLAY_OPTION_MANAGER => $this->t('Manager of logged in user'),
        self::DATA_DISPLAY_OPTION_SEARCH => $this->t('Search'),
        self::DATA_DISPLAY_OPTION_INHERIT => $this->t('Inherit values'),
      ],
    ]);

    WebformArrayHelper::insertBefore($form['composite'], 'data_type', 'message_test', [
      '#type' => 'webform_message',
      '#message_message' => $this->t('Data is fetched from SF1500, Fælleskommunalt Organisationssystem (FK org).'),
      '#message_type' => 'info',
      '#message_close' => TRUE,
      '#message_storage' => WebformMessage::STORAGE_SESSION,
      '#access' => TRUE,
    ]);

    // Hide the search block.
    $form['composite']['element']['search']['#access'] = FALSE;

    // Hide organisations funktion selector element. It will be enabled if any
    // organisations data is requested.
    $form['composite']['element']['organisations_funktion']['#access'] = FALSE;

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $element
   * @phpstan-param array<string, mixed> $options
   * @phpstan-return array<string, mixed>
   */
  protected function formatTextItemValue(array $element, WebformSubmissionInterface $webform_submission, array $options = []): array {
    $value = $this->getValue($element, $webform_submission, $options);

    $subElements = [
      'name',
      'email',
      'az',
      'phone',
      'location',
      'stillingsbetegnelse',
      'organisation_enhed',
      'organisation_adresse',
      'organisation_niveau_2',
      'magistrat',
    ];

    $lines = [];

    foreach ($subElements as $subElement) {
      if (!empty($value[$subElement])) {
        $title = NestedArray::getValue($element, [
          '#webform_composite_elements',
          $subElement,
          '#title',
        ]);
        $lines[$subElement] = NULL !== $title ? $title . ': ' . $value[$subElement] : $value[$subElement];
      }
    }

    return $lines;
  }

  /**
   * Alters form.
   *
   * @phpstan-param array<string, mixed> $element
   * @phpstan-param array<string, mixed> $form
   */
  public function alterForm(array &$element, array &$form, FormStateInterface $formState): void {
    $this->formState = $formState;

    if (!isset($form['#webform_id'])) {
      return;
    }

    $this->webformId = $form['#webform_id'];

    // Only alter when displaying submission form.
    $accessCheckRouteNames = [
      // Webform attached to a node.
      'entity.node.canonical',
      // Creating a new submission.
      'entity.webform.canonical',
      // Editing a submission.
      'entity.webform_submission.edit_form',
    ];

    if (!in_array($this->routeMatch->getRouteName(), $accessCheckRouteNames, TRUE)) {
      return;
    }

    if ('mine_organisations_data_element' === $element['#type']) {
      // Notice that this takes the elements from the form.
      $compositeElement = &NestedArray::getValue(
        $form['elements'],
        $element['#webform_parents']
      );

      if (!isset($element['#data_type'])) {
        throw new InvalidSettingException(sprintf('Invalid element configuration. OrganisationData element: %s, should contain a data display option', $form['#webform_id']));
      }

      $dataType = $element['#data_type'];

      $form['#attached']['library'][] = 'os2forms_organisation/os2forms_organisation';

      // Hide search result. Will be unhidden if a search is actually performed.
      $compositeElement['#search_result__access'] = FALSE;
      // Hide search block. Will be unhidden if needed.
      $compositeElement['#search__access'] = FALSE;

      if ($dataType === self::DATA_DISPLAY_OPTION_INHERIT) {
        return;
      }

      if (self::DATA_DISPLAY_OPTION_SEARCH === $dataType) {
        // Show search block.
        $compositeElement['#search__access'] = TRUE;
        // Set names on buttons,
        // such that we can find the right trigger element.
        $compositeElement['#search_submit__name'] = $this->getTriggerName('search_submit', $compositeElement);
        $compositeElement['#search_user_apply__name'] = $this->getTriggerName('search_user_apply', $compositeElement);
        if ($organisationElement = $this->isTriggered($compositeElement['#search_user_apply__name'])) {
          $parents = $organisationElement['#parents'];
          array_push($parents, 'search_user_id');
          if ($userId = $formState->getValue($parents)) {
            // Set our user id.
            $formState->set(self::FORM_STATE_USER_ID, $userId);
            // Set search bruger information.
            $this->setSearchInformation($userId);
          }
        }
        else {
          $this->handleSearch($element, $form, $compositeElement['#search_submit__name']);
          return;
        }
      }
      else {
        // Setup non-search information.
        $brugerId = $this->getRelevantOrganisationUserId($dataType);
        if ($brugerId) {
          if (self::DATA_DISPLAY_OPTION_CURRENT_USER === $dataType) {
            $this->setBrugerInformation($brugerId);
          }
          elseif (self::DATA_DISPLAY_OPTION_MANAGER === $dataType) {
            $this->setManagerInformation($brugerId);
          }
        }
      }

      $this->updateBasicSubElements($compositeElement, $dataType);

      // Show the organisations_funktion element and handle it
      // only if some funktion data is requested.
      $funktionDataRequested = $this->funktionDataRequested($compositeElement);
      $compositeElement['#organisations_funktion__access'] = $funktionDataRequested;

      if (!$funktionDataRequested) {
        return;
      }

      $funktionOptions = $this->buildOrganisationFunktionOptions($dataType);

      if (empty($funktionOptions)) {
        // A user must have at least one funktion (ansættelse).
        return;
      }

      // Setup organisation path information if requested.
      if ($this->organisationPathDataRequested($compositeElement)) {
        $this->setOrganisationPathInformation();
      }

      // Preselect organisation funktion (ansættelse) if there's only one.
      if (count($funktionOptions) === 1) {
        $compositeElement['#organisations_funktion__access'] = FALSE;
        $this->updateFunktionSubElements($compositeElement, array_key_first($funktionOptions));
        return;
      }

      // Get all funktion data and pass it on to a JavaScript handler.
      $data = [];
      foreach ($funktionOptions as $key => $value) {
        $data[$key] = $this->getFunktionValues($compositeElement, $key);
      }

      $emptyValue = '';
      $compositeElement['#organisations_funktion__empty_value'] = $emptyValue;

      // Create empty values for clearing fields.
      $values = reset($data);
      $data[$emptyValue] = array_map(static fn($value) => '', $values);

      $compositeElement['#organisations_funktion__attributes']['data-funktion'] = json_encode([
        'selector-pattern' => sprintf('[name="%s[%%key%%]"]', $compositeElement['#webform_key']),
        'values' => $data,
      ]);

      // @see https://www.drupal.org/docs/8/modules/webform/webform-cookbook/how-to-alter-properties-of-a-composites-sub-elements
      $compositeElement['#organisations_funktion__options'] = $funktionOptions;
    }
  }

  /**
   * Builds organisation funktion options for select.
   *
   * @phpstan-return array<string, mixed>
   */
  private function buildOrganisationFunktionOptions(string $dataType): array {

    $brugerId = $this->getRelevantOrganisationUserId($dataType);

    if (NULL === $brugerId) {
      return [];
    }

    $this->setFunktionInformation($brugerId, $dataType);

    // Make them human-readable.
    $options = [];
    foreach ($this->funktionInformation ?? [] as $funktion) {
      $options[$funktion['id']] = $funktion['enhedsnavn'] . ', ' . $funktion['funktionsnavn'];
    }

    return $options;
  }

  /**
   * Get bruger values.
   *
   * @param array $element
   *   The element.
   * @param string $dataType
   *   The data type.
   * @param string|null $brugerId
   *   Optional bruger id to use.
   *
   * @return array
   *   The values.
   *
   * @phpstan-param array<string, mixed> $element
   * @phpstan-return array<string, mixed>
   */
  private function getBasicValues(array &$element, string $dataType, ?string $brugerId = NULL): array {
    $values = [];

    if ($brugerId) {
      $this->setBrugerInformation($brugerId);
    }

    // Set data depending on data type.
    $data = match ($dataType) {
      self::DATA_DISPLAY_OPTION_CURRENT_USER => $this->brugerInformation,
      self::DATA_DISPLAY_OPTION_MANAGER => $this->managerInformation,
      self::DATA_DISPLAY_OPTION_SEARCH => $this->searchInformation,
      default => throw new InvalidSettingException(sprintf('Invalid data display option provided: %s. Allowed types: %s', $dataType, implode(', ', [
        self::DATA_DISPLAY_OPTION_CURRENT_USER,
        self::DATA_DISPLAY_OPTION_MANAGER,
        self::DATA_DISPLAY_OPTION_SEARCH,
      ]))),
    };

    if (NULL === $data) {
      $this->messenger()->addMessage($this->t('Could not fetch organisation data. Contact form owner.'));
    }

    $compositeElements = $this->propertyAccessor->getValue($element, '[#webform_composite_elements]');

    if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[name][#access]')) {
      $values['name'] = $data['navn'] ?? '';
    }

    if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[email][#access]')) {
      $values['email'] = $data['email'] ?? '';
    }

    if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[az][#access]')) {
      $values['az'] = $data['az'] ?? '';
    }

    if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[phone][#access]')) {
      $values['phone'] = $data['telefon'] ?? '';
    }

    if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[location][#access]')) {
      $values['location'] = $data['lokation'] ?? '';
    }

    $this->auditLog(
      implode(', ', array_map(
        function ($key, $value) {
          return $key . ':' . $value;
        },
        array_keys($values),
        $values
      ))
    );

    return $values;
  }

  /**
   * Get funktion values.
   *
   * @param array $element
   *   The element.
   * @param string $funktionsId
   *   The funktion-id.
   *
   * @return array
   *   The values.
   *
   * @phpstan-param array<string, mixed> $element
   * @phpstan-return array<string, mixed>
   */
  private function getFunktionValues(array &$element, string $funktionsId): array {
    $values = [];

    $compositeElements = $this->propertyAccessor->getValue($element, '[#webform_composite_elements]');

    if (NULL !== $compositeElements) {

      $funktionInformation = $this->funktionInformation ?? [];
      $organisationInformation = $this->organisationInformation ?? [];

      if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[stillingsbetegnelse][#access]')) {
        $values['stillingsbetegnelse'] = NestedArray::getValue(
          $funktionInformation,
          [$funktionsId, 'funktionsnavn']
        );
      }

      if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[organisation_enhed][#access]')) {
        $values['organisation_enhed'] = NestedArray::getValue(
          $funktionInformation,
          [$funktionsId, 'enhedsnavn']
        );
      }

      if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[organisation_adresse][#access]')) {
        $values['organisation_adresse'] = NestedArray::getValue(
          $funktionInformation,
          [$funktionsId, 'adresse']
        );
      }

      if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[organisation_niveau_2][#access]')) {
        $values['organisation_niveau_2'] = NestedArray::getValue(
          $organisationInformation,
          [$funktionsId, 1, 'enhedsnavn']
        );
      }

      if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[magistrat][#access]')) {
        $organisationArray = $organisationInformation[$funktionsId] ?? NULL;

        // Notice the -2 rather than -1, since the last entry will be 'Kommune'.
        $values['magistrat'] = is_countable($organisationArray)
          ? NestedArray::getValue(
            $organisationArray,
            [count($organisationArray) - 2, 'enhedsnavn']
          )
          : '';

      }
    }

    return $values;
  }

  /**
   * Updates basic sub elements.
   *
   * @phpstan-param array<string, mixed> $element
   */
  private function updateBasicSubElements(&$element, string $dataType): void {
    $values = $this->getBasicValues($element, $dataType);

    foreach ($values as $key => $value) {
      $element['#' . $key . '__value'] = $value;
    }
  }

  /**
   * Updates funktion sub elements.
   *
   * @phpstan-param array<string, mixed> $element
   */
  private function updateFunktionSubElements(&$element, string $funktionsId): void {
    $values = $this->getFunktionValues($element, $funktionsId);

    foreach ($values as $key => $value) {
      $element['#' . $key . '__value'] = $value;
    }
  }

  /**
   * Get current user organisation bruger id.
   */
  private function getCurrentUserOrganisationUserId(): ?string {
    // Let other modules set organisation user id.
    $event = new OrganisationUserIdEvent();
    $this->eventDispatcher->dispatch($event);

    return $event->getUserId();
  }

  /**
   * Gets relevant organisation bruger or funktions id.
   *
   * @phpstan-return mixed
   */
  private function getRelevantOrganisationUserId(string $dataType) {
    // If we have a value from form state, i.e. search, use it.
    if (NULL !== $this->formState && $this->formState->has(self::FORM_STATE_USER_ID) && $dataType === self::DATA_DISPLAY_OPTION_SEARCH) {
      $userId = $this->formState->get(self::FORM_STATE_USER_ID);
    }

    if (empty($userId)) {
      $userId = $this->getCurrentUserOrganisationUserId();
    }

    if (empty($userId)) {
      return NULL;
    }

    switch ($dataType) {
      case self::DATA_DISPLAY_OPTION_CURRENT_USER:
        return $userId;

      case self::DATA_DISPLAY_OPTION_MANAGER:
        try {
          return $this->organisationHelper->getManagerId($userId);
        }
        catch (ApiException $e) {
          return NULL;
        }

      case self::DATA_DISPLAY_OPTION_SEARCH:
        return $this->formState->get(self::FORM_STATE_USER_ID);
    }

    throw new InvalidSettingException(sprintf('Invalid data display option provided: %s. Allowed types: %s', $dataType, implode(', ', [
      self::DATA_DISPLAY_OPTION_CURRENT_USER,
      self::DATA_DISPLAY_OPTION_MANAGER,
      self::DATA_DISPLAY_OPTION_SEARCH,
    ])));
  }

  private const FORM_STATE_RESULT_KEY = 'os2forms_organisation_result';
  private const FORM_STATE_USER_ID = 'os2forms_organisation_user_id';

  /**
   * Handle search.
   *
   * @phpstan-param array<string, mixed> $element
   * @phpstan-param array<string, mixed> $form
   */
  private function handleSearch(array &$element, array &$form, string $triggerName): void {
    if ($organisationElement = $this->isTriggered($triggerName)) {
      if ($this->formState->isRebuilding()) {
        $storage = $this->formState->getStorage();
        unset($storage[self::FORM_STATE_RESULT_KEY]);
        $this->formState->setStorage($storage);
      }

      $parents = $organisationElement['#parents'];
      array_push($parents, 'search_query');
      $query = trim($this->formState->getValue($parents) ?: '');

      if (!empty($query)) {
        $parents = $organisationElement['#array_parents'];
        $compositeElement = &NestedArray::getValue($form, $parents);

        if ($this->formState->has(self::FORM_STATE_RESULT_KEY)) {
          $result = $this->formState->get(self::FORM_STATE_RESULT_KEY);
        }
        else {
          $result = $this->getSearchResult($compositeElement, $query);
          $this->formState->set(self::FORM_STATE_RESULT_KEY, $result);
        }

        $options = [];
        $rows = [];
        foreach ($result as $userId => $value) {
          $options[$userId] = json_encode($value);
          $rows[] = [
            $value['name'] ?? '',
            $value['email'] ?? '',
            $value['az'] ?? '',
            Markup::create(sprintf('<button type="button" data-result-user-id="%1$s" class="button button--outline--primary">%2$s</button>', $userId, (string) $this->t('Autofill'))),
          ];
        };
        $compositeElement['#search_result__access'] = TRUE;
        $compositeElement['#search_result_table__rows'] = $rows
          ?: [[$this->t('No results found for query %query', ['%query' => $query])]];
      }
    }
  }

  /**
   * Get search result.
   *
   * @phpstan-param array<string, mixed> $element
   *
   * @phpstan-return array<string, mixed>
   */
  private function getSearchResult(array &$element, string $query): array {
    $result = [];
    $userIds = $this->getSearchUserIds($query);

    foreach ($userIds as $userId) {
      $result[$userId] = $this->getBasicValues($element, self::DATA_DISPLAY_OPTION_CURRENT_USER, $userId);
    }

    return $result;
  }

  /**
   * Perform search to get user ids.
   *
   * @param string $query
   *   The query.
   *
   * @return string[]
   *   The user ids if any.
   */
  private function getSearchUserIds(string $query): array {

    try {
      $brugere = $this->organisationHelper->searchBruger($query);
    }
    catch (ApiException $e) {
      $this->logger->error(sprintf('Could not fetch organisation: %s', $e->getMessage()));
      $brugere = NULL;
      $this->messenger()->addMessage($this->t('Could not fetch organisation data. Contact form owner.'));
    }

    return array_column($brugere ?? [], 'id');
  }

  /**
   * Get organisation (composite) element for a trigger.
   *
   * @todo Rename this function to reflect what it actually does!
   *
   * @param string $name
   *   The trigger name.
   *
   * @return null|array
   *   The organisation element.
   *
   * @phpstan-return array<string, mixed>
   */
  private function isTriggered(string $name): ?array {
    $triggeringElement = $this->formState->getTriggeringElement();
    if ($triggeringElement && $name === ($triggeringElement['#name'] ?? NULL)) {
      $form = $this->formState->getCompleteForm();
      $parents = $triggeringElement['#array_parents'];

      // Check if we have a "mine_organisations_data_element" in the ancestor
      // path.
      while (!empty($parents)) {
        $element = NestedArray::getValue($form, $parents);
        if ('mine_organisations_data_element' === ($element['#type'] ?? NULL)) {
          return $element;
        }
        array_pop($parents);
      }
    }

    return NULL;
  }

  /**
   * Get trigger name.
   *
   * @phpstan-param array<string, mixed> $element
   *   The webform element.
   *
   * @return string
   *   The trigger name.
   *
   * @phpstan-param array<string, mixed> $element
   */
  private function getTriggerName(string $name, array $element): string {
    return $element['#webform_id'] . '-' . $name;
  }

  /**
   * Decide if any funktion data is requested.
   *
   * @phpstan-param array<string, mixed> $element
   *   The element.
   *
   * @return bool
   *   Whether funktion data is requested.
   *
   * @phpstan-param array<string, mixed> $element
   */
  private function funktionDataRequested(array $element): bool {
    return !empty(
      // Filter out elements that have been disabled.
      array_filter(
        self::FUNKTION_DATA_KEYS,
        static fn ($key) => FALSE !== ($element['#' . $key . '__access'] ?? TRUE)
      )
    );
  }

  /**
   * Decide if any organisation path data is requested.
   *
   * @phpstan-param array<string, mixed> $element
   *   The element.
   *
   * @return bool
   *   Whether funktion data is requested.
   *
   * @phpstan-param array<string, mixed> $element
   */
  private function organisationPathDataRequested(array $element): bool {
    return !empty(
      // Filter out elements that have been disabled.
    array_filter(
      self::ORGANISATION_PATH_KEYS,
      static fn ($key) => FALSE !== ($element['#' . $key . '__access'] ?? TRUE)
    )
    );
  }

  /**
   * Set bruger information.
   */
  private function setBrugerInformation(string $brugerId): void {
    try {
      $this->brugerInformation = $this->organisationHelper->getBrugerInformationer($brugerId);
    }
    catch (ApiException $e) {
      $this->brugerInformation = NULL;
    }
  }

  /**
   * Set manager information.
   */
  private function setManagerInformation(string $brugerId): void {
    try {
      $this->managerInformation = $this->organisationHelper->getManagerInformation($brugerId);
    }
    catch (ApiException $e) {
      $this->managerInformation = NULL;
    }
  }

  /**
   * Set funktion information.
   */
  private function setFunktionInformation(string $brugerId, string $dataType): void {
    try {
      $this->funktionInformation = $this->organisationHelper->getFunktionInformationer($brugerId, self::DATA_DISPLAY_OPTION_MANAGER === $dataType);
    }
    catch (ApiException $e) {
      $this->funktionInformation = NULL;
    }
  }

  /**
   * Set organisation path information.
   */
  private function setOrganisationPathInformation(): void {
    try {
      $organisationPathData = [];

      foreach (array_keys($this->funktionInformation) as $key) {
        $organisationPathData[$key] = $this->organisationHelper->getOrganisationPath($key);
      }

      $this->organisationInformation = $organisationPathData;
    }
    catch (ApiException $e) {
      $this->organisationInformation = NULL;
    }
  }

  /**
   * Set search bruger information.
   */
  private function setSearchInformation(string $brugerId): void {
    try {
      $this->searchInformation = $this->organisationHelper->getBrugerInformationer($brugerId);
    }
    catch (ApiException $e) {
      $this->searchInformation = NULL;
    }
  }

  /**
   * Audit logs viewed data.
   */
  private function auditLog(string $data): void {
    if (!$this->webformId) {
      $this->logger->error('Failed audit logging due to missing webform id.');
      return;
    }

    // Find configured session provider.
    try {
      $webform = $this->entityTypeManager->getStorage('webform')->load($this->webformId);
      $webformNemIdSettings = $webform->getThirdPartySetting('os2forms', 'os2forms_nemid');
      $sessionType = $webformNemIdSettings['session_type'] ?? NULL;
      $plugin = $sessionType ? $this->authProvider->getPluginInstance($sessionType) : $this->authProvider->getActivePlugin();
    }
    catch (InvalidPluginDefinitionException | PluginNotFoundException | PluginException $e) {
      $this->logger->error(sprintf('Failed audit logging: %s', $e->getMessage()));
      return;
    }

    $user = $plugin->fetchValue('email') ?: $this->getCurrentUserOrganisationUserId();

    $msg = sprintf('User %s looked at: %s', $user, $data);
    $this->auditLogger->info('OrganisationData', $msg);
  }

}
