<?php

namespace App\Controller;

use App\Form\ProfilType;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

#[Route("/profil")]
final class ProfilController extends AbstractController
{
    #[Route('/{id}/update', name: 'profil_update', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function updateProfil(int $id, ParticipantRepository $participantRepository, Request $request,
                                 EntityManagerInterface $em): Response
    {
        //récupère ce participant en fonction de l'id présent dans l'URL
        $participant = $participantRepository->find($id);

        //s'il n'existe pas en bdd, on déclenche une erreur 404
        if (!$participant) {
            throw $this->createNotFoundException('Ce participant n\'existe pas! Désolé!');
        }

        //le formulaire associé à l'entité vide
        $profilForm = $this->createForm(ProfilType::class, $participant);

        //récupère les données du form et les injecte dans le $participant
        $profilForm->handleRequest($request);

        //si le formulaire est soumis et valide...
        if ($profilForm->isSubmitted() && $profilForm->isValid()) {
            //sauvegarde en bdd
            $em->flush();
            //affiche un message sur la prochaine page
            $this->addFlash('succes', 'Le participant a été modifié avec succès!');
            //redirige vers la page de détail du participant fraîchement modifiée
            return $this->redirectToRoute('detail_profil', ['id' => $participant->getId()]);
        }
        // affiche le formulaire
        return $this->render('profil/update.html.twig', [
            'profilForm' => $profilForm
        ]);
    }

}