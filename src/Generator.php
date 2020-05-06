<?php

namespace App;

class Generator
{
    public static function generateCompanies($count)
    {
        $numbers = range(1, 100);
        shuffle($numbers);

        $faker = \Faker\Factory::create();
        $faker->seed(1);
        $companies = [];
        for ($i = 0; $i < $count; $i++) {
            $companies[] = [
                'id' => $numbers[$i],
                'name' => $faker->company,
                'phone' => $faker->phoneNumber
            ];
        }

        return $companies;
    }

    public static function generateUsers($count)
    {
        $faker = \Faker\Factory::create();
        $faker->seed(2);
        $companies = [];
        for ($i = 0; $i < $count; $i++) {
            $companies[] = [
                'id' => $i,
                'name' => $faker->name,
                'phone' => $faker->phoneNumber,
                'address' => $faker->streetAddress
            ];
        }

        return $companies;
    }
}
