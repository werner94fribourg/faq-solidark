<?php

namespace App\Controller;

use App\Entity\FAQ;
use App\Entity\Skill;
use App\Form\FaqFormType;
use App\Form\FaqModeratorFormType;
use App\Form\SetUserRightsType;
use App\Form\SkillFormType;
use App\Repository\FAQRepository;
use App\Repository\SkillRepository;
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
    public function adminManager(Request $request, UserRepository $userRepository, FAQRepository $fAQRepository, SkillRepository $skillRepository, EntityManagerInterface $entityManager): Response
    {

        //Admin rights form
        $adminRightsForm = $this->createForm(SetUserRightsType::class);
        $adminRightsForm->handleRequest($request);
        //Handle submission of Admin rights form
        $this->handleAdminRightsSubmission($adminRightsForm, $entityManager, $userRepository);

        //FAQ form
        $newFaq = new FAQ();
        $faqForm = $this->createForm(FaqFormType::class, $newFaq);
        $faqForm->handleRequest($request);
        //Handle submission of new faq form
        $this->handleNewFaqSubmission($faqForm, $entityManager, $newFaq);

        //Set Moderator form
        $faqModeratorForm = $this->createForm(FaqModeratorFormType::class);
        $faqModeratorForm->handleRequest($request);
        $adminUsers = $userRepository->findAllAdminUsers();
        //Handle submission of moderator form
        $this->handleFaqModeratorSubmission($faqModeratorForm, $entityManager, $fAQRepository, $userRepository);

        //Skill form
        $newSkill = new Skill();
        $skillForm = $this->createForm(SkillFormType::class, $newSkill);
        $skillForm->handleRequest($request);

        //Handle submission of skill form
        $this->handleNewSkillFormSubmission($skillForm, $entityManager, $newSkill);
        
        //List of entities to show on the dashboard
        $users = $userRepository->findAll();
        $faqs = $fAQRepository->findAll();
        $skills = $skillRepository->findAll();
        
        return $this->render('admin/admin_manager.html.twig', [
            'users' => $users,
            'faqs' => $faqs,
            'skills' => $skills,
            'adminRightsForm' => $adminRightsForm->createView(),
            'faqForm' => $faqForm->createView(),
            'faqModeratorForm' => $faqModeratorForm->createView(),
            'adminUsers' => $adminUsers,
            'skillForm' => $skillForm->createView()
        ]);
    }
    
    /**
     * @Route("/delete-faq/{id}", name="delete_faq", methods={"GET"})
     */
    public function deleteFaq($id, FAQRepository $fAQRepository, EntityManagerInterface $entityManager): Response
    {
        if(!$this->isGranted('ROLE_SUPERADMIN'))
            $this->addFlash('delete_faq_access_error', 'Error while trying to delete the faq : Access denied.');
        else
        {
            $faq = $fAQRepository->find($id);
            if($faq == null)
                $this->addFlash('delete_faq_error', 'The requested faq doesn\'t exist.');
            else
            {
                $entityManager->remove($faq);
                $entityManager->flush();
                $this->addFlash('delete_faq_success', 'The requested faq has successfully been removed.');
            }  
        }
        return $this->redirectToRoute('admin_manager');
    }
    /**
     * @Route("/delete-skill/{id}", name="delete_skill", methods={"GET"})
     */
    public function deleteSkill($id, SkillRepository $skillRepository, EntityManagerInterface $entityManager): Response
    {
        $skill = $skillRepository->find($id);
        if($skill == null)
            $this->addFlash('delete_skill_error', 'The requested skill doesn\'t exist.');
        else
        {
            $entityManager->remove($skill);
            $entityManager->flush();
            $this->addFlash('delete_skill_success', 'The requested skill has successfully been removed.');
        }
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
                $this->addFlash('change_role_error', 'Error while trying to change the role of the user.');
        }
    }

    private function handleNewFaqSubmission(Form $form, EntityManagerInterface $entityManager, $faq)
    {
        if($form->isSubmitted())
        {
            if(!$this->isGranted('ROLE_SUPERADMIN'))
                $this->addFlash('new_faq_access_error', 'Error while trying to create a new faq : Access denied.');
            if($form->isValid())
            {
                $entityManager->persist($faq);
                $entityManager->flush();
                $this->addFlash('new_faq_success', 'The new faq has succesfully been registered.');
                $faq = new FAQ();
            }
            else
                $this->addFlash('new_faq_error', 'Error while trying to create the new faq');
        }
    }

    private function handleFaqModeratorSubmission(Form $form, EntityManagerInterface $entityManager, FAQRepository $fAQRepository, UserRepository $userRepository)
    {
        if($form->isSubmitted())
        {
            if(!$this->isGranted('ROLE_SUPERADMIN'))
                $this->addFlash('moderator_access_error', 'Error while trying to change the moderator of a faq : Access denied.');
            else
            {
                if($form->isValid())
                {
                    $faq = $fAQRepository->find($form->get('id')->getData());
                    $moderator = $userRepository->find($form->get('moderator')->getData());
                    if($faq == null)
                        $this->addFlash('moderator_error', 'The requested faq doesn\'t exist.');
                    elseif($moderator == null)
                        $this->addFlash('moderator_error', 'The requested user doesn\'t exist.');
                    else
                    {
                        $moderatorRole = $moderator->getRoles()[0];
                        if($moderatorRole != 'ROLE_ADMIN')
                            $this->addFlash('moderator_error', 'The moderator must be an admin user.');
                        else
                        {
                            $faq->setModerator($moderator);
                            $entityManager->persist($faq);
                            $entityManager->flush();
                            $this->addFlash('moderator_success', 'The moderator has successfully been updated.');
                        }
                    }
                }
            }
        }
    }

    private function handleNewSkillFormSubmission(Form $form, EntityManagerInterface $entityManager, $skill)
    {
        if($form->isSubmitted())
        {
            if($form->isValid())
            {
                $entityManager->persist($skill);
                $entityManager->flush();
                $this->addFlash('new_skill_success', 'The new skill has successfully been registered.');
                $skill = new Skill();
            }
            else
                $this->addFlash('new_skill_error', 'Error while trying to create the new skill.');
        }
    }
}

