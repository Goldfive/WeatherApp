<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpClient\Response\CurlResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * @ORM\Entity(repositoryClass="App\Repository\WeatherDataRepository")
 */
class WeatherData
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @var array $data
     * @ORM\Column(type="json", nullable=true)
     */
    private $data;

    /**
     * @var \DateTime $date
     * @ORM\Column(type="date", nullable=true)
     */
    private $date;

    /**
     * @var
     * @ORM\Column(type="string")
     */
    private $location;

    /**
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * WeatherData constructor.
     * @param array $args
     * @param ResponseInterface $responseData
     */
    public function __construct(array $args, ResponseInterface $responseData)
    {
        try {
            $this->data = json_decode($responseData->getContent(), true)['data'];
        } catch (\Throwable $e) {
            echo 'There was an error with converting the response data to JSON. Try... again? Or blame the programmer?';
        }

        if (isset($args['date'])) {
            $this->date = \DateTime::createFromFormat("Y-m-d", $args['date']);
        }

        $this->location = $args['q'];
    }

    /**
     * @return mixed
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param mixed $data
     * @return WeatherData
     */
    public function setData($data)
    {
        $this->data = $data;
        return $this;
    }

    /**
     * @return \DateTime
     */
    public function getDate(): \DateTime
    {
        return $this->date;
    }

    /**
     * @param \DateTime $date
     * @return WeatherData
     */
    public function setDate(\DateTime $date): WeatherData
    {
        $this->date = $date;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @param mixed $location
     * @return WeatherData
     */
    public function setLocation($location)
    {
        $this->location = $location;
        return $this;
    }


}
