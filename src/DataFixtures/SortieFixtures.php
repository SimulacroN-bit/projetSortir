<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Entity\Ville;
use App\Enum\Etat;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class SortieFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $faker = \Faker\Factory::create('fr_FR');

        $villeRepo = $manager->getRepository(Ville::class);
        $villes = $villeRepo->findAll();

        $campusRepo = $manager->getRepository(Campus::class);
        $campuses = $campusRepo->findAll();
        $campus = $campuses[0] ?? null;

        $lieuRepo = $manager->getRepository(Lieu::class);
        $lieux = $lieuRepo->findAll();

        $participants = [];
        for ($i = 0; $i < 10; $i++) {
            $participant = new Participant();
            $participant->setNom($faker->lastName());
            $participant->setPrenom($faker->firstName());
            $participant->setTelephone('06' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT));
            $participant->setMail($faker->email());
            $participant->setAdministrateur($i === 0);
            $participant->setActif(true);
            $participant->setCampus($campus);
            $manager->persist($participant);
            $participants[] = $participant;
        }
        $manager->flush();

        $sorties = [];
        $activites = ['Randonnée', 'Cinéma', 'Restaurant', 'Musée', 'Picnic', 'Sport', 'Bowling', 'Concert'];
        $states = [Etat::Ouverte, Etat::Cloturee, Etat::EnCours, Etat::Annulee, Etat::Terminee, Etat::EnCreation];

        for ($i = 0; $i < 15; $i++) {
            $sortie = new Sortie();
            $sortie->setNom($activites[$i % count($activites)] . ' ' . ($i + 1));
            $daysFromNow = rand(-10, 30);
            $sortie->setDateHeureDebut(new \DateTimeImmutable("+$daysFromNow days " . rand(9, 18) . ':00'));
            $sortie->setDateLimiteInscription(new \DateTimeImmutable('+' . max(1, $daysFromNow - 3) . ' days'));
            $sortie->setDuree(new \DateTime('0' . rand(1, 8) . ':' . str_pad(rand(0, 59), 2, '0', STR_PAD_LEFT) . ':00'));
            $sortie->setNbInscriptionMax(rand(5, 30));
            $sortie->setInfosSortie($faker->sentence());
            $sortie->setEtat($states[$i % count($states)]);
            $sortie->setLieu($lieux[$i % count($lieux)]);
            $sortie->setCampus($campus);
            $sortie->setOrganisateur($participants[0]);

            for ($j = 1; $j < rand(2, 6); $j++) {
                $sortie->addParticipant($participants[$j]);
            }

            $manager->persist($sortie);
            $sorties[] = $sortie;
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [VilleFixtures::class, LieuFixtures::class, CampusFixtures::class];
    }
}
