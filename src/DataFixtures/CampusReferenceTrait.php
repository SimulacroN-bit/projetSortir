<?php

namespace App\DataFixtures;

use App\Entity\Campus;

//création d'un trait pour permettre le partage les références des différents campus dans différentes classes
trait CampusReferenceTrait
{
    /**
     * Fournie par Doctrine\Bundle\FixturesBundle\Fixture (via AbstractFixture)
     * dans les classes qui utilisent ce trait.
     */
    abstract protected function getReference(string $name, string $class): object;

    /**
     * @return Campus[]
     */
    private function getCampusReferences(): array
    {
        return [
            $this->getReference(CampusFixtures::CAMPUS_NANTES, Campus::class),
            $this->getReference(CampusFixtures::CAMPUS_NIORT, Campus::class),
            $this->getReference(CampusFixtures::CAMPUS_QUIMPER, Campus::class),
            $this->getReference(CampusFixtures::CAMPUS_RENNES, Campus::class),
        ];
    }
}