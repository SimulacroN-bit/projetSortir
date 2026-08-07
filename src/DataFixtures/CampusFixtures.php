<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CampusFixtures extends Fixture
{
    const CAMPUS_NANTES = "Campus Nantes";
    const CAMPUS_NIORT = "Campus Niort";
    const CAMPUS_QUIMPER = "Campus Quimper";
    const CAMPUS_RENNES = "Campus Rennes";


    public function load(ObjectManager $manager): void
    {
        $noms = [
            self::CAMPUS_NANTES, self::CAMPUS_NIORT, self::CAMPUS_QUIMPER, self::CAMPUS_RENNES
        ];

        foreach ($noms as $nom) {
            $campus = new Campus();
            $campus->setNom($nom);
            $this->setReference($nom, $campus);
            $manager->persist($campus);
        }

        $manager->flush();

    }
}
