<?php

namespace App\Command;

use App\API\WeatherApi;
use App\Entity\WeatherData;
use App\Repository\WeatherDataRepository;
use App\Utils\DataTemplate;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class WeatherCommand extends Command
{
    protected static $defaultName = 'weather';

    /**
     * @var SymfonyStyle $io
     */
    private $io;

    /**
     * @var ContainerInterface $container
     */
    private $container;

    /** @var EntityManagerInterface $em */
    private $em;

    /** @var WeatherDataRepository $weatherDataRepository */
    private $weatherDataRepository;

    /** @var ProgressBar $progressBar */
    public static $progressBar;

    public $advanceProgressBar;

    /**
     * WeatherCommand constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct();
        $this->container = $container;

        $this->em = $this->container->get('doctrine')->getManager();
        $this->weatherDataRepository = $this->em->getRepository(WeatherData::class);
    }

    protected function configure()
    {
        $this
            ->setDescription('Get information about the weather. You need only ask.')
            ->addArgument('location', InputArgument::OPTIONAL, 'Location to see the weather from')
            ->addOption('date', 'd', InputOption::VALUE_REQUIRED,
                'Get weather information about a certain date. 
                If there\'s only 4 digits found (e.g. 2010) it is considered as a year')
        ;
    }

    /**
     * @param string $text
     * @param int $i
     * @param bool $isFinished
     */
    public function advanceProgressBar(string $text, int $i, bool $isFinished = false) {
        $this->io->text($text);
            WeatherCommand::$progressBar->advance($i);

            if ($isFinished) {
                WeatherCommand::$progressBar->finish();
            }
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void|null
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->io = new SymfonyStyle($input, $output);
        $location = $input->getArgument('location');
        $date = $input->getOption('date');

        if (empty($location)) {
            $this->io->text(
                "How to use this tool: \n");
            $this->io->section('Arguments');
            $this->io->note(": 
            - location - required. Example: Leiden
            - If city contains a space, quotes must be used: \"Leiden, the Netherlands\"
            - date - optional. Example: --date=1-1-2019
            
            Full command: php weather.php \"Prague, Czech Republic\" --date=1-5-1999");
            return;
        }

        $this->io->text("Looking up weather data...");

        WeatherCommand::$progressBar = $this->io->createProgressBar(100);

        $args = $this->handleArguments($location, $date);

        /**
         * @var WeatherData[] $existingRecords
         */
        $existingRecords = $this->getDataFromDatabase($args);
        $this->advanceProgressBar("Looking for weather data in local database...", 10);
        if (sizeof($existingRecords) == 0) {

            $this->advanceProgressBar("Weather data can't be found in local database. Fetching data from API...", 5);

            $api = new WeatherApi();
            $weatherData = $api->getWeatherData($args);
            if (array_key_exists('error', $weatherData->getData())) {
                $this->io->warning($weatherData->getData()['error'][0]['msg']);

                return;
            }
            $this->advanceProgressBar("Weather data retrieved! Saving to local database...", 50);

            $this->saveToDatabase($weatherData);

            $this->advanceProgressBar("Saved to local database!",10, true);


        } else {
            $this->advanceProgressBar("Weather data found in local database :)", 70, true);
            $weatherData = $existingRecords[0];
        }
        DataTemplate::showDataToUser($weatherData, $this->io);
    }

    /**
     * @param $args
     * @return array|mixed
     */
    private function getDataFromDatabase($args) {
        if (isset($args['date']) && $args['date'] !== null) {
            $searchArray = ['date' => $args['date']];
        }
        $searchArray['location'] = str_replace('+', '', $args['q']);

        return $this->weatherDataRepository->findByDateAndLocation($searchArray);
    }

    /**
     * @param WeatherData $weatherData
     */
    private function saveToDatabase(WeatherData $weatherData) {
        //First check if this record already exists in the database. Who wants duplicate data?
        /** @var EntityManagerInterface $em */
        $em = $this->container->get('doctrine')->getManager();
        $em->persist($weatherData);
        $em->flush();
    }

    /**
     * @param $location
     * @param $date
     * @return array
     */
    private function handleArguments($location, $date) {
        $apiArgs = ['format' => 'json'];

        if (strlen($date) == 4 && is_numeric($date)) { //Date is only a year
            $apiArgs['date'] = $date;
        } else if (strpos($date, '-') !== false) {
            //We expect the date to be in Dutch format. Because why not?
            preg_match_all('(\d+)', $date, $match);
            $match = $match[0];

            try {
                if (strlen($match[0]) == 4) {
                    throw new \Exception("WrongDateFormatException");
                }
                $apiArgs['date'] = (new \DateTime())->setDate($match[2], $match[1], $match[0])->format('Y-m-d');
            } catch (\Exception $e) {
                $this->io->error("Whoops! Something is apparently not quite right with your date. Try the format: d-m-Y (1-12-2010)");

                return [];
            }
        }

        if ($location) {
            $apiArgs['q'] = $location;
        }

        $this->advanceProgressBar("Handled arguments...", 20);

        return $apiArgs;
    }
}
