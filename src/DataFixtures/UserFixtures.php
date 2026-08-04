<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class UserFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $faker = \Faker\Factory::create('fr_FR');
        // création d'un administrateur
        $userAdmin = new User();
        $userAdmin->setUsername('admin');
        $userAdmin->setRoles(['ROLE_ADMIN']);
        $userAdmin->setPassword('123456');
        $manager->persist($userAdmin);
        $manager->flush();
    }
}
