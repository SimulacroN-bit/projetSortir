<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfilType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route("/mon-profil")]
final class ProfilController extends AbstractController
{
    #[Route('/', name: 'profil_update', methods: ['GET', 'POST'])]
    public function updateProfil(
        Request $request,
        EntityManagerInterface $em,
        UserPasswordHasherInterface $passwordHasher,
        UserRepository $userRepository,
    ): Response {
        /** @var User|null $user */
        $user = $this->getUser();

        //vérifie que l'instance utilisateur ne fait pas partie de l'entité User
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        $participant = $user->getParticipant();

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
            //Mise à jour du pseudo sur l'entité User
            $newUsername = $profilForm->get('username')->getData();

            //vérification de l'unicité du pseudo si modifié
            if ($newUsername !== $user->getUsername()) {
                $existingUser = $userRepository->findOneBy(['username' => $newUsername]);
                if ($existingUser instanceof User) {
                    $profilForm->get('username')->addError(new FormError('Ce pseudo est déjà utilisé.'));

                    return $this->render('profil/profil.html.twig', [
                        'profilForm' => $profilForm,
                        'participant' => $participant,
                    ]);
                }
                $user->setUsername($newUsername);
            }

            //Mise à jour du mot de passe uniquement s'il a été rempli
            $plainPassword = $profilForm->get('plainPassword')->getData();
            if ($plainPassword){
                $hashedPassword = $passwordHasher->hashPassword($user, $plainPassword);
                $user->setPassword($hashedPassword);
            }

            //sauvegarde en bdd
            $em->flush();

            //affiche un message sur la prochaine page
            $this->addFlash('success', 'Profil modifié avec succès!');

            //redirige vers la page de détail du participant fraîchement modifiée
            return $this->redirectToRoute('detail_profil');
        }
        // affiche le formulaire
        return $this->render('profil/profil.html.twig', [
            'profilForm' => $profilForm,
            'participant' => $participant,
        ]);
    }

}