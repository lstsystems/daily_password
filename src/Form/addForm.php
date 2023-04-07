<?php
namespace Drupal\daily_password\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\daily_password\DailyPasswordRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

class addForm implements FormInterface, ContainerInjectionInterface {

  use StringTranslationTrait;
  use MessengerTrait;

  /**
   * Database repository
   * @var DailyPasswordRepository
   */
  protected DailyPasswordRepository $repository;


  /**
   * The current user.
   * We'll need this service in order to check if the user is logged in.
   * @var AccountProxyInterface
   */
  protected AccountProxyInterface $currentUser;

  /**
   * {@inheritdoc}
   *
   * We'll use the ContainerInjectionInterface pattern here to inject the
   * current user and also get the string_translation service.
   */
  public static function create(ContainerInterface $container): static
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
   * Constructor
   * @param DailyPasswordRepository $repository
   * @param AccountProxyInterface $current_user
   */
  public function __construct(DailyPasswordRepository $repository, AccountProxyInterface $current_user) {
    $this->repository = $repository;
    $this->currentUser = $current_user;
  }

  /**
   * form id
   * @return string
   */
  public function getFormId(): string
  {
    return 'add_form';
  }



  /**
   * Create form
   * @param array $form
   * @param FormStateInterface $form_state
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $form = [];

    $form['message'] = [
      '#markup' => $this->t('Add an entry to the daily password table.'),
    ];

    $form['add'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Add a user entry'),
    ];
    $form['add']['usernames'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Usernames'),
      '#size' => 255,
      '#description' => $this->t('You can add a single user name or if you want to have multiple users sharing the same password add them separated by a comma.'),
    ];
    $form['add']['frequency'] = [
      '#type' => 'radios',
      '#title' => $this->t('Frequency'),
      '#options' => array(
        'Daily' => $this->t('Daily'),
        'Weekly' => $this->t('Weekly'),
        'Monthly' => $this->t('Monthly'),
        'Yearly' => $this->t('Yearly'),
      ),
      '#description' => $this->t('Select the frequency for the password update time frame.'),
    ];

      $form['remote'] = [
          '#type' => 'details',
          '#title' => $this->t('Remote submission'),
      ];

      $form['remote']['send'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Send to remote API.'),
          '#default_value' => false,
          '#description' => $this->t('Please select this checkbox to indicate whether you want to send the password to a remote API.'),
      ];


      $form['remote']['url'] = [
          '#type' => 'textfield',
          '#title' => $this->t('API Url'),
          '#size' => 255,
          '#description' => $this->t('Please provide the web address to the API endpoint, making sure to include the full http:// or https:// '),
      ];


      $form['remote']['header'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Header key'),
          '#size' => 255,
          '#description' => $this->t('Please specify the request header for the API token. If no header is provided, the default "x-api-token" will be used.'),
      ];

      $form['remote']['token'] = [
          '#type' => 'textfield',
          '#title' => $this->t('API Token'),
          '#size' => 255,
          '#description' => $this->t('Consider providing a security token in this field, as it is strongly recommended.'),
      ];


      $form['remote']['jsonkey'] = [
          '#type' => 'textfield',
          '#title' => $this->t('API JSON payload key'),
          '#size' => 255,
          '#description' => $this->t('Please specify the JSON key to be used for the endpoint. If no key is provided, the default key "password" will be used.'),
      ];


      $form['actions'] = [
          '#type' => 'actions',
      ];

      $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Add'),
          '#button_type' => 'primary',
      ];

    return $form;
  }

  /**
   * Form validation
   * @param array $form
   * @param FormStateInterface $form_state
   */
  public function validateForm(array &$form, FormStateInterface $form_state): void {
    // Verify that the user is logged-in.
    if ($this->currentUser->isAnonymous()) {
      $form_state->setError($form['add'], $this->t('You must be logged in to add values to the database.'));
    }
    // Confirm that username is not empty.
    elseif (empty($form_state->getValue('usernames'))) {
      $form_state->setErrorByName('usernames', $this->t('Username can not be empty'));
    }

    // Confirm that frequency is not empty.
    elseif (empty($form_state->getValue('frequency'))) {
      $form_state->setErrorByName('frequency', $this->t('Frequency must be selected'));
    }
  }

  /**
   * Submit the form
   * @param array $form
   * @param FormStateInterface $form_state
   * @throws \Exception
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    // Gather the current user so the new record has ownership.
    $account = $this->currentUser;
    // Save the submitted entry.
    $entry = [
        'usernames' => $form_state->getValue('usernames'),
        'frequency' => $form_state->getValue('frequency'),
        'password' => t('Password not yet set'),
        'send' => $form_state->getValue('send'),
        'url' => $form_state->getValue('url'),
        'header' => $form_state->getValue('header'),
        'token' => $form_state->getValue('token'),
        'jsonkey' => $form_state->getValue('jsonkey'),
    ];
    $return = $this->repository->insert($entry);
    $form_state->setRedirect('daily_password.form_table');
    if ($return) {
      $this->messenger()->addMessage($this->t('Created entry @entry', ['@entry' => print_r($entry['usernames'], TRUE)]));
    }
  }

}
