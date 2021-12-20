<?php

namespace App\Controller;

use App\Form\SetUserRightsType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

class AdminController extends AbstractController
{
    /**
     * @Route("/admin", name="admin_manager")
     */
    public function adminManager(Request $request, UserRepository $userRepository,EntityManagerInterface $entityManager): Response
    {
        $adminRightsForm = $this->createForm(SetUserRightsType::class);
        $users = $userRepository->findAll();
        $adminRightsForm->handleRequest($request);
        if($adminRightsForm->isSubmitted())
        {
            if($adminRightsForm->isValid())
            {
                $user_id = $adminRightsForm->get('user_id')->getData();
                $userToChange = $userRepository->find($user_id);
                $userRole = $userToChange->getRoles()[0];
                if($userRole == 'ROLE_ADMIN' && !$this->isGranted('ROLE_SUPERADMIN'))
                {
                    $this->addFlash('change_role_error', 'Error while trying to change the role of the user : Access denied.');
                    $this->denyAccessUnlessGranted('ROLE_SUPERADMIN');
                }
                $user_role = intval($adminRightsForm->get('user_role')->getData());
                switch($user_role)
                {
                    case 0:
                        $userToChange->setRoles(['ROLE_ADMIN']);
                        break;
                    case 1:
                        $userToChange->setRoles(['ROLE_USER']);
                        break;
                }
                $entityManager->persist($userToChange);
                $entityManager->flush();
                $this->addFlash('change_role_success', 'The role of the user has successfully been changed.');
            }
            else
            {
                $this->addFlash('change_role_error', 'Error while trying to change the role of the user');
            }
        }
        return $this->render('admin/admin_manager.html.twig', [
            'users' => $users,
            'adminRightsForm' => $adminRightsForm->createView()
        ]);
    }
}
