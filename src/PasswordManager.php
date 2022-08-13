<?php


namespace Drupal\daily_password;


use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;



class PasswordManager
{


  private EntityTypeManagerInterface  $entityTypeManager;
  private LoggerChannelFactoryInterface $logger;
  private Connection $connection;
  private PasswordGenerator $passwordGenerator;
  private $user;

  /**
   * PasswordManager constructor.
   *
   * @param EntityTypeManagerInterface  $entityTypeManager
   * @param LoggerChannelFactoryInterface $logger
   * @param Connection $connection
   * @param PasswordGenerator $passwordGenerator
   */
  public function __construct(
    EntityTypeManagerInterface  $entityTypeManager,
                              LoggerChannelFactoryInterface $logger,
                              Connection $connection,
                              PasswordGenerator $passwordGenerator) {

    $this->entityTypeManager = $entityTypeManager;
    $this->logger = $logger;
    $this->connection = $connection;
    $this->passwordGenerator = $passwordGenerator;

  }






  /**
   * Sets the password for the users
   */
  private function changeUserPassword($userNames, $password): void {
    foreach ($userNames as $name) {

      try {
        //$user = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $name]);
        $this->user = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $name]);
      } catch (InvalidPluginDefinitionException $e) {
        $this->logger->get('daily_password')->error('InvalidPluginDefinitionException '.$e);
      } catch (PluginNotFoundException $e) {
        $this->logger->get('daily_password')->error('PluginNotFoundException '.$e);
      }




      if (count($this->user) == 1) {
        //get the user from the array.
        $user = reset($this->user);
        $user->setPassword($password);
        $user->save();
      } else {
        $this->logger->get('daily_password')->error('Username "'.$name.'" is incorrect or does not exists' );
      }



    }
  }


  /**
   * Store password in database
   * Old Name: _daily_password_store_password
   * @param $pid
   * @param $password
   */
  private function databasePasswordStorage($pid, $password): void {

    $query = $this->connection->update('{daily_password}');
    $query->fields([
      'password' => $password,
    ]);
    $query->condition('pid', $pid);
    $query->execute();
  }


  /**
   * run set password for user and database
   * @param $userNames
   * @param $pid
   */
  public function passwordSetter($userNames,  $pid) {

    //trim and store as array for multiple usernames to pass into the function
    $userNames = array_map('trim', explode(',', $userNames));

    //Get password from generator
    $password = $this->passwordGenerator->securedPassword();

    //run password change function for users
    $this->changeUserPassword($userNames, $password['plain']);

    //store password in database
    $this->databasePasswordStorage($pid, $password['secured']);


  }


}
