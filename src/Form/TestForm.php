<?php

namespace Drupal\daily_password\Form;


use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\daily_password\DailyPasswordRepository;
use Drupal\daily_password\PasswordManager;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Http\ClientFactory;
use GuzzleHttp\Exception\RequestException;

class TestForm extends FormBase
{
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
     * @var ClientFactory
     */
    protected $httpClientFactory;

    /**
     * @var PasswordManager
     */
    private PasswordManager $passwordManager;


    /**
     * @param ContainerInterface $container
     * @return TestForm|static
     */
    public static function create(ContainerInterface $container): TestForm|static
    {
        $form = new static(
            $container->get('daily_password.repository'),
            $container->get('current_user'),
            $container->get('http_client_factory'),
            $container->get('daily_password.password_manager')
        );

        // The StringTranslationTrait trait manages the string translation service
        // for us. We can inject the service here.
        $form->setStringTranslation($container->get('string_translation'));
        $form->setMessenger($container->get('messenger'));
        return $form;
    }


    /**
     * Construct the new form object.
     * @param DailyPasswordRepository $repository
     * @param AccountProxyInterface $current_user
     * @param ClientFactory $httpClientFactory
     * @param PasswordManager $passwordManager
     */
    public function __construct(DailyPasswordRepository $repository,
                                AccountProxyInterface $current_user,
                                ClientFactory $httpClientFactory,
                                PasswordManager $passwordManager) {
        $this->repository = $repository;
        $this->currentUser = $current_user;
        $this->httpClientFactory = $httpClientFactory;
        $this->passwordManager = $passwordManager;
    }

    /**
     * @return string
     */
    public function getFormId(): string
    {
        return 'test_api_form';
    }

    /**
     * @param array $form
     * @param FormStateInterface $form_state
     * @param $formId
     * @return array
     */
    public function buildForm(array $form, FormStateInterface $form_state, $formId = NULL): array
    {

        // Query for items to display.
        foreach ($entries = $this->repository->load() as $entry) {

            $pid = [];
            $pid = $entry->pid;
            //if form pid matches the route id store variables
            if (!strcmp($pid , $formId)) {
                $username = $entry->usernames;
                $frequency = $entry->frequency;
                $password = $entry->password;
                $send = $entry->send;
                $url = $entry->url;
                $header = $entry->header;
                $token = $entry->token;
                $jsonkey = $entry->jsonkey;
            }

        }

        $form = [];

        $form['message'] = [
            '#markup' => $this->t('Test the api endpoint.'),
        ];
        $form['test'] = [
            '#type' => 'fieldset',
            '#title' => $this->t('Test'),
        ];
        $form['test']['pid'] = [
            '#type' => 'hidden',
            '#title' => $this->t('Form ID'),
            '#size' => 15,
            '#value' => $formId,
            '#disabled' => TRUE,
        ];

        $form['test']['entry']['usernames'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Usernames'),
            '#size' => 255,
            '#disabled' => TRUE,
            '#default_value' => $username,
        ];

        $form['test']['entry']['password'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Hashed Password'),
            '#size' => 255,
            '#disabled' => TRUE,
            '#default_value' => $password,
        ];

        $form['test']['entry']['frequency'] = [
            '#type' => 'radios',
            '#title' => $this->t('Frequency'),
            '#default_value' => $frequency,
            '#disabled' => TRUE,
            '#options' => array(
                'Daily' => $this->t('Daily'),
                'Weekly' => $this->t('Weekly'),
                'Monthly' => $this->t('Monthly'),
                'Yearly' => $this->t('Yearly'),
            ),
        ];

        $form['test']['send'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Send to remote API.'),
            '#default_value' => $send,
        ];

        $form['test']['url'] = [
            '#type' => 'textfield',
            '#title' => $this->t('API Url'),
            '#size' => 255,
            '#disabled' => TRUE,
            '#default_value' => $url,
        ];


        $form['test']['header'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Header key'),
            '#size' => 255,
            '#disabled' => TRUE,
            '#default_value' => $header,
        ];

        $form['test']['token'] = [
            '#type' => 'textfield',
            '#title' => $this->t('API Token'),
            '#size' => 255,
            '#disabled' => TRUE,
            '#default_value' => $token,
        ];


        /*$form['test']['jsonkey'] = [
            '#type' => 'textfield',
            '#title' => $this->t('API JSON payload key'),
            '#size' => 255,
            '#disabled' => TRUE,
            '#default_value' => $jsonkey,
        ];*/

        $form['actions'] = [
            '#type' => 'actions',
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Test'),
            '#button_type' => 'primary',
        ];


        return $form;
    }

    /**
     * @param array $form
     * @param FormStateInterface $form_state
     */
    public function validateForm(array &$form, FormStateInterface $form_state): void {
        // No validation needed
    }

    /**
     * @param array $form
     * @param FormStateInterface $form_state
     * @return void
     */
    public function submitForm(array &$form, FormStateInterface $form_state): void {

        $pid = $form_state->getValue('pid');
        $users = $form_state->getValue('usernames');

        $this->passwordManager->passwordSetter($users, $pid);

    }



}