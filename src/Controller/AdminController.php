<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    /**
     * @Route("/admin", name="admin_manager")
     */
    public function adminManager(): Response
    {
        return $this->render('admin/admin_manager.html.twig', [
            
        ]);
    }
}
