<?php

namespace App\Utils;


use App\Entity\WeatherData;
use Symfony\Component\Console\Style\SymfonyStyle;

class DataTemplate
{
    /** @var SymfonyStyle $io */
    public static $io;

    /**
     * @param WeatherData $weatherData
     * @param SymfonyStyle $io
     */
    public static function showDataToUser(WeatherData $weatherData, SymfonyStyle $io)
    {
        DataTemplate::$io = $io;

        $io->comment("You've successfully requested weather data! Here it comes...");
        $data = $weatherData->getData();


        foreach ($data as $key => $value) {
            if ($key == "weather") {

                do {
                    $choices = [];
                    foreach ($value as $key2 => $choice) {
                        $choices[] = $choice['date'];
                    }

                    $choice = $io->choice("There are " . (sizeof($value)) . " forecasts available. Of what day do you want to see the weather?", $choices);

                    self::showWeather($value[array_search($choice, $choices)]);

                    $choice = self::$io->choice("Do you want to quit or continue?", ['quit', 'continue']);
                } while ($choice != 'quit');
                break;
            }
            if ($key == "current_condition") {
                $choice = $io->choice(
                    "Do you want to see information about the current weather condition?",
                    ['yes', 'no']
                );
                if ($choice == 'yes') {
                    self::listOutput($value[0], true, 0, 8);
                }
            }
        }
    }

    /**
     * @param $weather
     */
    public static function showWeather($weather)
    {
        self::$io->section("The weather of " . $weather['date'] . ':');

        self::listOutput($weather);

        $choice = self::$io->choice("Do you want to see the astronomy data?", ['yes', 'no']);
        if ($choice == "yes") {
            self::listOutput($weather['astronomy'], true);
        }

        $choices = ['cancel'];
        $choices = array_merge($choices, range(1, 7));
        $choice = self::$io->choice(
            "There are 7 hourly slots, with 3 hours in between. Which one do you want to see?",
            $choices
        );

        if ($choice !== "cancel") {
            self::listOutput($weather['hourly'][$choice], true);
        }
    }

    /**
     * @param array $list
     * @param bool $deeperOnArray
     * @param int $depth
     * @param int $maxDepth
     */
    public static function listOutput(array $list, bool $deeperOnArray = false, int $depth = 0, int $maxDepth = 1)
    {
        $listing = [];
        foreach ($list as $key => $value) {
            if (is_array($value)) {
                if ($deeperOnArray && $depth <= $maxDepth) {
                    self::$io->section($key);
                    self::listOutput($value, $deeperOnArray, $depth++);
                }
                continue;
            }
            $listing[] = $key . ": " . $value;
        }
        self::$io->listing($listing);
    }
}
