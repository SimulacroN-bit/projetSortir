<?php

namespace App\Repository;

use App\Entity\Participant;
use App\Entity\Sortie;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class SortieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sortie::class);
    }

    public function findByFilters(
        ?string $nom = null,
        ?DateTime $dateDebut = null,
        ?DateTime $dateFin = null,
        ?int $campusId = null,
        ?Participant $user = null,
        ?bool $organisateur = false,
        ?bool $inscrit = false,
        ?bool $nonInscrit = false,
        ?bool $termine = false,
    ): array {
        $qb = $this->createQueryBuilder('s');

        if ($nom) {
            $qb->andWhere('LOWER(s.nom) LIKE LOWER(:nom)')
                ->setParameter('nom', '%' . $nom . '%');
        }

        if ($dateDebut) {
            $qb->andWhere('s.dateHeureDebut >= :dateDebut')
                ->setParameter('dateDebut', $dateDebut);
        }

        if ($dateFin) {
            $qb->andWhere('s.dateHeureDebut <= :dateFin')
                ->setParameter('dateFin', $dateFin);
        }

        if ($campusId) {
            $qb->andWhere('s.campus = :campusId')
                ->setParameter('campusId', $campusId);
        }

        if ($organisateur && $user) {
            $userRef = $this->getEntityManager()->getReference(Participant::class, $user->getId());
            $qb->andWhere('s.organisateur = :org')
                ->setParameter('org', $userRef);
        }

        if ($inscrit && $user) {
            $qb->andWhere(':user MEMBER OF s.participants')
                ->setParameter('user', $user);
        }

        if ($nonInscrit && $user) {
            $qb->andWhere(':user NOT MEMBER OF s.participants')
                ->setParameter('user', $user);
        }

        if ($termine) {
            $qb->andWhere("s.etat = 'Terminée'");
        }

        return $qb->getQuery()->getResult();
    }
}
