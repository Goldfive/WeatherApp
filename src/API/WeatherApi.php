<?php


namespace App\API;

use App\Entity\WeatherData;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * Class WeatherAPi
 * Gets the weather data from the external API openweathermap.org and returns the information
 * @package App\API
 */
class WeatherApi
{

    const API_KEY = "a545dbb59b644ff1877112112190909";
    /**
     * @var string $API_URL
     */
    const API_URL = "http://api.worldweatheronline.com/premium/v1/<MODULE>.ashx";

    const MODULES = [
        'weather',
        'past-weather'
    ];

    /** @var HttpClient $client */
    private $client;

    /** @var WeatherData $weatherData */
    private $weatherData;

    /**
     * @param $args
     * @return WeatherData
     */
    public function getWeatherData($args): WeatherData {
        $this->client = HttpClient::create();
        $args['key'] = self::API_KEY;
        try {
            $response = $this->client->request('GET', $this->getApiUrlWithModule($args), ['query' => $args]);
        } catch (TransportExceptionInterface $e) {
            echo 'Something went wrong with transporting the data. Whoops!';

            return null;
        }

        $weatherData = new WeatherData($args, $response);

        return $weatherData;
    }

    /**
     * @param $args
     * @return mixed
     */
    private function getApiUrlWithModule($args) {
        if (isset($args)) {
            if (isset($args['date'])) {
                return str_replace('<MODULE>', self::MODULES[1], self::API_URL);
            }
        }
        return str_replace('<MODULE>', self::MODULES[0], self::API_URL);
    }
}
