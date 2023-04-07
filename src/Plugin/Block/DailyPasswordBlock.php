<?php

namespace Drupal\daily_password\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\daily_password\DailyPasswordRepository;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Site\Settings;

/**
 * Provides a 'DailyPasswordBlock' block.
 *
 * @Block(
 *  id = "daily_password_block",
 *  admin_label = @Translation("Daily Password"),
 * )
 */
class DailyPasswordBlock extends BlockBase implements ContainerFactoryPluginInterface {

  protected $repository;

  protected $settings;

   public function __construct(array $configuration, $plugin_id, $plugin_definition, dailyPasswordRepository $repository,Settings $settings) {
     parent::__construct($configuration, $plugin_id, $plugin_definition);
     $this->repository = $repository;
     $this->settings = $settings;
   }

  /**
   * @param \Symfony\Component\DependencyInjection\ContainerInterface $container
   * @param array $configuration
   * @param string $plugin_id
   * @param mixed $plugin_definition
   *
   * @return \Drupal\Core\Plugin\ContainerFactoryPluginInterface|\Drupal\daily_password\Plugin\Block\DailyPasswordBlock
   */
  public static function create(ContainerInterface $container,array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('daily_password.repository'),
      $container->get('settings')
    );
  }



  /**
   * {@inheritdoc}
   */
  public function defaultConfiguration() {
    return [
    ] + parent::defaultConfiguration();
  }



  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {


    //setup empty array
    $options = array();

    //get deta from database
    $entries = $this->repository->load();

    //push date as a key value pair to the new array
    foreach ($entries as $entry) {
      $options[$entry->pid]=$entry->usernames;
    }

    //Setup form
    $form['select_password_to_display'] = [
      '#type' => 'radios',
      '#title' => $this->t('Select password to display'),
      '#options' => $options,
      '#description' => t('You can only select one, to select another create a new instance of this block'),
      '#default_value' => $this->configuration['select_password_to_display'],
    ];

    return $form;


  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    $this->configuration['select_password_to_display'] = $form_state->getValue('select_password_to_display');
  }

  /**
   * {@inheritdoc}
   */
  public function build() {


    // setup pid condition
    $pid =  ['pid' => $this->configuration['select_password_to_display']];

    // load by pid and rest array to an object
    $entries = $this->repository->load($pid);
    $entries = reset($entries);


    // Get hash from setting.php
    $hash = $this->settings->getHashSalt();
    //$hash =  \Drupal\Core\Site\Settings::getHashSalt();

    // decrypt the password before passing it to the block
    $deccryptPassword = openssl_decrypt($entries->password,"AES-256-ECB",$hash);

    return [
      '#markup' => $deccryptPassword,
    ];

  }

}
