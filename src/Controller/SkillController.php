<?php

namespace App\Controller;

use App\Entity\Skill;
use App\Form\SkillFormType;
use App\Repository\SkillRepository;
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
            $skillForm = $this->createForm(SkillFormType::class, $skill);
            $skillForm->handleRequest($request);
            $this->handleModifySkillFormSubmission($skillForm, $entityManager, $skill);
            return $this->render('skill/skill.html.twig', [
                'skill' => $skill,
                'skillForm' => $skillForm->createView()
            ]);
        }
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
}
