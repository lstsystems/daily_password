<?php
namespace Drupal\daily_password;

use Drupal\Core\Database\Connection;
use Drupal\Core\Messenger\MessengerInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

class DailyPasswordRepository {

  use MessengerTrait;
  use StringTranslationTrait;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Construct a repository object.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The database connection.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $translation
   *   The translation service.
   * @param \Drupal\Core\Messenger\MessengerInterface $messenger
   *   The messenger service.
   */
  public function __construct(Connection $connection, TranslationInterface $translation, MessengerInterface $messenger) {
    $this->connection = $connection;
    $this->setStringTranslation($translation);
    $this->setMessenger($messenger);
  }

  /**
   * Save an entry in the database.
   *
   * Exception handling is shown in this example. It could be simplified
   * without the try/catch blocks, but since an insert will throw an exception
   * and terminate your application if the exception is not handled, it is best
   * to employ try/catch.
   *
   * @param array $entry
   *   An array containing all the fields of the database record.
   *
   * @return int
   *   The number of updated rows.
   *
   * @throws \Exception
   *   When the database insert fails.
   */
  public function insert(array $entry) {
    try {
      $return_value = $this->connection->insert('daily_password')
        ->fields($entry)
        ->execute();
    }
    catch (\Exception $e) {
      $this->messenger()->addMessage(t('Insert failed. Message = %message', [
        '%message' => $e->getMessage(),
      ]), 'error');
    }
    return $return_value ?? NULL;
  }


  /**
   * Update an entry in the database.
   *
   * @param array $entry
   *   An array containing all the fields of the item to be updated.
   *
   * @return int
   *   The number of updated rows.
   */
  public function update(array $entry) {
    try {
      // Connection->update()...->execute() returns the number of rows updated.
      $count = $this->connection->update('daily_password')
        ->fields($entry)
        ->condition('pid', $entry['pid'])
        ->execute();
    }
    catch (\Exception $e) {
      $this->messenger()->addMessage(t('Update failed. Message = %message, query= %query', [
          '%message' => $e->getMessage(),
          '%query' => $e->query_string,
        ]
      ), 'error');
    }
    return $count ?? 0;
  }


  /**
   * Delete an entry from the database.
   *
   * @param array $entry
   *   An array containing at least the person identifier 'pid' element of the
   *   entry to delete.
   *
   * @see Drupal\Core\Database\Connection::delete()
   */
  public function delete(array $entry) {
    $this->connection->delete('daily_password')
      ->condition('pid', $entry['pid'])
      ->execute();
  }



  /**
   * Read from the database using a filter array.
   *
   * The standard function to perform reads for static queries is
   * Connection::query().
   *
   * Connection::query() uses an SQL query with placeholders and arguments as
   * parameters.
   *
   * Drupal DBTNG provides an abstracted interface that will work with a wide
   * variety of database engines.
   *
   * The following is a query which uses a string literal SQL query. The
   * placeholders will be substituted with the values in the array. Placeholders
   * are marked with a colon ':'. Table names are marked with braces, so that
   * Drupal's' multisite feature can add prefixes as needed.
   *
   * @code
   *   // SELECT * FROM {dbtng_example} WHERE uid = 0 AND name = 'John'
   *   \Drupal::database()->query(
   *     "SELECT * FROM {dbtng_example} WHERE uid = :uid and name = :name",
   *     [':uid' => 0, ':name' => 'John']
   *   )->execute();
   * @endcode
   *
   * For more dynamic queries, Drupal provides Connection::select() API method,
   * so there are several ways to perform the same SQL query. See the
   * @link http://drupal.org/node/310075 handbook page on dynamic queries. @endlink
   * @code
   *   // SELECT * FROM {dbtng_example} WHERE uid = 0 AND name = 'John'
   *   \Drupal::database()->select('dbtng_example')
   *     ->fields('dbtng_example')
   *     ->condition('uid', 0)
   *     ->condition('name', 'John')
   *     ->execute();
   * @endcode
   *
   * Here is select() with named placeholders:
   * @code
   *   // SELECT * FROM {dbtng_example} WHERE uid = 0 AND name = 'John'
   *   $arguments = array(':name' => 'John', ':uid' => 0);
   *   \Drupal::database()->select('dbtng_example')
   *     ->fields('dbtng_example')
   *     ->where('uid = :uid AND name = :name', $arguments)
   *     ->execute();
   * @endcode
   *
   * Conditions are stacked and evaluated as AND and OR depending on the type of
   * query. For more information, read the conditional queries handbook page at:
   * http://drupal.org/node/310086
   *
   * The condition argument is an 'equal' evaluation by default, but this can be
   * altered:
   * @code
   *   // SELECT * FROM {dbtng_example} WHERE age > 18
   *   \Drupal::database()->select('dbtng_example')
   *     ->fields('dbtng_example')
   *     ->condition('age', 18, '>')
   *     ->execute();
   * @endcode
   *
   * @param array $entry
   *   An array containing all the fields used to search the entries in the
   *   table.
   *
   * @return object
   *   An object containing the loaded entries if found.
   *
   * @see Drupal\Core\Database\Connection::select()
   */
  public function load(array $entry = []) {
    // Read all the fields from the daily_password table.
    $select = $this->connection
      ->select('daily_password')
      // Add all the fields into our select query.
      ->fields('daily_password');

    // Add each field and value as a condition to this query.
    foreach ($entry as $field => $value) {
      $select->condition($field, $value);
    }
    // Return the result in object format.
    return $select->execute()->fetchAll();
  }


}