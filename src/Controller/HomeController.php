<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class HomeController extends AbstractController
{
    #[Route('/', name: 'app_root')]
    #[Route('/home', name: 'app_home')]
    public function index(
        \App\Repository\FeaturedServiceRepository $fRepo,
        \App\Repository\ServiceRepository $sRepo
    ): Response
    {
        // Allow all users, including Admin and Provider, to view the Home Page

        $activeFeatures = $fRepo->findActiveFeaturedServices();
        $sections = ['hero' => [], 'trending' => [], 'premium' => [], 'seasonal' => []];
        
        foreach ($activeFeatures as $f) {
            $sections[$f->getSection()][] = $f->getService();
        }

        if (empty($sections['trending'])) {
            $sections['trending'] = $sRepo->findBy([], ['id' => 'DESC'], 6);
        }

        // Guests and Normal Users see the public marketing page
        return $this->render('home/index.html.twig', [
            'sections' => $sections,
        ]);
    }
}
