<?php

namespace App\Controller;

use App\Repository\CampusRepository;
use App\Repository\SortieRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SortieController extends AbstractController
{
    #[Route('/sortie', name: 'app_sortie')]
    public function index(
        SortieRepository $sortieRepository,
        CampusRepository $campusRepository,
        Request $request
    ): Response {
        $campusId = $request->query->get('campus');
        $sorties = $campusId
            ? $sortieRepository->findBy(['campus' => $campusId])
            : $sortieRepository->findAll();

        $campus = $campusRepository->findAll();

        return $this->render('sortie/index.html.twig', [
            'sorties' => $sorties,
            'campus' => $campus,
            'selectedCampusId' => $campusId,
        ]);
    }
}
