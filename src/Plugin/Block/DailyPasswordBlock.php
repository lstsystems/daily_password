<?php

namespace Drupal\daily_password\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Form\FormStateInterface;


/**
 * Provides a 'DailyPasswordBlock' block.
 *
 * @Block(
 *  id = "daily_password_block",
 *  admin_label = @Translation("Daily Password"),
 * )
 */
class DailyPasswordBlock extends BlockBase {



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
    $query = \Drupal::database()->select('daily_password', 'n');
    $query->fields('n', ['pid', 'usernames', 'frequency']);
    $results = $query->execute()->fetchAll();

    //push date as a key value pair to the new array
    foreach ($results as $entry) {
      $options[$entry->pid]=$entry->usernames;
    }


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

    $pid =  $this->configuration['select_password_to_display'];



    $query = \Drupal::database()->select('daily_password', 'n');
    $query->condition('pid', $pid);
    $query->fields('n', ['password']);
    $results = $query->execute()->fetch();

    //$hash = \Drupal::config('daily_password.settings')->get('password_hash');

    // Get hash from setting.php
    $hash =  \Drupal\Core\Site\Settings::getHashSalt();

    $deccryptPassword = openssl_decrypt($results->password,"AES-256-ECB",$hash);

    return [
      //'#theme' => 'daily_password_block',
      '#markup' => $deccryptPassword,
    ];

  }

}
