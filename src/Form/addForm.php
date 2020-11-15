<?php
namespace Drupal\daily_password\Form;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\daily_password\dailyPasswordRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;

class addForm implements FormInterface, ContainerInjectionInterface {

  use StringTranslationTrait;
  use MessengerTrait;

  /**
   * Our database repository service.
   *
   * @var \Drupal\daily_password\dailyPasswordRepository
   */
  protected $repository;

  /**
   * The current user.
   *
   * We'll need this service in order to check if the user is logged in.
   *
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   *
   * We'll use the ContainerInjectionInterface pattern here to inject the
   * current user and also get the string_translation service.
   */
  public static function create(ContainerInterface $container) {
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
   * Construct the new form object.
   */
  public function __construct(dailyPasswordRepository $repository, AccountProxyInterface $current_user) {
    $this->repository = $repository;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'add_form';
  }


  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
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

    $form['submit'] = [
      '#type' => 'submit',
      '#button_type' => 'primary',
      '#value' => $this->t('Add'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
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
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Gather the current user so the new record has ownership.
    $account = $this->currentUser;
    // Save the submitted entry.
    $entry = [
      'usernames' => $form_state->getValue('usernames'),
      'frequency' => $form_state->getValue('frequency'),
      'password' => t('Password not yet set'),
    ];
    $return = $this->repository->insert($entry);
    $form_state->setRedirect('daily_password.form_table');
    if ($return) {
      $this->messenger()->addMessage($this->t('Created entry @entry', ['@entry' => print_r($entry['usernames'], TRUE)]));
    }
  }

}