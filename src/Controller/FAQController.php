<?php

namespace App\Controller;

use App\Entity\Answer;
use App\Entity\FAQ;
use App\Entity\Question;
use App\Form\AnswerFormType;
use App\Form\AssignQuestionFAQFormType;
use App\Form\AssignQuestionFormType;
use App\Form\FaqFormType;
use App\Form\ModifyAnswerFormType;
use App\Form\QuestionFormType;
use App\Repository\AnswerRepository;
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
    public function __construct()
    {
        $this->modifyAnswerForm = null;
    }

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
        
        //Form to modify the faq affectation of a question
        $assignQuestionFAQForm = $this->createForm(AssignQuestionFAQFormType::class);
        $assignQuestionFAQForm->handleRequest($request);
        //Handling of submission of modification of faq affectation
        $this->handleAssignQuestionFAQFormSubmission($assignQuestionFAQForm, $questionRepository, $entityManager);

        //List of entities to show on the dashboard
        $questions = $questionRepository->findBy([], ['creationDate' => 'DESC']);
        $faqs = $fAQRepository->findBy([], ['name' => 'ASC']);
        $users = $userRepository->findBy([], ['username' => 'ASC']);
        
        return $this->render('faq/faq_main.html.twig', [
            'questionForm' => $questionForm->createView(),
            'assignQuestionFAQForm' => $assignQuestionFAQForm->createView(),
            'questions' => $questions,
            'faqs' => $faqs,
            'users' => $users
        ]);
    }

    /**
     * @Route("/question/{id}", name="question")
     */
    public function question($id, Request $request, UserRepository $userRepository, QuestionRepository $questionRepository, AnswerRepository $answerRepository, EntityManagerInterface $entityManager): Response
    {
        $question = $questionRepository->find($id);

        //Modify question form
        $questionForm = $this->createForm(QuestionFormType::class, $question);
        $questionForm->handleRequest($request);
        //Handling of submission of modifying the question
        $this->handleQuestionFormSubmission($questionForm, $entityManager, $question, false);

        //Answer form
        $answer = new Answer();
        $answerForm = $this->createForm(AnswerFormType::class, $answer);
        $answerForm->handleRequest($request);
        //Handling of submission of new answer
        $this->handleAnswerFormSubmission($answerForm, $question, $entityManager, $answer);

        //Modify answer form
        $modifyAnswerForm = $this->createForm(ModifyAnswerFormType::class);
        $modifyAnswerForm->handleRequest($request);
        //Handling of submission of modifying an answer
        $this->handleModifyAnswerFormSubmission($modifyAnswerForm, $answerRepository, $entityManager);

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
            'hasAdminRightToDeleteQuestion' => $hasAdminRightToDeleteQuestion,
            'answerForm' => $answerForm->createView(),
            'modifyAnswerForm' => $modifyAnswerForm->createView()
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
                if(!$this->hasRightToDeleteQuestion($moderatedFAQs, $belongingsFAQs))
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

    /**
     * @Route("/delete-question-faq/{faq_id}/{question_id}", name="delete_question_faq")
     */
    public function deleteQuestionFAQ($faq_id, $question_id, FAQRepository $fAQRepository, QuestionRepository $questionRepository, EntityManagerInterface $entityManager)
    {
        $this->denyAccessUnlessGranted('ROLE_SUPERADMIN');
        $faq = $fAQRepository->find($faq_id);
        $question = $questionRepository->find($question_id);
        if($question == null)
        {
            $this->addFlash('delete_question_faq_error', 'The requested question doesn\'t exist.');
        }
        else if($faq == null )
        {
            $this->addFlash('delete_question_faq_error', 'The requested faq doesn\'t exist.');
        }
        else
        {
            $faq->removeRelatedQuestion($question);
            $entityManager->persist($faq);
            $entityManager->flush();
            $this->addFlash('delete_question_faq_success', 'The requested question was removed from the faq.');
        }
        return $this->redirectToRoute('faq', ['id' => $faq_id]);
    }

    /**
     * @Route("/delete-answer/{question_id}/{answer_id}", name="delete_answer")
     */
    public function deleteAnswer($question_id, $answer_id, AnswerRepository $answerRepository, UserRepository $userRepository, EntityManagerInterface $entityManager)
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $answer = $answerRepository->find($answer_id);
        $currentUser = $userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        if($answer == null)
        {
            $this->addFlash('delete_answer_error', 'The requested answer doesn\'t exist.');
            return $this->redirectToRoute('question', ['id' => $question_id]);
        }
        $question = $answer->getRelatedQuestion();
        if($answer->getEditor() != $currentUser)
        {
            if(!$this->isGranted('ROLE_SUPERADMIN') && !$this->isGranted('ROLE_ADMIN'))
            {
                $this->addFlash('delete_answer_error', 'You don\'t have the right to delete the question.');
                return $this->redirectToRoute('question', ['id' => $question_id]);
            } 
            if($this->isGranted('ROLE_ADMIN'))
            {
                $moderatedFAQs = $currentUser->getModeratedFAQs();
                $belongingsFAQs = $question->getBelongingFAQs();
                if(!$this->hasRightToDeleteQuestion($moderatedFAQs, $belongingsFAQs))
                {
                    $this->addFlash('delete_answer_error', 'You don\'t have the right to delete the question.');
                    return $this->redirectToRoute('question', ['id' => $question_id]);
                }
            }
        }
        $entityManager->remove($answer);
        $entityManager->flush();
        $this->addFlash('delete_answer_success', 'The requested question has successfully been removed.');
        return $this->redirectToRoute('question', ['id' => $question_id]);
    }

    /**
     * @Route("/like-question-{id}", name="like_question", methods={"POST"})
     * @Route("/dislike-question-{id}", name="dislike_question", methods={"POST"})
     */
    public function toggleLikesQuestionAjax($id, Request $request, UserRepository $userRepository, QuestionRepository $questionRepository, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $question = $questionRepository->find($id);
        if($question == null)
            return $this->json(['action' => 'error', 'id' => $id]);
        $user = $userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $result = '';
        switch($request->get('_route'))
        {
            case 'like_question':
                if($question->getLikingUsers()->contains($user))
                {
                    $question->removeLikingUser($user);
                    $result = 'unliked';
                }
                else
                {
                    $question->addLikingUser($user);
                    $result = 'liked';
                }
                break;
            case 'dislike_question':
                if($question->getDislikingUsers()->contains($user))
                {
                    $question->removeDislikingUser($user);
                    $result = 'undodisliked';
                }
                else
                {
                    $question->addDislikingUser($user);
                    $result = 'disliked';
                }
                break;
        }
        $entityManager->persist($question);
        $entityManager->flush();
        return $this->json(['action' => $result, 'id' => $id]);
    }

    /**
     * @Route("/like-answer-{id}", name="like_answer", methods={"POST"})
     * @Route("/dislike-answer-{id}", name="dislike_answer", methods={"POST"})
     */
    public function toggleLikesAnswerAjax($id, Request $request, UserRepository $userRepository, AnswerRepository $answerRepository, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $answer = $answerRepository->find($id);
        if($answer == null)
            return $this->json(['action' => 'error', 'id' => $id]);
        $user = $userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        $result = '';
        switch($request->get('_route'))
        {
            case 'like_answer':
                if($answer->getLikingUsers()->contains($user))
                {
                    $answer->removeLikingUser($user);
                    $result = 'unliked';
                }
                else
                {
                    $answer->addLikingUser($user);
                    $result = 'liked';
                }
                break;
            case 'dislike_answer':
                if($answer->getDislikingUsers()->contains($user))
                {
                    $answer->removeDislikingUser($user);
                    $result = 'undodisliked';
                }
                else
                {
                    $answer->addDislikingUser($user);
                    $result = 'disliked';
                }
                break;
        }
        $entityManager->persist($answer);
        $entityManager->flush();
        return $this->json(['action' => $result, 'id' => $id]);
    }

    /**
     * @Route("/check-liking-question-{id}", name="check_liking_question", methods={"POST"})
     * @Route("/check-disliking-question-{id}", name="check_disliking_question", methods={"POST"})
     */
    public function checkQuestionLikes($id, Request $request, QuestionRepository $questionRepository, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $question = $questionRepository->find($id);
        if($question == null)
            return $this->json(['like' => 'false']);
        $user = $userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        switch($request->get('_route'))
        {
            case 'check_liking_question':
                if($user->getLikedQuestions()->contains($question))
                    return $this->json(['like' => 'true']);
                break;
            case 'check_disliking_question':
                if($user->getDislikedQuestions()->contains($question))
                    return $this->json(['like' => 'true']);
                break;
        }
        return $this->json(['like' => 'false']);
    }

    /**
     * @Route("/check-liking-answer-{id}", name="check_liking_answer", methods={"POST"})
     * @Route("/check-disliking-answer-{id}", name="check_disliking_answer", methods={"POST"})
     */
    public function checkAnswerLikes($id, Request $request, AnswerRepository $answerRepository, UserRepository $userRepository): Response
    {
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_REMEMBERED');
        $answer = $answerRepository->find($id);
        if($answer == null)
            return $this->json(['like' => 'false']);
        $user = $userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
        switch($request->get('_route'))
        {
            case 'check_liking_answer':
                if($user->getLikedAnswers()->contains($answer))
                    return $this->json(['like' => 'true']);
                break;
            case 'check_disliking_answer':
                if($user->getDislikedAnswers()->contains($answer))
                    return $this->json(['like' => 'true']);
                break;
        }
        return $this->json(['like' => 'false']);
    }

    /**
     * @Route("/check-faq-belonging/{faq_id}/{question_id}", name="check_faq_belonging", methods={"POST"})
     */
    public function checkFAQBelonging($faq_id, $question_id, FAQRepository $fAQRepository, QuestionRepository $questionRepository):Response
    {
        $faq = $fAQRepository->find($faq_id);
        $question = $questionRepository->find($question_id);
        if($faq != null && $question != null)
        {
            if($question->getBelongingFAQs()->contains($faq))
                return $this->json(['belonging' => true]);
        }
        return $this->json(['belonging' => false]);
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
        //Form to assign questions to the faq
        $assignQuestionForm = $this->createForm(AssignQuestionFormType::class, null, ['faq' => $faq]);
        $assignQuestionForm->handleRequest($request);
        //Handling of submission of assigning a question to the faq
        $this->handleAssignQuestionFormSubmission($assignQuestionForm, $entityManager, $faq);
        return $this->render('faq/faq.html.twig', [
            'faq' => $faq,
            'faqForm' => $faqForm->createView(),
            'assignQuestionFAQForm' => $assignQuestionForm->createView()
        ]);
    }

    /**
     * @Route("/unassigned-questions", name="unassigned_questions")
     */
    public function unassignedQuestions(Request $request ,QuestionRepository $questionRepository, EntityManagerInterface $entityManager)
    {
        //Form to modify the faq affectation of a question
        $assignQuestionFAQForm = $this->createForm(AssignQuestionFAQFormType::class);
        $assignQuestionFAQForm->handleRequest($request);
        //Handling of submission of modification of faq affectation
        $this->handleAssignQuestionFAQFormSubmission($assignQuestionFAQForm, $questionRepository, $entityManager);

        //Unassigned questions
        $unassignedQuestions = $this->getUnassignedQuestions($questionRepository);
        return $this->render('faq/unassigned_questions.html.twig', [
            'unassignedQuestions' => $unassignedQuestions,
            'assignQuestionFAQForm' => $assignQuestionFAQForm->createView()
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

    private function handleAnswerFormSubmission(Form $form, Question $question, EntityManagerInterface $entityManager, Answer $answer)
    {
        if($form->isSubmitted())
        {
            if($form->isValid())
            {
                $answer->setRelatedQuestion($question);
                $answer->setEditor($this->getUser());
                $currentDate = new \DateTime('now');
                $answer->setCreationDate($currentDate);
                $entityManager->persist($answer);
                $entityManager->flush();
                $this->addFlash('new_answer_success', 'The answer has successfully been added.');
            }
            else
                $this->addFlash('new_answer_error', 'Error while trying to add the answer.');
        }
    }

    private function handleModifyAnswerFormSubmission(Form $form, AnswerRepository $answerRepository, EntityManagerInterface $entityManager)
    {
        if($form->isSubmitted())
        {
            if($form->isValid())
            {
                $answer = $answerRepository->find($form->get('answer_id')->getData());
                if($answer == null)
                {
                    $this->addFlash('modify_answer_error', 'The submitted answer doesn\'t exist.');
                    return;
                }
                if($answer->getEditor()->getEmail() != $this->getUser()->getUserIdentifier())
                {
                    $this->addFlash('modify_answer_error', 'You aren\'t the creator of the answer.');
                    return;
                }
                $answer->setContent($form->get('content')->getData());
                $entityManager->persist($answer);
                $entityManager->flush();
                $this->addFlash('modify_answer_success', 'The answer was successfully been updated.');
            }
            else
                $this->addFlash('modify_answer_invalid_error', 'Error while trying to modify the answer : invalid form.');
        }
    }

    private function handleAssignQuestionFAQFormSubmission(Form $form, QuestionRepository $questionRepository, EntityManagerInterface $entityManager)
    {
        if($form->isSubmitted())
        {
            if($form->isValid())
            {
                $question = $questionRepository->find($form->get('question_id')->getData());
                if($question == null)
                    $this->addFlash('assign_question_faq_error', 'The requested submitted question doesn\'t exist.');
                else
                {
                    $faqs = $form->get('faq')->getData();
                    foreach($faqs as $faq)
                    {
                        $question->addBelongingFAQ($faq);
                    }
                    $entityManager->persist($question);
                    $entityManager->flush();
                    $this->addFlash('assign_question_faq_success', 'The faqs were successfully assigned to the question.');
                }
            }
            else
                $this->addFlash('assign_question_faq_error', 'Error while trying to modify the assignation of the question : invalid form.');
        }
    }

    private function handleAssignQuestionFormSubmission(Form $form, EntityManagerInterface $entityManager, FAQ $faq)
    {
        if($form->isSubmitted())
        {
            if($form->isValid())
            {
                $questions = $form->get('questions')->getData();
                foreach($questions as $question)
                {
                    $faq->addRelatedQuestion($question);
                }
                $entityManager->persist($faq);
                $entityManager->flush();
                $this->addFlash('assign_question_faq_success', 'The questions were successfullly assigned to the faq.');
            }
            else
                $this->addFlash('assign_question_faq_error', 'Error while trying to assign the questions to the faq.');
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

    private function hasRightToDeleteQuestion($moderatedFAQs, $belongingsFAQs)
    {
        if($this->isGranted('ROLE_SUPERADMIN'))
            return true;
        foreach($moderatedFAQs as $moderatedFAQ)
        {
            if($belongingsFAQs->contains($moderatedFAQ))
                return true;
        }
        return false;
    }

    public function renderDeleteButton($question_id, UserRepository $userRepository, QuestionRepository $questionRepository):Response
    {
        $question = $questionRepository->find($question_id);
        if($question == null)
            return new Response('');
        $belongingsFAQs = $question->getBelongingFAQs();
        if($this->isGranted('ROLE_USER'))
        {
            $currentUser = $userRepository->findOneBy(['email' => $this->getUser()->getUserIdentifier()]);
            $moderatedFAQs = $currentUser->getModeratedFAQs();
        }
        if($this->isGranted('ROLE_SUPERADMIN') || ($this->isGranted('ROLE_ADMIN') && $this->hasRightToDeleteQuestion($moderatedFAQs, $belongingsFAQs)) || ($this->isGranted('ROLE_USER') && $question->getCreator() == $this->getUser()))
            return $this->render('faq/delete_question_button.html.twig', [
                'question' => $question
            ]);
        else
            return new Response('');
    }
}
