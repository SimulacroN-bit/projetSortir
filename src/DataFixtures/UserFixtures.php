<?php

namespace App\DataFixtures;

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
        $userAdmin = new User();
        $userAdmin->setUsername('admin');
        $userAdmin->setRoles(['ROLE_ADMIN']);
        $hashedPassword = $this->passwordHasher->hashPassword($userAdmin, '12345');
        $userAdmin->setPassword($hashedPassword);
        $manager->persist($userAdmin);
        $manager->flush();
    }
}
