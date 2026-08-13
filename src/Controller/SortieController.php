<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Enum\Etat;
use App\Form\SortieType;
use App\Repository\CampusRepository;
use App\Repository\LieuRepository;
use App\Repository\SortieRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

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

        $organisateur = $user && $request->query->has('organisateur');
        $inscrit = $user && $request->query->has('inscrit');
        $nonInscrit = $user && $request->query->has('nonInscrit');

        $dateDebutObj = $dateDebut ? DateTime::createFromFormat('Y-m-d', $dateDebut) : null;
        $dateFinObj = $dateFin ? DateTime::createFromFormat('Y-m-d', $dateFin) : null;

        $sorties = $sortieRepository->findByFilters(
            nom: $nomSortie ?: null,
            dateDebut: $dateDebutObj,
            dateFin: $dateFinObj,
            campusId: $campusId ? (int)$campusId : null,
            user: $user,
            organisateur: $organisateur,
            inscrit: $inscrit,
            nonInscrit: $nonInscrit,
            termine: $termine,
        );

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

    #[Route('/sortie/create', name: 'app_sortie_create', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_USER')]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        LieuRepository $lieuRepository
    ): Response {
        $user = $this->getUser();
        $sortie = new Sortie();
        $sortie->setOrganisateur($user);
        $sortie->setCampus($user->getCampus());
        $sortie->setEtat(Etat::EnCreation);

        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $sortie->setOrganisateur($user);
            $sortie->setCampus($user->getCampus());
            $sortie->setEtat(Etat::EnCreation);
            $entityManager->persist($sortie);
            $entityManager->flush();

            return $this->redirectToRoute('app_user');
        }

        $lieux = $lieuRepository->findAll();

        return $this->render('sortie/create.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/sortie/{id}/publish', name: 'app_sortie_publish', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function publish(
        Sortie $sortie,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if ($sortie->getOrganisateur() !== $user) {
            throw $this->createAccessDeniedException('Seul l\'organisateur peut publier cette sortie.');
        }

        if ($sortie->getEtat() !== Etat::EnCreation) {
            throw $this->createAccessDeniedException('Seules les sorties en création peuvent être publiées.');
        }

        $sortie->setEtat(Etat::Ouverte);
        $entityManager->flush();

        return $this->redirectToRoute('app_user');
    }

    #[Route('/sortie/{id}', name: 'app_sortie_detail')]
    public function detail(Sortie $sortie): Response
    {
        return $this->render('sortie/detail.html.twig', [
            'sortie' => $sortie,
        ]);
    }
}
