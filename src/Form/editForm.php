<?php
namespace Drupal\daily_password\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\daily_password\dailyPasswordRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Messenger\MessengerTrait;


class editForm extends FormBase {

  use StringTranslationTrait;
  use MessengerTrait;

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
    return 'edit_form';
  }



  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state, $edit = NULL) {

    // Query for items to display.
    foreach ($entries = $this->repository->load() as $entry) {

      $pid = [];
      $pid = $entry->pid;
      //if form pid matches the route id store variables
      if (!strcmp($pid , $edit)) {
        $username = $entry->usernames;
        $frequency = $entry->frequency;
      }

    }

    $form = [];

    $form['message'] = [
      '#markup' => $this->t('Edit entry in the daily password table.'),
    ];
    $form['edit'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Edit a user entry'),
    ];
    $form['edit']['pid'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Form ID'),
      '#size' => 15,
      '#value' => $edit,
      '#disabled' => TRUE,
    ];

    $form['edit']['usernames'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Usernames'),
      '#size' => 255,
      '#default_value' => $username,
      '#description' => $this->t('You can add a single user name or if you want to have multiple users sharing the same password add them separated by a comma.'),
    ];
    $form['edit']['frequency'] = [
      '#type' => 'radios',
      '#title' => $this->t('Frequency'),
      '#default_value' => $frequency,
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
      '#value' => $this->t('Edit'),
    ];



    return $form;
  }
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Verify that the user is logged-in.
    if ($this->currentUser->isAnonymous()) {
      $form_state->setError($form['edit'], $this->t('You must be logged in to add values to the database.'));
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
      'pid' => $form_state->getValue('pid'),
      'usernames' => $form_state->getValue('usernames'),
      'frequency' => $form_state->getValue('frequency'),
    ];
    $count = $this->repository->update($entry);
    $form_state->setRedirect('daily_password.form_table');
    $this->messenger()->addMessage($this->t('Updated entry @entry (@count row updated)', [
      '@count' => $count,
      '@entry' => print_r($entry['usernames'], TRUE),
    ]));
  }


}