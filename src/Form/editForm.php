<?php
namespace Drupal\daily_password\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\daily_password\DailyPasswordRepository;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Messenger\MessengerTrait;


class editForm extends FormBase {

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
   * @param ContainerInterface $container
   * @return editForm|static
   */
  public static function create(ContainerInterface $container): editForm|static
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
   * Construct the new form object.
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
    return 'edit_form';
  }


  /**
   * @param array $form
   * @param FormStateInterface $form_state
   * @param null $edit
   * @return array
   */
  public function buildForm(array $form, FormStateInterface $form_state, $edit = NULL): array
  {

    // Query for items to display.
    foreach ($entries = $this->repository->load() as $entry) {

      $pid = [];
      $pid = $entry->pid;
      //if form pid matches the route id store variables
      if (!strcmp($pid , $edit)) {
        $username = $entry->usernames;
        $frequency = $entry->frequency;
        $send = $entry->send;
        $url = $entry->url;
        $header = $entry->header;
        $token = $entry->token;
        $jsonkey = $entry->jsonkey;
      }

    }

    $form = [];

    $form['message'] = [
      '#markup' => $this->t('Edit entry in the daily password table.'),
    ];
    $form['edit'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Edit entry'),
    ];
    $form['edit']['pid'] = [
      '#type' => 'hidden',
      '#title' => $this->t('Form ID'),
      '#size' => 15,
      '#value' => $edit,
      '#disabled' => TRUE,
    ];

      $form['edit']['entry']['usernames'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Usernames'),
          '#size' => 255,
          '#default_value' => $username,
          '#description' => $this->t('You can enter a single user name in the password field. Alternatively, if you want multiple users to share the same password, you can add their names separated by a comma.'),
      ];
      $form['edit']['entry']['frequency'] = [
          '#type' => 'radios',
          '#title' => $this->t('Frequency'),
          '#default_value' => $frequency,
          '#options' => array(
              'Daily' => $this->t('Daily'),
              'Weekly' => $this->t('Weekly'),
              'Monthly' => $this->t('Monthly'),
              'Yearly' => $this->t('Yearly'),
          ),
          '#description' => $this->t('Please choose how often you would like to update the password.'),
      ];

      $form['remote'] = [
          '#type' => 'details',
          '#title' => $this->t('Remote submission'),
      ];

      $form['remote']['send'] = [
          '#type' => 'checkbox',
          '#title' => $this->t('Send to remote API.'),
          '#default_value' => $send,
          '#description' => $this->t('Please select this checkbox to indicate whether you want to send the password to a remote API.'),
      ];


      $form['remote']['url'] = [
          '#type' => 'textfield',
          '#title' => $this->t('API Url'),
          '#size' => 255,
          '#default_value' => $url,
          '#description' => $this->t('Please provide the web address to the API endpoint, making sure to include the full http:// or https:// '),
      ];


      $form['remote']['header'] = [
          '#type' => 'textfield',
          '#title' => $this->t('Header key'),
          '#size' => 255,
          '#default_value' => $header,
          '#description' => $this->t('Please specify the request header for the API token. If no header is provided, the default "x-api-token" will be used. If no token is given no header will be used.'),
      ];

      $form['remote']['token'] = [
          '#type' => 'textfield',
          '#title' => $this->t('API Token'),
          '#size' => 255,
          '#default_value' => $token,
          '#description' => $this->t('Consider providing a security token in this field, as it is strongly recommended.'),
      ];


      /*$form['remote']['jsonkey'] = [
          '#type' => 'textfield',
          '#title' => $this->t('API JSON payload key'),
          '#size' => 255,
          '#default_value' => $jsonkey,
          '#description' => $this->t('Please specify the JSON key to be used for the endpoint. If no key is provided, the default key "password" will be used.'),
      ];*/

      $form['actions'] = [
          '#type' => 'actions',
      ];

      $form['actions']['submit'] = [
          '#type' => 'submit',
          '#value' => $this->t('Edit'),
          '#button_type' => 'primary',
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
    // Confirm that username is not empty.
    elseif (empty($form_state->getValue('usernames'))) {
      $form_state->setErrorByName('usernames', $this->t('Username can not be empty'));
    }

    // Confirm that frequency is not empty.
    elseif (empty($form_state->getValue('frequency'))) {
      $form_state->setErrorByName('frequency', $this->t('Frequency must be selected'));
    }

    // Confirm that URL is not empty if send is marked true.
    elseif ($form_state->getValue('send') && empty($form_state->getValue('url'))) {
        $form_state->setErrorByName('url', $this->t('URL can not be empty if send is marked true'));
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
        'usernames' => $form_state->getValue('usernames'),
        'frequency' => $form_state->getValue('frequency'),
        'send' => $form_state->getValue('send'),
        'url' => $form_state->getValue('url'),
        'header' => $form_state->getValue('header'),
        'token' => $form_state->getValue('token'),
        'jsonkey' => $form_state->getValue('jsonkey'),
    ];
    $count = $this->repository->update($entry);
    $form_state->setRedirect('daily_password.form_table');
    $this->messenger()->addMessage($this->t('Updated entry @entry (@count row updated)', [
      '@count' => $count,
      '@entry' => print_r($entry['usernames'], TRUE),
    ]));
  }


}
