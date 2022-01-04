<?php

namespace App\Controller;

use App\Entity\FAQ;
use App\Entity\Question;
use App\Form\FaqFormType;
use App\Form\QuestionFormType;
use App\Repository\FAQRepository;
use App\Repository\QuestionRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
* @Route("/faqs")
*/
class FAQController extends AbstractController
{
    /**
     * @Route("", name="faq_main")
     */
    public function faqMain(Request $request, EntityManagerInterface $entityManager, QuestionRepository $questionRepository, FAQRepository $fAQRepository, UserRepository $userRepository): Response
    {
        $question = new Question();
        //New question form
        $questionForm = $this->createForm(QuestionFormType::class, $question);
        $questionForm->handleRequest($request);
        //Handling of submission of new question
        $this->handleQuestionFormSubmission($questionForm, $entityManager, $question);

        //List of entities to show on the dashboard
        $questions = $questionRepository->findBy([], ['creationDate' => 'DESC']);
        $faqs = $fAQRepository->findBy([], ['name' => 'ASC']);
        $users = $userRepository->findBy([], ['username' => 'ASC']);
        
        return $this->render('faq/faq_main.html.twig', [
            'questionForm' => $questionForm->createView(),
            'questions' => $questions,
            'faqs' => $faqs,
            'users' => $users
        ]);
    }

    /**
     * @Route("/question/{id}", name="question")
     */
    public function question($id, Request $request, UserRepository $userRepository, QuestionRepository $questionRepository, EntityManagerInterface $entityManager): Response
    {
        $question = $questionRepository->find($id);

        //Modify question form
        $questionForm = $this->createForm(QuestionFormType::class, $question);
        $questionForm->handleRequest($request);
        //Handling of submission of modifying the question
        $this->handleQuestionFormSubmission($questionForm, $entityManager, $question, false);
        $hasAdminRightToDeleteQuestion = false;
        if($this->isGranted('IS_AUTHENTICATED_REMEMBERED'))
        {
            $loggedUser = $userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
            $hasAdminRightToDeleteQuestion = $this->hasRightToDeleteQuestion($loggedUser->getModeratedFAQs(), $question->getBelongingFAQs());
        }
        if($question == null)
        {
            $this->addFlash('question_error', 'The requested question doesn\'t exist.');
            return $this->redirectToRoute('faq_main');
        }
        return $this->render('faq/question.html.twig', [
            'question' => $question,
            'questionForm' => $questionForm->createView(),
            'hasAdminRightToDeleteQuestion' => $hasAdminRightToDeleteQuestion
        ]);
    }
    /**
     * @Route("/delete-question/{id}", name="delete_question")
     */
    public function deleteQuestion($id, QuestionRepository $questionRepository, UserRepository $userRepository, EntityManagerInterface $entityManager)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $question = $questionRepository->find($id);
        $currentUser = $userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        if($question == null)
            $this->addFlash('delete_question_error', 'The requested question doesn\'t exist.');
        else
        {
            if($question->getCreator() != $this->getUser())
                $this->denyAccessUnlessGranted('ROLE_ADMIN');
            if($this->isGranted('ROLE_ADMIN'))
            {
                $moderatedFAQs = $currentUser->getModeratedFAQs();
                $belongingsFAQs = $question->getBelongingFAQs();
                if($this->isGranted('ROLE_ADMIN') && !$this->hasRightToDeleteQuestion($moderatedFAQs, $belongingsFAQs))
                {
                    $this->addFlash('delete_question_error', 'You don\'t have the right to delete the question.');
                    $this->denyAccessUnlessGranted('ROLE_SUPERADMIN');
                }
            }
            $entityManager->remove($question);
            $entityManager->flush();
            $this->addFlash('delete_question_success', 'The requested question has successfully been removed.');
        }
        return $this->redirectToRoute('faq_main');
    }

    private function hasRightToDeleteQuestion($moderatedFAQs, $belongingsFAQs)
    {
        foreach($moderatedFAQs as $moderatedFAQ)
        {
            if($belongingsFAQs->contains($moderatedFAQ))
                return true;
        }
        return false;
    }
    /**
     * @Route("/faq/{id}", name="faq", requirements={"id"="\d+"})
     */
    public function faq($id, Request $request, FAQRepository $fAQRepository, EntityManagerInterface $entityManager): Response
    {
        $faq = $fAQRepository->find($id);
        if($faq == null)
        {
            $this->addFlash('faq_error', 'The requested faq doesn\'t exist.');
            return $this->redirectToRoute('faq_main');
        }

        //Form to modify the faq
        $faqForm = $this->createForm(FaqFormType::class, $faq);
        $faqForm->handleRequest($request);
        //Handling of submission of modifying the faq
        $this->handleModifyFaqFormSubmission($faqForm, $entityManager, $faq);
        return $this->render('faq/faq.html.twig', [
            'faq' => $faq,
            'faqForm' => $faqForm->createView()
        ]);
    }

    /**
     * @Route("/unassigned-questions", name="unassigned_questions")
     */
    public function unassignedQuestions(Request $request ,QuestionRepository $questionRepository, EntityManagerInterface $entityManager)
    {
        $unassignedQuestions = $this->getUnassignedQuestions($questionRepository);
        
        return $this->render('faq/unassigned_questions.html.twig', [
            'unassignedQuestions' => $unassignedQuestions
        ]);
    }

    private function handleModifyFaqFormSubmission(Form $form, EntityManagerInterface $entityManager, FAQ $faq)
    {
        if($form->isSubmitted())
        {
            if($form->isValid())
            {
                $entityManager->persist($faq);
                $entityManager->flush();
                $this->addFlash('modify_faq_success', 'The FAQ has successfully been modified.');
            }
            else
                $this->addFlash('modify_faq_error', 'Error while trying to modify the faq.');
        }
    }

    private function handleQuestionFormSubmission(Form $form, EntityManagerInterface $entityManager, Question $question, $newQuestion = true)
    {
        if($form->isSubmitted())
        {
            if($form->isValid())
            {
                $question->setCreator($this->getUser());
                $currentDate = new \DateTime('now');
                $question->setCreationDate($currentDate);
                $entityManager->persist($question);
                $entityManager->flush();
                if($newQuestion)
                    $this->addFlash('question_success', 'The question has successfully been added.');
                else
                    $this->addFlash('question_success', 'The question has successfully been updated.');
            }
            else
            {
                if($newQuestion)
                    $this->addFlash('question_error', 'Error while trying to add the question.');
                else
                    $this->addFlash('question_error', 'Error while trying to update the question.');
            }
        }
    }
        
    private function getUnassignedQuestions(QuestionRepository $questionRepository)
    {
        $questions = $questionRepository->findAll();
        $unassignedQuestions = [];
        foreach($questions as $question)
        {
            if($question->getBelongingFAQs()->isEmpty())
            {
                $unassignedQuestions[] = $question;
            }
        }
        return $unassignedQuestions;
    }
}
