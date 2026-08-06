<?php

namespace App\DataFixtures;

use App\Entity\Ville;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class VilleFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $villeData = [
            ['codePostal' => '44800', 'nom' => 'Saint-Herblain'],
            ['codePostal' => '35131', 'nom' => 'Chartres-de-Bretagne'],
            ['codePostal' => '29000', 'nom' => 'Quimper'],
            ['codePostal' => '79000', 'nom' => 'Niort'],
        ];

        foreach ($villeData as $data) {
            $ville = new Ville();
            $ville->setNom($data['nom']);
            $ville->setCodePostal($data['codePostal']);
            $manager->persist($ville);
        }

        $manager->flush();
    }
}
