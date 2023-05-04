<?php

namespace Drupal\os2forms_organisation\Plugin\WebformElement;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\os2forms_organisation\Exception\InvalidSettingException;
use Drupal\os2forms_organisation\Helper\OrganisationHelper;
use Drupal\os2forms_organisation\Helper\Settings;
use Drupal\webform\Plugin\WebformElement\WebformCompositeBase;
use Drupal\webform\Utility\WebformArrayHelper;
use Drupal\webform\WebformSubmissionInterface;
use ItkDev\Serviceplatformen\Service\SF1500\AbstractService;
use ItkDev\Serviceplatformen\Service\SF1500\BrugerService;
use ItkDev\Serviceplatformen\Service\SF1500\Model\AbstractModel;
use ItkDev\Serviceplatformen\Service\SF1500\PersonService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessor;

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
  const DATA_DISPLAY_OPTION_CURRENT_USER = 'current_user';
  const DATA_DISPLAY_OPTION_MANAGER = 'manager';
  const DATA_DISPLAY_OPTION_SEARCH = 'search';

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
   * @var \Drupal\os2forms_organisation\Helper\OrganisationHelper
   */
  protected OrganisationHelper $organisationHelper;

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
   * Current user.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  private AccountProxyInterface $account;

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $configuration
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition): MineOrganisationsData {
    $instance = parent::create($container, $configuration, $plugin_id, $plugin_definition);

    $instance->settings = $container->get(Settings::class);
    $instance->organisationHelper = $container->get(OrganisationHelper::class);
    $instance->propertyAccessor = PropertyAccess::createPropertyAccessor();
    $instance->routeMatch = $container->get('current_route_match');
    $instance->account = $container->get('current_user');

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
      ],
    ]);

    // Hide the search block.
    $form['composite']['element']['search']['#access'] = FALSE;

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
      'organisation_funktionsnavn',
      'organisation_enhed',
      'organisation_adresse',
      'organisation_niveau_2',
      'magistrat',
    ];

    $lines = [];

    foreach ($subElements as $subElement) {
      if (!empty($value[$subElement])) {
        $lines[$subElement] = $value[$subElement];
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
      $compositeElement = &NestedArray::getValue($form['elements'], $element['#webform_parents']);

      if (!isset($element['#data_type'])) {
        throw new InvalidSettingException(sprintf('Invalid element configuration. OrganisationData element: %s, should contain a data display option', $form['#webform_id']));
      }

      $form['#attached']['library'][] = 'os2forms_organisation/os2forms_organisation';

      // Hide search result. Will be unhidden if a search is actually performed.
      $compositeElement['#search_result__access'] = FALSE;
      $dataType = $element['#data_type'];
      if (self::DATA_DISPLAY_OPTION_SEARCH === $dataType) {
        // Set names on buttons so we can find the right trigger element.
        $compositeElement['#search_submit__name'] = $this->getTriggerName('search_submit', $compositeElement);
        $compositeElement['#search_user_apply__name'] = $this->getTriggerName('search_user_apply', $compositeElement);
        if ($organisationElement = $this->isTriggered($compositeElement['#search_user_apply__name'])) {
          $parents = $organisationElement['#parents'];
          array_push($parents, 'search_user_id');
          if ($userId = $formState->getValue($parents)) {
            // Set our user id.
            $formState->set(self::FORM_STATE_USER_ID, $userId);
            // And display user data.
            $dataType = self::DATA_DISPLAY_OPTION_CURRENT_USER;
          }
        }
        else {
          $this->handleSearch($element, $form, $compositeElement['#search_submit__name']);
          return;
        }
      }
      else {
        // Hide search block.
        $compositeElement['#search__access'] = FALSE;
      }

      $funktionOptions = $this->buildOrganisationFunktionOptions($dataType);

      if (empty($funktionOptions)) {
        // A user must have at least one funktion (ansættelse).
        return;
      }

      $this->updateBasicSubElements($compositeElement, $element['#data_type']);

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

      // Preselect organisation funktion (ansættelse) if there's only one.
      if (count($funktionOptions) === 1) {
        $compositeElement['#organisations_funktion__value'] = $key;
      }
    }
  }

  /**
   * Builds organisation funktion options for select.
   *
   * @phpstan-return array<string, mixed>
   */
  private function buildOrganisationFunktionOptions(string $dataType): array {

    $brugerId = $this->getRelevantOrganisationUserId($dataType, FALSE);

    if (NULL === $brugerId) {
      return [];
    }

    $ids = match ($dataType) {
      self::DATA_DISPLAY_OPTION_MANAGER => (array) $this->getRelevantOrganisationUserId($dataType, TRUE),
      self::DATA_DISPLAY_OPTION_CURRENT_USER => $this->organisationHelper->getOrganisationFunktioner($brugerId),
      default => []
    };

    if (empty($ids)) {
      return [];
    }

    // Make them human-readable.
    $options = [];
    foreach ($ids as $id) {
      $organisationEnhed = $this->organisationHelper->getOrganisationEnhed($id);
      $funktionsNavn = $this->organisationHelper->getFunktionsNavn($id);

      $options[$id] = $organisationEnhed . ', ' . $funktionsNavn;
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
  private function getBasicValues(array &$element, string $dataType, string $brugerId = NULL): array {
    $values = [];

    if (NULL === $brugerId) {
      $brugerId = $this->getRelevantOrganisationUserId($dataType, FALSE);
    }

    if (NULL !== $brugerId) {
      $compositeElements = $this->propertyAccessor->getValue($element, '[#webform_composite_elements]');

      if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[name][#access]')) {
        $values['name'] = $this->organisationHelper->getPersonName($brugerId);
      }

      if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[email][#access]')) {
        $values['email'] = $this->organisationHelper->getPersonEmail($brugerId);
      }

      if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[az][#access]')) {
        $values['az'] = $this->organisationHelper->getPersonAZIdent($brugerId);
      }

      if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[phone][#access]')) {
        $values['phone'] = $this->organisationHelper->getPersonPhone($brugerId);
      }

      if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[location][#access]')) {
        $values['location'] = $this->organisationHelper->getPersonLocation($brugerId);
      }
    }

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
      if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[organisation_funktionsnavn][#access]')) {
        $values['organisation_funktionsnavn'] = $this->organisationHelper->getFunktionsNavn($funktionsId);
      }

      if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[organisation_enhed][#access]')) {
        $values['organisation_enhed'] = $this->organisationHelper->getOrganisationEnhed($funktionsId);
      }

      if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[organisation_adresse][#access]')) {
        $values['organisation_adresse'] = $this->organisationHelper->getOrganisationAddress($funktionsId);
      }

      if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[organisation_niveau_2][#access]')) {
        $values['organisation_niveau_2'] = $this->organisationHelper->getOrganisationEnhedNiveauTo($funktionsId);
      }

      if (FALSE !== $this->propertyAccessor->getValue($compositeElements, '[magistrat][#access]')) {
        $values['magistrat'] = $this->organisationHelper->getPersonMagistrat($funktionsId);
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
   * Fetches current user organisation user id.
   */
  private function getCurrentUserOrganisationId(): ?string {
    if (NULL !== $this->formState && $this->formState->has(self::FORM_STATE_USER_ID)) {
      return $this->formState->get(self::FORM_STATE_USER_ID);
    }

    $user = $this->entityTypeManager->getStorage('user')->load($this->account->id());

    return $user->hasField('field_organisation_user_id') ? $user->get('field_organisation_user_id')->value : NULL;
  }

  /**
   * Gets relevant organisation bruger or funktions id.
   *
   * @phpstan-return mixed
   */
  private function getRelevantOrganisationUserId(string $dataType, bool $returnFunktionsId) {
    $currentUserId = $this->getCurrentUserOrganisationId();

    if (NULL === $currentUserId) {
      return NULL;
    }

    switch ($dataType) {
      case self::DATA_DISPLAY_OPTION_CURRENT_USER:
        return $currentUserId;

      case self::DATA_DISPLAY_OPTION_MANAGER:
        $managerInfo = $this->organisationHelper->getManagerInfo($currentUserId);

        if (empty($managerInfo)) {
          return [];
        }

        // @todo Handle multiple managers - for now just pick first one.
        $managerInfo = reset($managerInfo);

        return $managerInfo[$returnFunktionsId ? 'funktionsId' : 'brugerId'];

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
            Markup::create(sprintf('<button type="button" data-result-user-id="%1$s" class="button btn">%2$s</button>', $userId, (string) $this->t('Select user'))),
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
    $models = [];
    $models[] = $this->organisationHelper->search([
      BrugerService::FILTER_BRUGERNAVN => $query,
      PersonService::FILTER_NAVNTEKST => $query,
      AbstractService::PARAMETER_LIMIT => 10,
    ]);

    return array_map(
      static fn (AbstractModel $model) => $model->id,
      array_merge(...$models)
    );
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
   * @param string $name
   *   The name.
   * @param array $element
   *   The webform element.
   *
   * @return string
   *   The trigger name.
   */
  private function getTriggerName(string $name, array $element): string {
    return $element['#webform_id'] . '-' . $name;
  }

}
