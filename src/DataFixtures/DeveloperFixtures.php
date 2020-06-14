<?php

namespace App\DataFixtures;

use App\Entity\Developer;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class DeveloperFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $developers = [
            ['name' => 'DEV 1', 'period' => 1, 'difficulty' => 1],
            ['name' => 'DEV 2', 'period' => 1, 'difficulty' => 2],
            ['name' => 'DEV 3', 'period' => 1, 'difficulty' => 3],
            ['name' => 'DEV 4', 'period' => 1, 'difficulty' => 4],
            ['name' => 'DEV 5', 'period' => 1, 'difficulty' => 5],
        ];

        foreach ($developers as $developerItem) {
            $developer = new Developer();
            $developer->setName($developerItem['name']);
            $developer->setPeriod($developerItem['period']);
            $developer->setDifficulty($developerItem['difficulty']);

            $manager->persist($developer);
        }

        $manager->flush();
    }
}
