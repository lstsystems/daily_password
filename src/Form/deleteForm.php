<?php


namespace Drupal\daily_password\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\daily_password\DailyPasswordRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Messenger\MessengerTrait;

class deleteForm extends FormBase {


  use StringTranslationTrait;
  use MessengerTrait;

  /**
   * @var AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;


  /**
   * @var DailyPasswordRepository
   */
  private DailyPasswordRepository $repository;

  /**
   *
   * @param ContainerInterface $container
   * @return deleteForm|static
   */
  public static function create(ContainerInterface $container): deleteForm|static
  {
    $form = new static(
      $container->get('daily_password.repository'),
      $container->get('current_user')
    );
    // The StringTranslationTrait trait manages the string translation service
    // for us. We can inject the service here.
    $form->setStringTranslation($container->get('string_translation'));
    $form->setMessenger($container->get('messenger'));
    return $form;
  }


  /**
   * @param DailyPasswordRepository $repository
   * @param AccountProxyInterface $current_user
   */
  public function __construct(DailyPasswordRepository $repository, AccountProxyInterface $current_user) {
    $this->repository = $repository;
    $this->currentUser = $current_user;
  }

  /**
   * @return string
   */
  public function getFormId(): string
  {
    return 'delete_form';
  }


  /**
   *
   * @param array $form
   * @param FormStateInterface $form_state
   * @param null $delete
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state, $delete = NULL): array
  {

    $form['message'] = [
      '#markup' => $this->t('This action cannot be undone.'),
      '#suffix' => '<br />',
    ];
    $form['pid'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Form ID'),
      '#size' => 15,
      '#value' => $delete,
      '#disabled' => TRUE,
    ];
    $form['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Delete'),
    ];
    $form['actions']['cancel'] = [
      '#type' => 'submit',
      '#value' => $this->t('Cancel'),
      '#button_type' => 'secondary',
      '#attributes' => [
        'onClick' => 'javascript:window.history.go(-1); return false;'
      ],

    ];

    return $form;
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // Verify that the user is logged-in.
    if ($this->currentUser->isAnonymous()) {
      $form_state->setError($form['edit'], $this->t('You must be logged in to add values to the database.'));
    }
  }

  /**
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {


    // Gather the current user so the new record has ownership.
    $account = $this->currentUser;
    // Save the submitted entry.
    $entry = [
      'pid' => $form_state->getValue('pid'),
    ];
    $this->repository->delete($entry);
    $form_state->setRedirect('daily_password.form_table');
    $this->messenger()->addMessage($this->t('Deleted entry @entry', [
      '@entry' => print_r($entry['usernames'], TRUE),
    ]));
  }

}
