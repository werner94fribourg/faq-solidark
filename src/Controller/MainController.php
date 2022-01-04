<?php

namespace App\Controller;

use App\Repository\FAQRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;


class MainController extends AbstractController
{
    /**
     * @Route("/{deleteAccount}", name="index", requirements={"deleteAccount"="\d+"})
     */
    public function index($deleteAccount = 0): Response
    {
        if($deleteAccount == 1)
        {
            $this->addFlash('delete_account', 'Your account has been removed');
            return $this->redirectToRoute('index');
        }
        return $this->render('base.html.twig', [
            
        ]);
    }

    public function getAllFaqs(FAQRepository $fAQRepository)
    {
        $faqs = $fAQRepository->findAll();
        return $this->render('all_faqs.html.twig', [
            'faqs' => $faqs
        ]);
    }
    
    /**
     * @Route("/access-denied", name="access_denied")
     */
    public function accessDenied()
    {
        if($this->getUser())
        {
            return $this->redirectToRoute('my_profile');
        }
        return $this->redirectToRoute('login');
    }
}
