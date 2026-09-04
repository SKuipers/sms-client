<?php
declare(strict_types=1);

namespace Matthewbdaly\SMS\Drivers;

use GuzzleHttp\ClientInterface as GuzzleClient;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ServerException;
use Psr\Http\Message\ResponseInterface;
use Matthewbdaly\SMS\Contracts\Driver;
use Matthewbdaly\SMS\Exceptions\DriverNotConfiguredException;

/**
 * Driver for mNotify (Ghana)
 * API docs: https://developer.mnotify.com/
 */
class MNotify implements Driver
{
    /**
     * Guzzle client.
     *
     * @var
     */
    protected $client;

    /**
     * Guzzle response.
     *
     * @var
     */
    protected $response;

    /**
     * Send endpoint.
     *
     * @var string
     */
    private $endpoint = 'https://api.mnotify.com/api/sms/quick';

    /**
     * API Key.
     *
     * @var string
     */
    private $apiKey;

    /**
     * Constructor.
     *
     * @param GuzzleClient      $client   The Guzzle Client instance.
     * @param ResponseInterface $response The response instance.
     * @param array             $config   The configuration array.
     * @throws DriverNotConfiguredException Driver not configured correctly.
     *
     * @return void
     */
    public function __construct(GuzzleClient $client, ResponseInterface $response, array $config)
    {
        $this->client = $client;
        $this->response = $response;

        if (!array_key_exists('api_key', $config) || empty($config['api_key'])) {
            throw new DriverNotConfiguredException();
        }

        $this->apiKey = $config['api_key'];
    }

    /**
     * Get driver name.
     *
     * @return string
     */
    public function getDriver(): string
    {
        return 'MNotify';
    }

    /**
     * Get endpoint URL.
     *
     * @return string
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * Send the SMS.
     *
     * mNotify quick SMS endpoint expects a POST with JSON body:
     * {
     *   "recipient": ["233241234567"],
     *   "sender":    "MySchool",
     *   "message":   "Hello!",
     *   "is_schedule": false,
     *   "schedule_date": ""
     * }
     *
     * The API key is passed as a query parameter: ?key=YOUR_API_KEY
     *
     * @param array $message An array containing the message (keys: to, from, content).
     *
     * @throws \Matthewbdaly\SMS\Exceptions\ClientException  Client exception.
     * @throws \Matthewbdaly\SMS\Exceptions\ServerException  Server exception.
     * @throws \Matthewbdaly\SMS\Exceptions\RequestException Request exception.
     * @throws \Matthewbdaly\SMS\Exceptions\ConnectException Connect exception.
     *
     * @return boolean
     */
    public function sendRequest(array $message): bool
    {
        try {
            // mNotify accepts a comma-separated string or an array for recipient.
            // Gibbon may pass a comma-separated batch string, so we split it back to array.
            $recipients = array_map(
                fn($n) => preg_replace('/[^0-9+]/', '', trim($n)),
                explode(',', (string) $message['to'])
            );

            $payload = [
                'recipient'     => $recipients,
                'sender'        => $message['from'],
                'message'       => $message['content'],
                'is_schedule'   => false,
                'schedule_date' => '',
            ];

            $url = $this->getEndpoint() . '?key=' . urlencode($this->apiKey);

            $this->client->request('POST', $url, [
                'headers' => ['Content-Type' => 'application/json'],
                'json'    => $payload,
            ]);

        } catch (ClientException $e) {
            throw new \Matthewbdaly\SMS\Exceptions\ClientException();
        } catch (ServerException $e) {
            throw new \Matthewbdaly\SMS\Exceptions\ServerException();
        } catch (ConnectException $e) {
            throw new \Matthewbdaly\SMS\Exceptions\ConnectException();
        } catch (RequestException $e) {
            throw new \Matthewbdaly\SMS\Exceptions\RequestException();
        }

        return true;
    }
}
