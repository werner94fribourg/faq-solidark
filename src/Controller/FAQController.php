<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
* @Route("/faq")
*/
class FAQController extends AbstractController
{
    /**
     * @Route("/", name="faq_main")
     */
    public function faqMain(): Response
    {
        return $this->render('faq/faq_main.html.twig', [
            
        ]);
    }

    /**
     * @Route("/question", name="question")
     */
    public function question(): Response
    {
        return $this->render('faq/question.html.twig', [
            
        ]);
    }
}
