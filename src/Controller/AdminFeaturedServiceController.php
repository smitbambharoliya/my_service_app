<?php

namespace App\Controller;

use App\Entity\FeaturedService;
use App\Entity\Service;
use App\Repository\FeaturedServiceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/featured-services')]
#[IsGranted('ROLE_ADMIN')]
class AdminFeaturedServiceController extends AbstractController
{
    #[Route('/', name: 'app_admin_featured_services', methods: ['GET'])]
    public function index(FeaturedServiceRepository $repo): Response
    {
        $features = $repo->findBy([], ['section' => 'ASC', 'displayOrder' => 'ASC']);
        return $this->render('admin/featured_services/manager.html.twig', [
            'features' => $features,
        ]);
    }

    #[Route('/add', name: 'app_admin_featured_services_add', methods: ['POST'])]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('add_featured', $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid CSRF token. Please try again.');
            return $this->redirectToRoute('app_admin_featured_services');
        }

        $serviceId = $request->request->get('service_id');
        $section = $request->request->get('section', 'trending');
        $displayOrder = (int)$request->request->get('display_order', 1);

        if (!$serviceId) {
            $this->addFlash('danger', 'Please select a service to feature.');
            return $this->redirectToRoute('app_admin_featured_services');
        }

        $service = $em->getRepository(Service::class)->find($serviceId);
        if (!$service) {
            $this->addFlash('danger', 'Invalid service selected.');
            return $this->redirectToRoute('app_admin_featured_services');
        }

        $featured = new FeaturedService();
        $featured->setService($service);
        $featured->setSection($section);
        $featured->setDisplayOrder($displayOrder);
        $featured->setIsActive(true);
        $featured->setCreatedBy($this->getUser());

        $startDateStr = $request->request->get('start_date');
        $endDateStr = $request->request->get('end_date');
        if ($startDateStr) $featured->setStartDate(new \DateTime($startDateStr));
        if ($endDateStr) $featured->setEndDate(new \DateTime($endDateStr));

        $em->persist($featured);
        $em->flush();

        $this->addFlash('success', 'Service featured successfully on homepage.');
        return $this->redirectToRoute('app_admin_featured_services');
    }

    #[Route('/{id}/toggle', name: 'app_admin_featured_services_toggle', methods: ['POST'])]
    public function toggle(FeaturedService $featured, Request $request, EntityManagerInterface $em): Response
    {
        if (!$this->isCsrfTokenValid('toggle' . $featured->getId(), $request->request->get('_token'))) {
            $this->addFlash('danger', 'Invalid CSRF token. Please try again.');
            return $this->redirectToRoute('app_admin_featured_services');
        }

        $featured->setIsActive(!$featured->isActive());
        $em->flush();
        $this->addFlash('success', 'Featured service status updated.');
        return $this->redirectToRoute('app_admin_featured_services');
    }

    #[Route('/{id}/delete', name: 'app_admin_featured_services_delete', methods: ['POST'])]
    public function delete(FeaturedService $featured, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $featured->getId(), $request->request->get('_token'))) {
            $em->remove($featured);
            $em->flush();
            $this->addFlash('success', 'Featured service removed from homepage layout.');
        }

        return $this->redirectToRoute('app_admin_featured_services');
    }

    // Ajax route to search services without loading full page
    #[Route('/api/search-services', name: 'api_admin_search_services', methods: ['GET'])]
    public function searchServices(Request $request, EntityManagerInterface $em): Response
    {
        $query = $request->query->get('q', '');
        $services = $em->getRepository(Service::class)->createQueryBuilder('s')
            ->where('s.title LIKE :query OR s.category LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        $data = [];
        foreach ($services as $s) {
            $data[] = [
                'id' => $s->getId(),
                'title' => $s->getTitle(),
                'provider' => $s->getProvider()->getEmail(),
                'price' => $s->getPrice(),
                'category' => $s->getCategory()->getName()
            ];
        }

        return $this->json($data);
    }
}
