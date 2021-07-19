<?php

namespace App;

class Generator
{
    public static function generate($count = 100)
    {
        $numbers = range(1, $count);
        shuffle($numbers);

        $faker = \Faker\Factory::create();
        $faker->seed(1);
        $id = [];
        for ($i = 0; $i < $count; $i++) {
            $id[] = $faker->uuid;
        }

        return $id;
    }



}

