<?php

namespace Drupal\daily_password;

use Drupal\Core\Database\Connection;

class UserManager
{
  private Connection $connection;

  /**
   * Constructor
   * @param Connection $connection
   */
  public function __construct(Connection $connection)
  {
    $this->connection = $connection;
  }

  /**
   * Get all rows in the table
   * @return mixed
   */
  public function getUserNames(): mixed
  {

    $query = $this->connection->select('daily_password', 'n');
    $query->fields('n', ['pid', 'usernames', 'frequency']);
    return $query->execute()->fetchAll();

  }
}
