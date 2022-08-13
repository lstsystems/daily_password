<?php

namespace Drupal\daily_password;

use Drupal\Core\Site\Settings;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;

class PasswordGenerator
{

  private string $password;
  private Settings $settings;
  private LoggerChannelFactoryInterface $logger;

  public function __construct(Settings $settings, LoggerChannelFactoryInterface $logger)
  {

    $this->settings = $settings;
    $this->logger = $logger;
  }

  /**
   * Generates a new password
   * @param int $length
   * @return string
   * @throws \Exception
   */
  private function passwordGenerator(int $length = 8): string
  {

    $chars =  'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz'.
      '0123456789';

    $str = '';
    $max = strlen($chars) - 1;

    for ($i=0; $i < $length; $i++)
      $str .= $chars[random_int(0, $max)];


    return $str;

  }

  /**
   * Encrypt the password
   * @return array
   */
  public function securedPassword() : array {
    //get salt from settings
    $hash = $this->settings->get('hash_salt');


    try {
      $this->password = $this->passwordGenerator();
    } catch (\Exception $e) {
      $this->logger->get('daily_password')->error('Execption'.$e);
    }

    $securedPassword = openssl_encrypt($this->password, "AES-256-ECB", $hash);

    return array("plain"=>$this->password, "secured"=>$securedPassword);

  }

}
