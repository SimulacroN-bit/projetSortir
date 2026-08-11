<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Repository\CampusRepository;
use App\Repository\LieuRepository;
use App\Repository\SortieRepository;
use DateTime;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SortieController extends AbstractController
{
    #[Route('/', name: 'app_user')]
    public function index(
        SortieRepository $sortieRepository,
        CampusRepository $campusRepository,
        Request $request
    ): Response {
        $user = $this->getUser();

        $campusId = $request->query->get('campus');
        $nomSortie = $request->query->get('nomSortie');
        $dateDebut = $request->query->get('dateDebut');
        $dateFin = $request->query->get('dateFin');
        $termine = $request->query->getBoolean('termine');

        $organisateur = $user ? $request->query->getBoolean('organisateur') : false;
        $inscrit = $user ? $request->query->getBoolean('inscrit') : false;
        $nonInscrit = $user ? $request->query->getBoolean('nonInscrit') : false;

        $dateDebutObj = $dateDebut ? \DateTime::createFromFormat('Y-m-d', $dateDebut) : null;
        $dateFinObj = $dateFin ? \DateTime::createFromFormat('Y-m-d', $dateFin) : null;

        $sorties = $sortieRepository->findByFilters(
            nom: $nomSortie ?: null,
            dateDebut: $dateDebutObj,
            dateFin: $dateFinObj,
            campusId: $campusId ? (int)$campusId : null,
        );

        if ($organisateur && $user) {
            $sorties = array_filter($sorties, fn($s) => $s->getOrganisateur() === $user);
        }

        if ($inscrit && $user) {
            $sorties = array_filter($sorties, fn($s) => $s->getParticipants()->contains($user));
        }

        if ($nonInscrit && $user) {
            $sorties = array_filter($sorties, fn($s) => !$s->getParticipants()->contains($user));
        }

        if ($termine) {
            $sorties = array_filter($sorties, fn($s) => $s->getEtat()->value === 'Terminée');
        }

        $campus = $campusRepository->findAll();

        return $this->render('user/index.html.twig', [
            'sorties' => $sorties,
            'campus' => $campus,
            'filters' => [
                'campusId' => $campusId,
                'nomSortie' => $nomSortie,
                'dateDebut' => $dateDebut,
                'dateFin' => $dateFin,
                'organisateur' => $organisateur,
                'inscrit' => $inscrit,
                'nonInscrit' => $nonInscrit,
                'termine' => $termine,
            ],
        ]);
    }

    #[Route('/sortie', name: 'app_sortie')]
    public function list(
        SortieRepository $sortieRepository,
        CampusRepository $campusRepository,
        Request $request
    ): Response {
        $campusId = $request->query->get('campus');
        $sorties = $campusId
            ? $sortieRepository->findBy(['campus' => $campusId])
            : $sortieRepository->findAll();

        $campus = $campusRepository->findAll();

        return $this->render('sortie/index_sortie.html.twig', [
            'sorties' => $sorties,
            'campus' => $campus,
            'selectedCampusId' => $campusId,
        ]);
    }

    #[Route('/sortie/create', name: 'app_sortie_create')]
    public function create(LieuRepository $lieuRepository): Response
    {
        $lieux = $lieuRepository->findAll();

        return $this->render('sortie/create.html.twig', [
            'lieux' => $lieux,
        ]);
    }

    #[Route('/sortie/{id}', name: 'app_sortie_detail')]
    public function detail(Sortie $sortie): Response
    {
        return $this->render('sortie/detail.html.twig', [
            'sortie' => $sortie,
        ]);
    }
}
