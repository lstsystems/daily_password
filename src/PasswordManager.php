<?php


namespace Drupal\daily_password;


use Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException;
use Drupal\Component\Plugin\Exception\PluginNotFoundException;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Logger\LoggerChannelFactoryInterface;
use Drupal\Core\Http\ClientFactory;
use GuzzleHttp\Exception\RequestException;


class PasswordManager
{


    private EntityTypeManagerInterface $entityTypeManager;
    private LoggerChannelFactoryInterface $logger;
    private Connection $connection;
    private PasswordGenerator $passwordGenerator;
    private ClientFactory $httpClientFactory;
    private $user;

    /**
     * PasswordManager constructor.
     *
     * @param EntityTypeManagerInterface $entityTypeManager
     * @param LoggerChannelFactoryInterface $logger
     * @param Connection $connection
     * @param PasswordGenerator $passwordGenerator
     */
    public function __construct(
        EntityTypeManagerInterface    $entityTypeManager,
        LoggerChannelFactoryInterface $logger,
        Connection                    $connection,
        PasswordGenerator             $passwordGenerator, ClientFactory $httpClientFactory)
    {

        $this->entityTypeManager = $entityTypeManager;
        $this->logger = $logger;
        $this->connection = $connection;
        $this->passwordGenerator = $passwordGenerator;
        $this->httpClientFactory = $httpClientFactory;

    }


    /**
     * Sets the password for the users
     */
    private function changeUserPassword($userNames, $password): void
    {
        foreach ($userNames as $name) {

            try {
                //$user = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $name]);
                $this->user = $this->entityTypeManager->getStorage('user')->loadByProperties(['name' => $name]);
            } catch (InvalidPluginDefinitionException $e) {
                $this->logger->get('daily_password')->error('InvalidPluginDefinitionException ' . $e);
            } catch (PluginNotFoundException $e) {
                $this->logger->get('daily_password')->error('PluginNotFoundException ' . $e);
            }


            if (count($this->user) == 1) {
                //get the user from the array.
                $user = reset($this->user);
                $user->setPassword($password);
                $user->save();
            } else {
                $this->logger->get('daily_password')->error('Username "' . $name . '" is incorrect or does not exists');
            }


        }
    }


    /**
     * Store password in database
     * Old Name: _daily_password_store_password
     * @param $pid
     * @param $password
     */
    private function databasePasswordStorage($pid, $password): void
    {

        $query = $this->connection->update('{daily_password}');
        $query->fields([
            'password' => $password,
        ]);
        $query->condition('pid', $pid);
        $query->execute();
    }

    /**
     * Evaluate if a post request most be made and send the data
     * @param $pid
     * @param $password
     * @return void
     */

    private function performPostRequest($pid, $password)
    {

        $query = $this->connection->select('daily_password', 'n')
            ->fields('n', ['pid', 'usernames', 'frequency', 'url', 'header', 'token', 'jsonkey', 'send'])
            ->condition('n.pid', $pid)
            ->execute()
            ->fetchAll();

        // check that query, and url ar not empty and send is set to 1 which is active
        if (!empty($query) && $query[0]->send === '1' && !empty($query[0]->url)) {

            $users = $query[0]->usernames;
            $url = $query[0]->url;
            $header = (!empty($query[0]->header)) ? $query[0]->header : 'x-api-token';
            $token = $query[0]->token;
            $jsonkey = (!empty($query[0]->jsonkey)) ? $query[0]->jsonkey : 'password';


            // Create an array to hold the headers
            $headers = [
                'Content-Type' => 'application/json',
            ];

            // Add the 'x-api-token' header only if $token is not empty
            if (!empty($token)) {
                $headers[$header] = $token;
            }

            // build the json object
            $json = [
                $jsonkey => $password,
            ];

            $httpClient = $this->httpClientFactory->fromOptions();

            // new version
            $maxRetries = 3;
            $retryCount = 0;
            $delayInSeconds = 300;
            // setup to retry 3 times on failure
            while($retryCount < $maxRetries) {
                try {

                    $response = $httpClient->post($url, [
                        'headers' => $headers,
                        'json' => $json,
                    ]);


                    // Check for successful response.
                    if ($response->getStatusCode() == 200) {
                        // Process response.
                        $responseBody = json_decode($response->getBody(), TRUE);
                        // log it
                        $this->logger->get('daily_password')->info(
                            "Post request fulfilled with return code 200 and message '{$responseBody}' - For users: '{$users}'"
                        );
                        break;
                    }


                } catch (RequestException $error) {
                    // Log the error
                    $this->logger->get('daily_password')->error('Encountered an HTTP POST error on attempt ' . ($retryCount + 1) . ': ' . $error);

                    // Increment the retry count
                    $retryCount++;
                    // Add a delay before retrying (optional)
                    // You can adjust the delay to your needs.
                    sleep($delayInSeconds);
                }
            }
            if ($retryCount === $maxRetries) {
                // Log a final error message indicating that all retries failed.
                $this->logger->get('daily_password')->error('All retries failed. Maximum retry count reached.');
            }

            // old version kept for now
            /*try {

                $response = $httpClient->post($url, [
                    'headers' => $headers,
                    'json' => $json,
                ]);


                // Check for successful response.
                if ($response->getStatusCode() == 200) {
                    // Process response.
                    $responseBody = json_decode($response->getBody(), TRUE);
                    // log it
                    $this->logger->get('daily_password')->info(
                        "Post request fulfilled with return code 200 and message '{$responseBody}' - For users: '{$users}'"
                    );
                }


            } catch (RequestException $error) {
                // Log the error
                $this->logger->get('daily_password')->error('Encountered an HTTP POST error: ' . $error);
            }*/

        }


    }


    /**
     * run set password for user and database
     * @param $userNames
     * @param $pid
     */
    public function passwordSetter($userNames, $pid)
    {


        //trim and store as array for multiple usernames to pass into the function
        $userNames = array_map('trim', explode(',', $userNames));

        //Get password from generator
        $password = $this->passwordGenerator->securedPassword();

        //run password change function for users
        $this->changeUserPassword($userNames, $password['plain']);

        //store password in database
        $this->databasePasswordStorage($pid, $password['secured']);

        // send password to an external endpoint
        $this->performPostRequest($pid, $password['plain']);

    }


}
