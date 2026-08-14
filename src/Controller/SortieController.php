<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\Sortie;
use App\Enum\Etat;
use App\Form\AnnulationType;
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
        Request          $request
    ): Response
    {
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
        $sorties = array_filter($sorties, fn(Sortie $s) => $s->getEtatAffichage() !== Etat::Historisee);

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
        Request          $request
    ): Response
    {
        $campusId = $request->query->get('campus');
        $sorties = $campusId
            ? $sortieRepository->findBy(['campus' => $campusId])
            : $sortieRepository->findAll();

        $campus = $campusRepository->findAll();

        return $this->render('user/index.html.twig', [
            'sorties' => $sorties,
            'campus' => $campus,
            'selectedCampusId' => $campusId,
        ]);
    }

    #[Route('/sortie/{id}', name: 'app_sortie_detail', requirements: ['id' => '\d+'])]
    public function detail(Sortie $sortie): Response
    {
        if ($sortie->getEtatAffichage() === Etat::Historisee) {
            throw  $this->createNotFoundException('Cette sortie n\'est plus consultable.');
        }
        return $this->render('sortie/detail.html.twig', [
            'sortie' => $sortie,
        ]);
    }

    #[Route('/sortie/create', name: 'app_sortie_create', methods: ['GET', 'POST'])]
    public function create(
        Request                $request,
        EntityManagerInterface $entityManager,
    ): Response
    {
        $participant = $this->getUser();

        if (!$participant instanceof Participant) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }
        $sortie = new Sortie();
        $sortie->setOrganisateur($participant);
        $sortie->setCampus($participant->getCampus());
        $sortie->setEtat(Etat::EnCreation);

        $sortieForm = $this->createForm(SortieType::class, $sortie);
        $sortieForm->handleRequest($request);

        if ($sortieForm->isSubmitted() && $sortieForm->isValid()) {
            $clickedAction = $request->request->get('action');
            $sortie->setEtat($clickedAction === 'publier' ? Etat::Ouverte : Etat::EnCreation);

            $entityManager->persist($sortie);
            $entityManager->flush();

            $this->addFlash('success', $clickedAction === 'publier'
                ? 'Sortie publiée avec succès!'
                : 'Sortie enregistrée en tant que brouillon.');

            return $this->redirectToRoute('app_sortie_detail', ['id' => $sortie->getId()]);
        }
        return $this->render('sortie/create.html.twig', [
            'sortieForm' => $sortieForm,
            'sortie' => $sortie,
        ]);
    }

    #[Route('/sortie/{id}/update', name: 'app_sortie_update', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function update(
        Sortie                 $sortie,
        Request                $request,
        EntityManagerInterface $em
    ): Response
    {
        /** @var Participant|null $participant */
        $participant = $this->getUser();

        //vérifier si l'utilisateur est bien connecté
        if (!$participant instanceof Participant) {
            throw $this->createAccessDeniedException('Vous devez être connecté');
        }

        //seul l'organisateur peut modifier la sortie et seulement tant que la sortie n'est pas publiée
        if ($sortie->getOrganisateur() !== $participant) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas l\'organisateur de cette sortie.');
        }
        if ($sortie->getEtatAffichage() !== Etat::EnCreation) {
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

    #[Route('/sortie/{id}/delete', name: 'app_sortie_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(
        Sortie                 $sortie,
        Request                $request,
        EntityManagerInterface $em,
    ): Response
    {
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
        if ($sortie->getEtatAffichage() !== Etat::EnCreation) {
            $this->addFlash('error', 'Cette sortie ne peut plus être supprimée.');
            return $this->redirectToRoute('app_sortie_detail', ['id' => $sortie->getId()]);
        }

        if ($this->isCsrfTokenValid('delete-sortie-' . $sortie->getId(), $request->request->get('_token'))) {
            $em->remove($sortie);
            $em->flush();
            $this->addFlash('success', 'Sortie supprimée.');
        }

        return $this->redirectToRoute('app_sortie');
    }

    #[Route('//sortie/{id}/cancel', name: 'app_sortie_cancel', requirements: ['id' => '\d+'], methods: ['GET',
   'POST'])]
    public function cancel(
        Sortie                 $sortie,
        Request                $request,
        EntityManagerInterface $em
    ): Response
    {
        /** @var Participant|null $participant*/
        $participant = $this->getUser();

        //vérifier si 'utilisateur est bien connecté
        if (!$participant instanceof Participant) {
            throw $this->createAccessDeniedException('Vous devez être connecté');
        }

        //seul l'organisateur peut modifier la sortie et seulement tant que la sortie n'est pas publiée
        if ($sortie->getOrganisateur() !== $participant) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas l\'organisateur de cette sortie.');
        }

        //annulation possible seulement si la sortie a déjà été publiée, mais n'a pas encore commencé
        if (!in_array($sortie->getEtatAffichage(), [Etat::Ouverte, Etat::Cloturee], true)) {
            $this->addFlash('error', 'Cette sortie ne peut pas être annulée.');
            return $this->redirectToRoute('app_user');
        }

        $annulationForm = $this->createForm(AnnulationType::class, $sortie);
        $annulationForm->handleRequest($request);

        if ($annulationForm->isSubmitted() && $annulationForm->isValid()) {
            $sortie->setEtatAffichage(Etat::Annulee);
            $em->flush();

            $this->addFlash('success', 'Sortie annulée.');
            return $this->redirectToRoute('app_user');
        }

        return $this->render('sortie/cancel.html.twig', [
            'annulationForm' => $annulationForm,
            'sortie' => $sortie,
        ]);
    }
}
