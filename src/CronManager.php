<?php /** @noinspection PhpUnused */

namespace Drupal\daily_password;

use Drupal\Core\Cache\CacheBackendInterface;

class CronManager
{
  private PasswordManager $passwordManager;
  private UserManager $userManager;
  private ConfigData $configData;
  private CacheBackendInterface $cacheBackend;

  /**
   * Cron Manager constructor
   * @param PasswordManager $passwordManager
   * @param UserManager $userManager
   * @param ConfigData $configData
   * @param CacheBackendInterface $cacheBackend
   */
  public function __construct(PasswordManager $passwordManager, UserManager $userManager, ConfigData $configData, CacheBackendInterface $cacheBackend)
  {
    $this->passwordManager = $passwordManager;
    $this->userManager = $userManager;
    $this->configData = $configData;
    $this->cacheBackend = $cacheBackend;
  }

  /**
   * Setup cron and filter per settings
   */
  private function filter() : void {

    $midnight = strtotime('today midnight');

    // Call usernames from database and store the object
    $tableObjects =  $this->userManager->getUserNames();

    //iterate over usernames object
    foreach ($tableObjects as $object) {
      //If set for Daily this will always run
      if ($object->frequency == 'Daily') {

        // Apply and store password
        $this->passwordManager->passwordSetter($object->usernames, $object->pid);

      }
      //If set for Weekly this will run too
      if ($object->frequency == 'Weekly' && date('D') == 'Sun') {

        // Apply and store password
        $this->passwordManager->passwordSetter($object->usernames, $object->pid);

      }
      //If set for Monthly this will run too
      if ($object->frequency == 'Monthly' && date('d') == 01) {

        // Apply and store password
        $this->passwordManager->passwordSetter($object->usernames, $object->pid);

      }
      //If set for Yearly this will run too
      if ($object->frequency == 'Yearly' && date('z') == 0) {

        // Apply and store password
        $this->passwordManager->passwordSetter($object->usernames, $object->pid);

      }

    }

    // Check if httpError is false before setting run time
    if (!$this->passwordManager->getHttpErrorStatus()) {
      // Set run time stamp on config file after all functions have run
      $this->configData->set_run_time($midnight);
    }

  }

  /**
   * Run final cron daily
   */
  public function cron(): void {
    //get configuration last run time stamp
    $getSettingsLastRun = $this->configData->get_run_time();

    //check for last run and configure day to run
    if($getSettingsLastRun < strtotime('-1 days')) {

      // Run all functions
      $this->filter();

      //clear render cache so block will display up to date information
      $renderCache = $this->cacheBackend->invalidateAll();
      $renderCache->invalidateAll();

    }
  }

}
