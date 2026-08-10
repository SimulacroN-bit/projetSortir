<?php

namespace App\DataFixtures;

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
    use CampusReferenceTrait;

    /**
     * @throws \Exception
     */
    public function load(ObjectManager $manager): void
    {
        $faker = \Faker\Factory::create('fr_FR');

        //récupération des villes
        $villeRepo = $manager->getRepository(Ville::class);
        $villes = $villeRepo->findAll();

        //récupération des lieux
        $lieuRepo = $manager->getRepository(Lieu::class);
        $lieux = $lieuRepo->findAll();

        //récupération des campus
        $campus = $this->getCampusReferences();

        //Récupère les participants déjà créées et liés à un vrai  dans ParticipantFixtures
        $participants = [];
        for ($i = 1; $i <ParticipantFixtures::NB_PARTICIPANTS; $i++) {
            $participants[] = $this->getReference(ParticipantFixtures::PARTICIPANT_REFERENCE . $i,
                Participant::class
            );
        }
        $participantAdmin = $this->getReference(ParticipantFixtures::PARTICIPANT_ADMIN_REFERENCE,
            Participant::class
        );

        $activites = ['Randonnée', 'Cinéma', 'Restaurant', 'Musée', 'Picnic', 'Sport', 'Bowling', 'Concert'];
        $states = [Etat::Ouverte, Etat::Cloturee, Etat::EnCours, Etat::Annulee, Etat::Terminee, Etat::EnCreation];


        //création des fixtures de sortie
        for ($i = 0; $i < 15; $i++) {
            $sortie = new Sortie();
            $sortie->setNom($activites[$i % count($activites)] . ' ' . ($i + 1));
            $daysFromNow = rand(-10, 30);
            $sortie->setDateHeureDebut(new \DateTimeImmutable("+$daysFromNow days " . rand(9, 18) . ':00'));
            $sortie->setDateLimiteInscription(new \DateTimeImmutable('+' . max(1, $daysFromNow - 3) . ' days'));
            $sortie->setDuree(rand(30, 480)); // durée en minutes (30 min à 8h)
            $sortie->setNbInscriptionMax(rand(5, 30));
            $sortie->setInfosSortie($faker->sentence());
            $sortie->setEtat($states[$i % count($states)]);
            $sortie->setLieu($lieux[$i % count($lieux)]);
            $sortie->setCampus($campus[$i % count($campus)]);
            $sortie->setOrganisateur($participants[0]);

            for ($j = 1; $j < rand(2, 6); $j++) {
                $sortie->addParticipant($participants[$j]);
            }

            $manager->persist($sortie);
        }
        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            VilleFixtures::class,
            LieuFixtures::class,
            CampusFixtures::class,
            ParticipantFixtures::class,
        ];
    }
}
