<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Entity\User;
use App\Form\ProfilType;
use App\Repository\ParticipantRepository;
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
        ParticipantRepository $participantRepository,
    ): Response {
        /** @var Participant|null $participant */
        $participant = $this->getUser();

        //vérifie que l'instance participant ne fait pas partie de l'entité Participant
        if (!$participant instanceof Participant) {
            throw $this->createAccessDeniedException('Vous devez être connecté.');
        }

        $oldPseudo = $participant->getPseudo();

        //le formulaire associé à l'entité vide
        $profilForm = $this->createForm(ProfilType::class, $participant);

        //récupère les données du form et les injecte dans le $participant
        $profilForm->handleRequest($request);

        //si le formulaire est soumis et valide...
        if ($profilForm->isSubmitted() && $profilForm->isValid()) {

            //vérification de l'unicité du pseudo si modifié
            $newPseudo = $participant->getPseudo();
            if ($newPseudo !== $oldPseudo) {
                $existingParticipant = $participantRepository->findOneBy(['pseudo' => $newPseudo]);
                if ($existingParticipant instanceof Participant && $existingParticipant->getId() !== $participant->getId()) {
                    $profilForm->get('pseudo')->addError(new FormError('Ce pseudo est déjà utilisé.'));

                    return $this->render('profil/profil.html.twig', [
                        'profilForm' => $profilForm,
                        'participant' => $participant,
                    ]);
                }
            }

            //Mise à jour du mot de passe uniquement s'il a été rempli
            $plainPassword = $profilForm->get('plainPassword')->getData();
            if ($plainPassword){
                $hashedPassword = $passwordHasher->hashPassword($participant, $plainPassword);
                $participant->setPassword($hashedPassword);
            }

            //sauvegarde en bdd
            $em->flush();

            //affiche un message sur la prochaine page
            $this->addFlash('success', 'Profil modifié avec succès!');

            //redirige vers la page de détail du participant fraîchement modifiée
            return $this->redirectToRoute('profil_update');
        }
        // affiche le formulaire
        return $this->render('profil/profil.html.twig', [
            'profilForm' => $profilForm,
            'participant' => $participant,
        ]);
    }
}