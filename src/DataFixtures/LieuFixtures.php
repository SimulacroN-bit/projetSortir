<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Lieu;
use App\Entity\Ville;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LieuFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $villeRepo = $manager->getRepository(Ville::class);
        $villes = $villeRepo->findAll();

        $lieux = [
            ['nom' => 'Parc Merlin', 'rue' => '3 rue Michael Faraday'],
            ['nom' => 'Salle Léo', 'rue' => '8 rue Léo Lagrange'],
            ['nom' => 'Beach Quimper', 'rue' => '2 rue Georges Perros'],
            ['nom' => 'Cité Niort', 'rue' => '19 avenue Léo Lagrange'],
        ];

        foreach ($lieux as $index => $data) {
            $lieu = new Lieu();
            $lieu->setNom($data['nom']);
            $lieu->setRue($data['rue']);
            $lieu->setVille($villes[$index % count($villes)]);
            $lieu->setLatitude(46.0 + ($index * 0.5));
            $lieu->setLongitude(-1.0 + ($index * 0.3));
            $manager->persist($lieu);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [VilleFixtures::class];
    }
}
