<?php

namespace App\Controller;

use App\Entity\FAQ;
use App\Entity\Question;
use App\Form\FaqFormType;
use App\Form\QuestionFormType;
use App\Repository\FAQRepository;
use App\Repository\QuestionRepository;
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
    public function faqMain(Request $request, EntityManagerInterface $entityManager, QuestionRepository $questionRepository): Response
    {
        $question = new Question();
        //New question form
        $questionForm = $this->createForm(QuestionFormType::class, $question);
        $questionForm->handleRequest($request);
        //Handling of submission of new question
        $this->handleQuestionFormSubmission($questionForm, $entityManager, $question);

        //List of entities to show on the dashboard
        $questions = $questionRepository->findAll();
        
        return $this->render('faq/faq_main.html.twig', [
            'questionForm' => $questionForm->createView(),
            'questions' => $questions
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
    
    /**
     * @Route("/faq/{id}", name="faq")
     */
    public function faq($id, Request $request, FAQRepository $fAQRepository, EntityManagerInterface $entityManager, QuestionRepository $questionRepository): Response
    {
        $faq = $fAQRepository->find($id);
        if($faq == null)
        {
            $this->addFlash('faq_error', 'The requested faq doesn\'t exist.');
            return $this->redirectToRoute('faq_main');
        }
        $todayQuestions = $this->getTodayQuestions($faq, $questionRepository);
        $weeklyQuestions = $this->getWeeklyQuestions($faq, $questionRepository);

        //Form to modify the faq
        $faqForm = $this->createForm(FaqFormType::class, $faq);
        $faqForm->handleRequest($request);
        //Handling of submission of modifying the faq
        $this->handleModifyFaqFormSubmission($faqForm, $entityManager, $faq);
        return $this->render('faq/faq.html.twig', [
            'faq' => $faq,
            'todayQuestions' => $todayQuestions,
            'weeklyQuestions' => $weeklyQuestions,
            'faqForm' => $faqForm->createView()
        ]);
    }

    private function getTodayQuestions(FAQ $faq, QuestionRepository $questionRepository)
    {
        $questions = $questionRepository->findTodayQuestions();
        $todayQuestions = [];
        foreach($questions as $question)
        {
            if($question->getBelongingFAQs()->contains($faq))
            {
                $todayQuestions[] = $question;
            }
        }
        return $todayQuestions;
    }

    private function getWeeklyQuestions(FAQ $faq, QuestionRepository $questionRepository)
    {
        $questions = $questionRepository->findWeeklyQuestions();
        $weeklyQuestions = [];
        foreach($questions as $question)
        {
            if($question->getBelongingFAQs()->contains($faq))
            {
                $weeklyQuestions[] = $question;
            }
        }
        return $weeklyQuestions;
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

    private function handleQuestionFormSubmission(Form $form, EntityManagerInterface $entityManager, Question $question)
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
                $this->addFlash('new_question_success', 'The question has successfully been added.');
            }
            else
                $this->addFlash('new_question_error', 'Error while trying to add the question.');
        }
    }
}
