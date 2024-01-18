<?php

namespace Drupal\os2forms_organisation\Element;

use Drupal\webform\Element\WebformCompositeBase;

/**
 * Provides a webform element for personal organisation data (SF1500).
 *
 * @FormElement("mine_organisations_data_element")
 */
class MineOrganisationsData extends WebformCompositeBase {

  /**
   * {@inheritdoc}
   *
   * @phpstan-param array<string, mixed> $element
   * @phpstan-return array<string, mixed>
   */
  public static function getCompositeElements(array $element): array {
    $elements = [];

    $elements['search'] = [
      '#type' => 'markup',
      '#attributes' => [
        'class' => ['os2forms-organisation-search'],
      ],

      // We cannot render a "container" element since it is (or may be)
      // disabled. Therefore, we render a start tag here and an end tag below in
      // "search_query_wrapper_end".
      'search_query_wrapper_start' => [
        '#type' => 'markup',
        '#prefix' => '<div class="os2forms-organisation-search-query">',
      ],

      'search_query' => [
        '#type' => 'textfield',
        '#title' => t('Query'),
        '#title_display' => 'hidden',
        '#attributes' => [
          'placeholder' => t('Search for user name'),
        ],
      ],

      'search_submit' => [
        '#type' => 'button',
        '#value' => t('Search'),
        '#attributes' => [
          'data-name' => 'search-user-query',
          'class' => [
            'button--primary',
          ],
        ],
      ],

      // See "search_query_wrapper_start above".
      'search_query_wrapper_end' => [
        '#type' => 'markup',
        '#suffix' => '</div>',
      ],

      // We cannot render a "container" element since it is (or may be)
      // disabled. Therefore, we render a start tag here and an end tag below in
      // "search_result_wrapper_end".
      'search_result_wrapper_start' => [
        '#type' => 'markup',
        '#prefix' => '<div class="os2forms-organisation-search-result">',
      ],

      'search_user_id' => [
        '#type' => 'textfield',
        '#title' => t('User'),
        '#attributes' => [
          // Must match the selector in os2forms_organisation.js.
          'data-name' => 'search-user-id',
        ],
      ],

      'search_user_apply' => [
        '#type' => 'button',
        '#value' => t('Apply user'),
        '#attributes' => [
          // Must match the selector in os2forms_organisation.js.
          'data-name' => 'search-user-apply',
        ],
      ],

      // See "search_result_wrapper_start above".
      'search_result_wrapper_end' => [
        '#type' => 'markup',
        '#suffix' => '</div>',
      ],

      'search_result_table' => [
        '#type' => 'table',
        '#attributes' => [
          'class' => ['os2forms-organisation-search-result-table'],
        ],
      ],
    ];

    if (isset($element['#webform_key'])) {
      $elements['search']['search_submit'] += [
        '#limit_validation_errors' => [
          [
            $element['#webform_key'],
          ],
        ],
      ];

      $elements['search']['search_user_apply'] += [
        '#limit_validation_errors' => [
          [
            $element['#webform_key'],
          ],
        ],
      ];
    }

    $elements['name'] = [
      '#type' => 'textfield',
      '#title' => t('Name'),
      '#readonly' => TRUE,
    ];

    $elements['email'] = [
      '#type' => 'textfield',
      '#title' => t('Email'),
      '#readonly' => TRUE,
    ];

    $elements['az'] = [
      '#type' => 'textfield',
      '#title' => t('AZ-ident'),
      '#readonly' => TRUE,
    ];

    $elements['phone'] = [
      '#type' => 'textfield',
      '#title' => t('Phone'),
      '#readonly' => TRUE,
    ];

    $elements['location'] = [
      '#type' => 'textfield',
      '#title' => t('Location'),
      '#readonly' => TRUE,
    ];

    $elements['organisations_funktion'] = [
      '#type' => 'select',
      '#title' => t('Organisations funktion'),
      '#options' => [],
      '#access' => FALSE,
    ];

    $elements['stillingsbetegnelse'] = [
      '#type' => 'textfield',
      '#title' => t('Stillingsbetegnelse'),
      '#readonly' => TRUE,
    ];

    $elements['organisation_enhed'] = [
      '#type' => 'textfield',
      '#title' => t('Organisation enhed'),
      '#readonly' => TRUE,
    ];

    $elements['organisation_adresse'] = [
      '#type' => 'textfield',
      '#title' => t('Organisation enheds adresse'),
      '#readonly' => TRUE,
    ];

    $elements['organisation_niveau_2'] = [
      '#type' => 'textfield',
      '#title' => t('Organisation enhed niveau to'),
      '#readonly' => TRUE,
    ];

    $elements['magistrat'] = [
      '#type' => 'textfield',
      '#title' => t('Magistrat'),
      '#readonly' => TRUE,
    ];

    return $elements;
  }

}
