<?php

namespace App\DataFixtures;

use App\Entity\Ville;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class VilleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = \Faker\Factory::create('fr_FR');

        $villes = [];
        for ($i = 0; $i < 5; $i++) {
            $ville = new Ville();
            $ville->setNom($faker->city());
            $ville->setCodePostal(str_pad(rand(1000, 95999), 5, '0', STR_PAD_LEFT));
            $manager->persist($ville);
            $villes[] = $ville;
        }
        $manager->flush();
    }
}
