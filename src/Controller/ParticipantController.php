<?php

namespace App\Controller;

use App\Entity\Participant;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ParticipantController extends AbstractController
{
    #[Route('/participant/{id}', name: 'participant_detail', methods: ['GET'])]
    public function detail(Participant $participant): Response
    {
        return $this->render('participant/detail.html.twig', [
            'participant' => $participant,
        ]);
    }
}
