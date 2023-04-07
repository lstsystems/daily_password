<?php
namespace Drupal\daily_password\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\daily_password\dailyPasswordRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Url;



class formTableController extends ControllerBase {


  /**
   * The repository for our specialized queries.
   * @var dailyPasswordRepository|\Drupal\dbtng_example\dailyPasswordRepository
   */
  protected dailyPasswordRepository $repository;



  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container): formTableController|static
  {
    $controller = new static($container->get('daily_password.repository'));
    $controller->setStringTranslation($container->get('string_translation'));
    return $controller;
  }


  /**
   * Constructor
   * @param dailyPasswordRepository $repository
   */
  public function __construct(dailyPasswordRepository $repository) {
    $this->repository = $repository;
  }


  /**
   * Render a list of entries in the database.
   * @return array
   */
  public function formTableContent(): array
  {

    $content = [];

    $rows = [];
    $headers = [
      $this->t('Usernames'),
      $this->t('Frequency'),
      $this->t('Operations'),
    ];

    foreach ($entries = $this->repository->load() as $entry) {
      $row = [];
      $row[] = $entry->usernames;
      $row[] = $entry->frequency;
      $fid = [];
      $fid = $entry->pid;
      $links = [];
      $links['Edit'] = [
        'title' => $this->t('Edit'),
        'url' => Url::fromRoute('daily_password.edit_form', ['edit' => $fid]),
      ];
      $links['test'] = [
        'title' => $this->t('Test'),
        'url' => Url::fromRoute('daily_password.test_form', ['formId' => $fid]),
      ];
      $links['delete'] = [
        'title' => $this->t('Delete'),
        'url' => Url::fromRoute('daily_password.delete_form', ['delete' => $fid]),
      ];
      $row[] = [
        'data' => [
          '#type' => 'operations',
          '#links' => $links,
        ],
      ];

      $rows[] = $row;


    }

    $content['table'] = [
      '#type' => 'table',
      '#header' => $headers,
      '#rows' => $rows,
      '#empty' => $this->t('No entries available.'),
    ];


    // Don't cache this page.
    $content['#cache']['max-age'] = 0;



    return $content;
  }


}
