<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Sortie;
use App\Enum\Etat;
use App\Form\SortieType;
use App\Repository\CampusRepository;
use App\Repository\SortieRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
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

    #[Route('/sortie/{id}', name: 'app_sortie_detail')]
    public function detail(Sortie $sortie): Response
    {
        return $this->render('sortie/detail.html.twig', [
            'sortie' => $sortie,
        ]);
    }

    #[Route('/sortie/create', name: 'app_sortie_create', methods: ['GET', 'POST'])]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof Participant) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }
        $sortie = new Sortie();
        $sortie->setOrganisateur($user);
        $sortie->setCampus($user->getCampus());
        $sortie->setEtat(Etat::EnCreation);

        $sortieForm = $this->createForm(SortieType::class, $sortie);
        $sortieForm->handleRequest($request);

        if ($sortieForm->isSubmitted() && $sortieForm->isValid()) {
            $clickedAction = $request->request->get('action');
            $sortie->setEtat($clickedAction === 'publier' ? Etat::Ouverte : Etat::EnCreation);

            $entityManager->persist($sortie);
            $entityManager->flush();

            $this->addFlash('sucess', $clickedAction === 'publier'
                ? 'Sortie publiée avec succès!'
                : 'Sortie enregistrée en tant que brouillon.');

            return $this->redirectToRoute('app_sortie_detail', ['id' => $sortie->getId()]);
        }
        return $this->render('sortie/create.html.twig', [
            'sortieForm' => $sortieForm,
            'sortie' => $sortie,
        ]);
    }

    #[Route('/sortie/{id}/update', name: 'app_sortie_update', requirements: ['id' => '\d+'], methods: ['GET','POST'])]
    public function update(
        Sortie $sortie,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        /** @var Participant|null $participant */
        $participant = $this->getUser();

        //vérifier si 'utilisateur est bien connecté
        if (!$participant instanceof Participant) {
            throw $this->createAccessDeniedException('Vous devez être connecté');
        }

        //seul l'organisateur peut modifier la sortie et seulement tant que la sortie n'est pas publiée
        if ($sortie->getOrganisateur() !== $participant) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas l\'organisateur de cette sortie.');
        }
        if ($sortie->getEtat() !== Etat::EnCreation) {
            $this->addFlash('error', 'Cette sortie n\'est plus modifiable.');
            return $this->redirectToRoute('app_sortie_detail', ['id' => $sortie->getId()]);
        }

        //le formulaire associé à l'entité vide
        $sortieForm = $this->createForm(SortieType::class, $sortie);

        //récupération des données du form et les injecte dans la $sortie
        $sortieForm->handleRequest($request);

        //si le formulaire est soumis et valide
        if ($sortieForm->isSubmitted() && $sortieForm->isValid()) {
            $clickedAction = $request->request->get('action');
            $sortie->setEtat($clickedAction === 'publier' ? Etat::Ouverte : Etat::EnCreation);

            //sauvegarde en bdd
            $em->flush();
            //affichage d'un message sur la prochaine page pour confirmation la modification
            $this->addFlash('success', $clickedAction === 'publier'
                ? 'Sortie publiée avec succès!'
                : 'Sortie enregistrée en tant que brouillon.');
            //redirection vers la page de détail de la sortie tout juste modifiée
            return $this->redirectToRoute('app_sortie_detail', ['id' => $sortie->getId()]);
        }
        return $this->render('sortie/create.html.twig', [
            'sortieForm' => $sortieForm,
            'sortie' => $sortie,
        ]);
    }
}
