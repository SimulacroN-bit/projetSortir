<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Participant;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    public const USER_ADMIN_REFERENCE = 'userAdmin-ref';
    public const USER_REFERENCE = 'user-ref';
    public const NB_USERS = 10;
    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $faker = \Faker\Factory::create('fr_FR');

        //Admin
        $userAdmin = new User();
        $userAdmin->setUsername('admin');
        $userAdmin->setRoles(['ROLE_ADMIN']);
        $userAdmin->setPassword($this->passwordHasher->hashPassword($userAdmin, '12345'));
        $manager->persist($userAdmin);

        $this->addReference(self::USER_ADMIN_REFERENCE, $userAdmin);

        //Utilisateurs lambda
        for ($i = 1; $i <= self::NB_USERS; $i++) {
            $user = new User();
            $user->setUsername("user$i");
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($this->passwordHasher->hashPassword($user, 'abc123'));

            $manager->persist($user);
            $this->addReference(self::USER_REFERENCE . $i, $user);
        }
        /**aucun flush() ici comme les utilisateurs sont liés aux participants par une relation OneToOne
         * le flush sera donc sur ParticipantFixtures juste après l'instanciation et la liaison des participants tout
         * juste créés*/
    }

    public function getDependencies(): array
    {
        return [
            CampusFixtures::class,
        ];
    }
}
