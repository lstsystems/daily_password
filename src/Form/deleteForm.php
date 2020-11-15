<?php


namespace Drupal\daily_password\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\daily_password\dailyPasswordRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Messenger\MessengerTrait;

class deleteForm extends FormBase {


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
    return 'delete_form';
  }

  //need to add correct form build
  public function buildForm(array $form, FormStateInterface $form_state, $delete = NULL) {

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
   * {@inheritdoc}
   */
  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Verify that the user is logged-in.
    if ($this->currentUser->isAnonymous()) {
      $form_state->setError($form['edit'], $this->t('You must be logged in to add values to the database.'));
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
    ];
    $this->repository->delete($entry);
    $form_state->setRedirect('daily_password.form_table');
    $this->messenger()->addMessage($this->t('Deleted entry @entry', [
      '@entry' => print_r($entry['usernames'], TRUE),
    ]));
  }

}