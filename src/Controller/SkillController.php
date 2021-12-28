<?php

namespace App\Controller;

use App\Entity\Skill;
use App\Form\AddUserType;
use App\Form\SkillFormType;
use App\Repository\SkillRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
/**
* @Route("/skill")
*/
class SkillController extends AbstractController
{
    /**
     * @Route("/{id}", name="skill")
     */
    public function skill($id, Request $request, EntityManagerInterface $entityManager, SkillRepository $skillRepository): Response
    {
        $skill = $skillRepository->find($id);
        if($skill == null)
        {
            $this->addFlash('skill_not_found', 'The requested skill doesn\'t exist.');
            return $this->redirectToRoute('index');
        }
        else
        {
            //Form to modify the skill
            $skillForm = $this->createForm(SkillFormType::class, $skill);
            $skillForm->handleRequest($request);
            //Handling of submission of modifying the skill
            $this->handleModifySkillFormSubmission($skillForm, $entityManager, $skill);

            //Form to add an user to the skill
            $addUserForm = $this->createForm(AddUserType::class, null, ['skill' => $skill]);
            $addUserForm->handleRequest($request);
            //Handle submission of new user to the skill
            $this->handleAddUserSubmission($addUserForm, $entityManager, $skill);
            return $this->render('skill/skill.html.twig', [
                'skill' => $skill,
                'skillForm' => $skillForm->createView(),
                'addUserForm' => $addUserForm->createView()
            ]);
        }
    }

    /**
     * @Route("/delete-user-skill/{skill_id}/{user_id}", name="delete_user_skill", methods={"GET"})
     */
    public function deleteUserSkill($skill_id, $user_id, SkillRepository $skillRepository, UserRepository $userRepository, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('ROLE_SUPERADMIN');
        $skill = $skillRepository->find($skill_id);
        $userToRemove = $userRepository->find($user_id);
        if($skill == null)
        {
            $this->addFlash('delete_user_error', 'The requested skill doesn\'t exist');
            return $this->redirectToRoute('admin_manager');
        }
        if($userToRemove == null)
            $this->addFlash('delete_user_error', 'The requested user doesn\'t exist');
        else
        {
            if($userToRemove->getUserSkills()->contains($skill))
            {
                $userToRemove->removeUserSkill($skill);
                $entityManager->persist($userToRemove);
                $entityManager->flush();
                $this->addFlash('delete_user_success', 'The user was successfully removed from the skill.');
            }
            else
                $this->addFlash('delete_user_error', 'The skill isn\'t attributed to the user.');
        }

        return $this->redirectToRoute('skill', ['id' => $skill_id]);

    }

    private function handleModifySkillFormSubmission(Form $form, EntityManagerInterface $entityManager, Skill $skill)
    {
        if($form->isSubmitted())
        {
            if($form->isValid())
            {
                $entityManager->persist($skill);
                $entityManager->flush();
                $this->addFlash('modify_skill_success', 'The skill has successfully been modified.');
            }
            else
                $this->addFlash('modify_skill_error', 'Error while trying to modify the skill.');
        }
    }

    private function handleAddUserSubmission(Form $form, EntityManagerInterface $entityManager, Skill $skill)
    {
        if($form->isSubmitted())
        {
            if($form->isValid())
            {
                $user = $form->get('user')->getData();
                $usersThatHasSkill = $skill->getUsersThatHasSkill();
                if($usersThatHasSkill->contains($user))
                    $this->addFlash('add_user_error', 'The skill is already attributed to the user.');
                else
                {
                    $skill->addUsersThatHasSkill($user);
                    $entityManager->persist($skill);
                    $entityManager->flush();
                    $this->addFlash('add_user_success', 'The skill was successfully attributed to the user.');
                }
            }
            else
                $this->addFlash('add_user_error', 'The user submitted isn\'t valid.');
        }
    }
}
