<?php

namespace App\Controller;

use App\Entity\FAQ;
use App\Form\FaqFormType;
use App\Form\FaqModeratorFormType;
use App\Form\SetUserRightsType;
use App\Repository\FAQRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;

/**
 * @Route("/admin")
 */
class AdminController extends AbstractController
{
    /**
     * @Route("", name="admin_manager")
     */
    public function adminManager(Request $request, UserRepository $userRepository, FAQRepository $fAQRepository, EntityManagerInterface $entityManager): Response
    {
        //List of entities to show on the dashboard
        $users = $userRepository->findAll();
        $faqs = $fAQRepository->findAll();

        //Admin rights form
        $adminRightsForm = $this->createForm(SetUserRightsType::class);
        $adminRightsForm->handleRequest($request);
        
        //Handle submission of Admin rights form
        $this->handleAdminRightsSubmission($adminRightsForm, $entityManager, $userRepository);

        //FAQ form
        $newFaq = new FAQ();
        $faqForm = $this->createForm(FaqFormType::class, $newFaq);
        $faqForm->handleRequest($request);
        $newFaqSubmitted = false;

        //Handle submission of new faq form
        $newFaqSubmitted = $this->handleNewFaqSubmission($faqForm, $entityManager, $newFaq);

        //Set Moderator form
        $faqModeratorForm = $this->createForm(FaqModeratorFormType::class);
        $faqModeratorForm->handleRequest($request);
        $adminUsers = $userRepository->findAllAdminUsers();
        $moderatorFormSubmitted = false;

        //Handle submission of moderator form
        $moderatorFormSubmitted = $this->handleFaqModeratorSubmission($faqModeratorForm, $entityManager, $fAQRepository, $userRepository);

        return $this->render('admin/admin_manager.html.twig', [
            'users' => $users,
            'faqs' => $faqs,
            'adminRightsForm' => $adminRightsForm->createView(),
            'faqForm' => $faqForm->createView(),
            'newFaqSubmitted' => $newFaqSubmitted,
            'faqModeratorForm' => $faqModeratorForm->createView(),
            'adminUsers' => $adminUsers,
            'moderatorFormSubmitted' => $moderatorFormSubmitted
        ]);
    }
    
    /**
     * @Route("/delete-faq/{id}", name="delete_faq", methods={"GET"})
     */
    public function deleteFaq($id, FAQRepository $fAQRepository, EntityManagerInterface $entityManager): Response
    {
        if(!$this->isGranted('ROLE_SUPERADMIN'))
        {
            $this->addFlash('delete_faq_error', 'Error while trying to delete the faq : Access denied.');
            return $this->redirectToRoute('admin_manager');
        }
        $faq = $fAQRepository->find($id);
        if($faq == null)
        {
            $this->addFlash('delete_faq_error', 'The requested faq doesn\'t exist.');
            return $this->redirectToRoute('admin_manager');
        }

        $entityManager->remove($faq);
        $entityManager->flush($faq);
        $this->addFlash('delete_faq_success', 'The requested faq has successfully been removed.');
        return $this->redirectToRoute('admin_manager');
    }

    private function handleAdminRightsSubmission(Form $form, EntityManagerInterface $entityManager, UserRepository $userRepository)
    {
        if($form->isSubmitted())
        {
            if($form->isValid())
            {
                $user_id = $form->get('user_id')->getData();
                $userToChange = $userRepository->find($user_id);
                $userRole = $userToChange->getRoles()[0];
                if($userRole == 'ROLE_ADMIN' && !$this->isGranted('ROLE_SUPERADMIN'))
                {
                    $this->addFlash('change_role_error', 'Error while trying to change the role of the user : Access denied.');
                    return;
                }
                $user_role = intval($form->get('user_role')->getData());
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
    }

    private function handleNewFaqSubmission(Form $form, EntityManagerInterface $entityManager, $faq): bool
    {
        if($form->isSubmitted())
        {
            if(!$this->isGranted('ROLE_SUPERADMIN'))
            {
                $this->addFlash('new_faq_error', 'Error while trying to create a new faq : Access denied.');
                return false;
            }
            if($form->isValid())
            {
                $entityManager->persist($faq);
                $entityManager->flush();
                $this->addFlash('new_faq_created', 'The new faq has succesfully been registered.');
                $faq = new FAQ();
            }
            else
            {
                return true;
            }
        }
        return false;
    }

    private function handleFaqModeratorSubmission(Form $form, EntityManagerInterface $entityManager, FAQRepository $fAQRepository, UserRepository $userRepository): bool
    {
        if($form->isSubmitted())
        {
            if(!$this->isGranted('ROLE_SUPERADMIN'))
            {
                $this->addFlash('moderator_error', 'Error while trying to change the moderator of a faq : Access denied.');
                return false;
            }
            if($form->isValid())
            {
                $faq = $fAQRepository->find($form->get('id')->getData());
                $moderator = $userRepository->find($form->get('moderator')->getData());
                if($faq == null)
                {
                    $this->addFlash('moderator_error', 'The requested faq doesn\'t exist.');
                    return true;
                }
                elseif($moderator == null)
                {
                    $this->addFlash('moderator_error', 'The requested user doesn\'t exist.');
                    return true;
                }
                $moderatorRole = $moderator->getRoles()[0];
                if($moderatorRole != 'ROLE_ADMIN')
                {
                    $this->addFlash('moderator_error', 'The moderator must be an admin user.');
                    return true;
                }
                $faq->setModerator($moderator);
                $entityManager->persist($faq);
                $entityManager->flush();
                $this->addFlash('moderator_success', 'The moderator has successfully been updated.');
                return true;
            }
        }
        return false;
    }
}

