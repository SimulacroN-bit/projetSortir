<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CampusFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $noms = [
            "Campus Niort", "Campus Quimper", "Campus Nantes", "Campus Rennes"
        ];

        foreach ($noms as $nom) {
            $campus = new Campus();
            $campus->setNom($nom);
            $manager->persist($campus);
        }

        $manager->flush();
    }
}
