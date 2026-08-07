<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Participant;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(private UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        //Récupère tous les campus créés dans CampusFixture
        $campus = [
            $this->getReference(CampusFixtures::CAMPUS_NANTES, Campus::class),
            $this->getReference(CampusFixtures::CAMPUS_NIORT, Campus::class),
            $this->getReference(CampusFixtures::CAMPUS_QUIMPER, Campus::class),
            $this->getReference(CampusFixtures::CAMPUS_RENNES, Campus::class),
        ];

        //Admin
        $userAdmin = new User();
        $userAdmin->setUsername('admin');
        $userAdmin->setRoles(['ROLE_ADMIN']);
        $userAdmin->setPassword($this->passwordHasher->hashPassword($userAdmin, '12345'));

        $participantAdmin = new Participant();
        $participantAdmin->setNom('Admin');
        $participantAdmin->setPrenom('Super');
        $participantAdmin->setTelephone('0600000000');
        $participantAdmin->setMail('admin@sortir.com');
        $participantAdmin->setAdministrateur(true);
        $participantAdmin->setActif(true);
        $participantAdmin->setCampus($campus[0]);
        $participantAdmin->setUser($userAdmin); //synchronise les deux côtés

        $manager->persist($userAdmin);
        $manager->persist($participantAdmin);


        //Utilisateurs lambda
        for ($i = 1; $i <= 10; $i++) {
            $user = new User();
            $user->setUsername("user$i");
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'abc123'));

            $participant = new Participant();
            $participant->setNom("Nom$i");
            $participant->setPrenom("Prenom$i");
            $participant->setTelephone("06" . str_pad($i, 8, "0", STR_PAD_LEFT));
            $participant->setMail("user$i@sortir.com");
            $participant->setAdministrateur(false);
            $participant->setActif(true);
            $participant->setCampus($campus[$i % count($campus)]);
            $participant->setUser($user);

            $manager->persist($user);
            $manager->persist($participant);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            CampusFixtures::class,
        ];
    }
}
