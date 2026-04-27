<?php

namespace App\Controller;

use App\Constants\AppConstants;
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
            $sections['trending'] = $sRepo->findActiveWithProvider(AppConstants::FEATURED_SERVICES_LIMIT);
        }

        // REDIRECT LOGIC FOR PROFESSIONAL SEPARATION
        if ($this->isGranted('ROLE_PROVIDER')) {
            return $this->redirectToRoute('app_provider_dashboard');
        }

        // Guests and Normal Users see the public marketing page
        return $this->render('home/index.html.twig', [
            'sections' => $sections,
        ]);
    }

    #[Route('/provider/home', name: 'app_provider_home')]
    public function providerHome(): Response
    {
        return $this->render('home/provider_home.html.twig');
    }
}
