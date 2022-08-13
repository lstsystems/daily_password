<?php

namespace Drupal\daily_password;

use Drupal\Core\Config\ConfigFactoryInterface;

class ConfigData
{
  /**
   * Config settings.
   *
   * @var ConfigFactoryInterface
   */
  protected ConfigFactoryInterface $settings;


  /**
   * Get all config data
   * ConfigData constructor.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->settings =  $config_factory;
  }


  public function get_run_time() {
    return $this->settings->getEditable('daily_password.settings')->get('last_run');
  }


  public function set_run_time($value) {
    $this->settings->getEditable('daily_password.settings')->set('last_run', $value)
      ->save();
  }
}
