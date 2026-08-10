<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Participant;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class ParticipantFixtures extends Fixture implements DependentFixtureInterface
{
    use CampusReferenceTrait;

    public const PARTICIPANT_ADMIN_REFERENCE = 'participantAdmin_ref';
    public const PARTICIPANT_REFERENCE = 'participant-ref';

    public function load(ObjectManager $manager): void
    {
        $faker = \Faker\Factory::create('fr_FR');

        //Récupère tous les campus créés dans CampusFixture
        $campus = $this->getCampusReferences();

        //Participant Admin
        $userAdmin = $this->getReference(UserFixtures::USER_ADMIN_REFERENCE, User::class);
        $participantAdmin = $this->createParticipant(
            nom: 'Admin',
            prenom: 'Super',
            telephone: '0600000000',
            mail: 'admin@sortir.com',
            administrateur: true,
            campus:$campus[0],
            user: $userAdmin,
        );
        $manager->persist($participantAdmin);
        $this->addReference(self::PARTICIPANT_ADMIN_REFERENCE, $participantAdmin);

        //Participant des utilisateurs lambda
        for ($i = 1; $i < UserFixtures::NB_USERS; $i++) {
            //Récupère l'utilisateur dans UserFixture
            $user = $this->getReference(UserFixtures::USER_REFERENCE . $i, User::class);

            $participant = $this->createParticipant(
                nom: $faker->lastName(),
                prenom: $faker->firstName(),
                telephone: '06' . str_pad(rand(10000000, 99999999), 8, '0', STR_PAD_LEFT),
                mail: $faker->email(),
                administrateur: false,
                campus: $campus[$i % count($campus)],
                user: $user,
            );
            $manager->persist($participant);
            $this->addReference(self::PARTICIPANT_REFERENCE . $i, $participant);
        }
        //flush() commum pour la création des utilisateurs et des participants comme ils sont liés
        $manager->flush();
    }

    //permet de créer un participant en indiquant toutes ces données et éviter de dupliquer du code pour les deux
    // instanciations de UserAdmin et User
    public function createParticipant(
        string $nom,
        string $prenom,
        string $telephone,
        string $mail,
        bool $administrateur,
        Campus $campus,
        User $user
    ): Participant
    {
        $participant = new Participant();
        $participant->setNom($nom);
        $participant->setPrenom($prenom);
        $participant->setTelephone($telephone);
        $participant->setMail($mail);
        $participant->setAdministrateur($administrateur);
        $participant->setActif(true);
        $participant->setCampus($campus);
        $participant->setUser($user);

        return $participant;
    }

    public function getDependencies(): array
    {
        return [
            UserFixtures::class,
            CampusFixtures::class
        ];
    }
}
