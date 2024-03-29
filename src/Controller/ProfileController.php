<?php

namespace App\Controller;

use App\Form\RegistrationFormType;
use App\Entity\User;
use App\Form\AddSkillType;
use App\Form\ChangeCVFormType;
use App\Form\ChangeNameFormType;
use App\Form\ChangeOccupationFormType;
use App\Form\ChangePasswordRequestFormType;
use App\Form\ChangeProfilePictureFormType;
use App\Repository\SkillRepository;
use App\Repository\UserRepository;
use App\Service\FileUploader;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use SymfonyCasts\Bundle\VerifyEmail\Exception\VerifyEmailExceptionInterface;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Core\Authentication\Token\AnonymousToken;
use Symfony\Component\Security\Core\Security;

class ProfileController extends AbstractController
{
    private EmailVerifier $emailVerifier;

    public function __construct(EmailVerifier $emailVerifier, RequestStack $requestStack)
    {
        $this->emailVerifier = $emailVerifier;
        $this->requestStack = $requestStack;
    }
    /**
     * @Route("/login", name="login")
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();
        return $this->render('profile/login.html.twig', [
            'error' => $error,
            'last_username' => $lastUsername
        ]);
    }

    /**
     * @Route("/logout", name="logout", methods={"GET"})
     */
    public function logout(): Response
    {
        throw new \Exception("Activated through security.yaml file");
    }

    /**
     * @Route("/delete-account/{id}", name="delete_account", methods={"GET"})
     */
    public function deleteAccount($id, UserRepository $userRepository, EntityManagerInterface $entityManager, MailerInterface $mailer, Filesystem $filesystem): Response
    {
        $user = $userRepository->find($id);
        $adminDelete = false;
        $userRole = $user->getRoles()[0];
        if($userRole == 'ROLE_SUPERADMIN')
            throw new \Exception("We can't delete a superadmin user !");
        
        //Invalidate session if the user is deleting his own account
        if($this->getUser() == $user)
        {
            $session = $this->requestStack->getSession();
            $session = new Session();
            $session->invalidate();
        }
        else
        {
            $this->denyAccessUnlessGranted('ROLE_SUPERADMIN');
            $adminDelete = true;
        }
        //Generate email for confirmation and send it to the user
        $email = (new TemplatedEmail())
            ->from(new Address('werner94@hotmail.com', 'Admin Solidark'))
            ->to($user->getEmail())
            ->subject('Account removal')
            ->htmlTemplate('mails/delete_account_email.html.twig')
            ->context([
                'user' => $user
            ]);
        //Delete profile picture and CV files of the user
        $filesystem->remove([$user->getProfilePicture()]);
        $filesystem->remove([$user->getCV()]);
        //Delete the user from the database
        $entityManager->remove($user);
        $entityManager->flush();

        $mailer->send($email);
        if($adminDelete)
        {
            $this->addFlash('account_deleted', 'The account has successfully been removed.');
            return $this->redirectToRoute('admin_manager');
        }
        return $this->redirectToRoute('index', [
            'deleteAccount' => 1
        ]);
    }

    /**
     * @Route("modify-password", name="modify_password")
     */
    public function modifyPassword(Request $request, EntityManagerInterface $entityManager, UserPasswordHasherInterface $passwordHasher): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(ChangePasswordRequestFormType::class);
        $form->handleRequest($request);
        $validated = false;
        if($form->isSubmitted())
        {
            if($form->isValid())
            {
                //Hash password sent
                $password = $form->get('new_password')->getData();
                $passwordHashed = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($passwordHashed);
                
                //Save user
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('change_password_success', 'Your password has successfully been changed.');
                return $this->redirectToRoute('my_profile');
            }
            else
            {
                $validated = true;
            }
        }
        return $this->render('profile/modify_password.html.twig',[
            'form' => $form->createView(),
            'validated' => $validated
        ]);
    }

    /**
     * @Route("/my-profile", name="my_profile")
     */
    public function myProfile(Request $request, EntityManagerInterface $entityManager, SkillRepository $skillRepository, FileUploader $fileUploader, Filesystem $filesystem): Response
    {
        $user = $this->getUser();
        //Change profile picture form
        $changeProfilePictureForm = $this->createForm(ChangeProfilePictureFormType::class);
        $changeProfilePictureForm->handleRequest($request);
        //Handle submission change of profile picture
        $this->handleChangePictureSubmission($changeProfilePictureForm, $entityManager, $fileUploader, $filesystem, $user);

        //Change name form
        $changeNameForm = $this->createForm(ChangeNameFormType::class);
        $changeNameForm->get('first_name')->setData($user->getFirstName());
        $changeNameForm->get('last_name')->setData($user->getLastName());
        $changeNameForm->handleRequest($request);
        //Handle submission change of Name
        $this->handleChangeNameSubmission($changeNameForm, $entityManager, $user);

        //Change occupation form
        $changeOccupationForm = $this->createForm(ChangeOccupationFormType::class);
        $changeOccupationForm->get('occupation')->setData($user->getOccupation());
        $changeOccupationForm->handleRequest($request);
        //Handle submission change of Occupation
        $this->handleChangeOccupationSubmission($changeOccupationForm, $entityManager, $user);

        //Change CV form
        $changeCVForm = $this->createForm(ChangeCVFormType::class);
        $changeCVForm->handleRequest($request);
        //Handle submission change of CV
        $this->handleChangeCVSubmission($changeCVForm, $entityManager, $fileUploader, $filesystem, $user);

        //Add skill form
        $addSkillForm = $this->createForm(AddSkillType::class, null , ['user' => $user]);
        $addSkillForm->handleRequest($request);
        //Handle submission of new skill to the user
        $this->handleAddSkillSubmission($addSkillForm, $entityManager, $user);

        //List of all skills
        $skills = $skillRepository->findAll();
        return $this->render('profile/profile.html.twig', [
            'user' => $user,
            'skills' => $skills,
            'changeProfilePictureForm' => $changeProfilePictureForm->createView(),
            'changeNameForm' => $changeNameForm->createView(),
            'changeOccupationForm' => $changeOccupationForm->createView(),
            'changeCVForm' => $changeCVForm->createView(),
            'addSkillForm' => $addSkillForm->createView()
        ]);
    }

    /**
     * @Route("/delete-skill-user/{id}", name="delete_skill_user", methods={"GET"})
     */
    public function deleteSkill($id, SkillRepository $skillRepository, EntityManagerInterface $entityManager): Response
    {
        $skillToRemove = $skillRepository->find($id);
        $user = $this->getUser();
        if($skillToRemove == null)
            $this->addFlash('delete_skill_error', 'The skill doesn\'t exist.');
        else
        {
            if($skillToRemove->getUsersThatHasSkill()->contains($user))
            {
                $skillToRemove->removeUsersThatHasSkill($user);
                $entityManager->persist($skillToRemove);
                $entityManager->flush();
                $this->addFlash('delete_skill_success', 'The skill was successfully removed from the user.');
            }
            else
                $this->addFlash('delete_skill_error', 'The user doesn\'t have this skill.');
        }
        return $this->redirectToRoute('my_profile');
    }
    
    /**
     * @Route("/profile/{id}", name="profile")
     */
    public function profile($id, UserRepository $userRepository): Response
    {
        $user = $userRepository->find($id);
        if($user == $this->getUser())
            return $this->redirectToRoute('my_profile');

        if($user == null || $user->getRoles()[0] == 'ROLE_SUPERADMIN')
        {
            $this->addFlash('user_not_found', 'The user requested doesn\'t exist');
            return $this->redirectToRoute('index');
        }
        else
        {
            return $this->render('profile/profile.html.twig', [
                'user' => $user
            ]);
        }
    }

    /**
     * @Route("/show-profile-picture/{id}", name="show_profile_picture")
     */
    public function showProfilePicture(UserRepository $userRepository, $id): Response
    {
        $user = $userRepository->find($id);
        $pathProfilePicture = $user->getProfilePicture();
        return new BinaryFileResponse($pathProfilePicture);
    }

    /**
     * @Route("/show-cv/{id}", name="show_cv")
     */
    public function showCV(UserRepository $userRepository, $id): Response
    {
        $user = $userRepository->find($id);
        $pathCV = $user->getCV();
        return new BinaryFileResponse($pathCV);
    }

    /**
     * @Route("/register", name="register")
     */
    public function register(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $entityManager, FileUploader $fileUploader): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);
        $validated = false;
        if($form->isSubmitted())
        {
            if($form->isValid())
            {
                //Set user role
                $user->setRoles(['ROLE_USER']);
                //Hash password
                $password = $user->getPassword();
                $passwordHashed = $passwordHasher->hashPassword($user, $password);
                $user->setPassword($passwordHashed);

                //Save profile picture and update new path
                $profilePictureFile = $form->get('profile_picture')->getData();
                $profilePicturePath = $fileUploader->uploadPhoto($profilePictureFile);
                $user->setProfilePicture($profilePicturePath);

                //Save CV and update new path
                $CVFile = $form->get('CV')->getData();
                $CVPath = $fileUploader->uploadCV($CVFile);
                $user->setCV($CVPath);

                //Save user
                $entityManager->persist($user);
                $entityManager->flush();

                //Generate email for confirmation and send it to the user
                $this->emailVerifier->sendEmailConfirmation('verify_email', $user,
                    (new TemplatedEmail())
                        ->from(new Address('werner94@hotmail.com', 'Inscription Solidark'))
                        ->to($user->getEmail())
                        ->subject('Please Confirm your Email')
                        ->htmlTemplate('mails/confirmation_email.html.twig')
                );
                //Flash confirmation email     
                $this->addFlash('confirmation', 'A confirmation email has been sent');

                //Redirect to main page
                return $this->redirectToRoute('index');
            }
            else
            {
                $this->addFlash('submission_error', 'The submitted form was invalid. Please restart the procedure');
                $validated = true;
            }
        }
        return $this->render('profile/register.html.twig', [
            'form' => $form->createView(),
            'validated' => $validated
        ]);
    }

    /**
     * @Route("/validate-step-0", name="validate_step_0", methods={"POST"})
     */
    public function validateStep0(Request $request, ValidatorInterface $validator):Response
    {
        $email = $request->get('email');
        $password = $request->get('password');
        $emailErrorMessage = 'fine';
        $passwordErrorMessage = 'fine';
        //Create test user to check validation
        $user = new User();
        if($email != null)
            $user->setEmail($email);
        if($password != null)
            $user->setPassword($password);
        $violations = $validator->validate($user);
        //Retrieve validation errors
        foreach($violations as $violation)
        {
            if($violation->getPropertyPath() == 'email')
                $emailErrorMessage = $violation->getMessage();
            else if($violation->getPropertyPath() == 'password')
                $passwordErrorMessage = $violation->getMessage();
        }
        //Send response
        return $this->json([
            'emailErrorMessage' => $emailErrorMessage,
            'passwordErrorMessage' => $passwordErrorMessage
        ]);
    }

    /**
     * @Route("/validate-step-1", name="validate_step_1", methods={"POST"})
     */
    public function validateStep1(Request $request, ValidatorInterface $validator):Response
    {
        $username = $request->get('username');
        $first_name = $request->get('first_name');
        $last_name = $request->get('last_name');
        $usernameErrorMessage = 'fine';
        $firstNameErrorMessage = 'fine';
        $lastNameErrorMessage = 'fine';
        //Create test user to check validation
        $user = new User();
        $user->setUsername($username);
        $user->setFirstName($first_name);
        $user->setLastName($last_name);
        $violations = $validator->validate($user);
        //Retrieve validation errors
        foreach($violations as $violation)
        {
            if($violation->getPropertyPath() == 'username')
                $usernameErrorMessage = $violation->getMessage();
            else if($violation->getPropertyPath() == 'first_name')
                $firstNameErrorMessage = $violation->getMessage();
            else if($violation->getPropertyPath() == 'last_name')
                $lastNameErrorMessage = $violation->getMessage();
        }
        //Send response
        return $this->json([
            'usernameErrorMessage' => $usernameErrorMessage,
            'firstNameErrorMessage' => $firstNameErrorMessage,
            'lastNameErrorMessage' => $lastNameErrorMessage
        ]);
    }

    /**
     * @Route("/validate-step-2", name="validate_step_2", methods={"POST"})
     */
    public function validateStep2(Request $request, ValidatorInterface $validator):Response
    {
        $occupation = $request->get('occupation');
        $profilePicture = $request->get('profile_picture');
        $CV = $request->get('CV');
        $occupationErrorMessage = 'fine';
        $profilePictureErrorMessage = 'fine';
        $CVErrorMessage = 'fine';
        $profilePictureExtension = '';
        $CVExtension = '';
        //Extension validation of Profile picture
        if($profilePicture != '')
        {
            $profilePictureExtension = strtolower(pathinfo($profilePicture, PATHINFO_EXTENSION));
            if($profilePictureExtension != 'png' && $profilePictureExtension != 'jpeg')
                $profilePictureErrorMessage = "Please upload a picture in .jpeg or .png format.";
        }
        else
            $profilePictureErrorMessage = "Please upload your Profile picture.";
        //Extension validation of CV
        if($CV != '')
        {
            $CVExtension = strtolower(pathinfo($CV, PATHINFO_EXTENSION));
            if($CVExtension != 'pdf' && $CVExtension != 'x-pdf')
                $CVErrorMessage = "Please upload a pdf file.";
        }
        else
            $CVErrorMessage = "Please upload your CV.";
        //Create test user to check validation of occupation
        $user = new User();
        $user->setOccupation($occupation);
        $violations = $validator->validate($user);
        //Retrieve validation errors
        foreach($violations as $violation)
            if($violation->getPropertyPath() == 'occupation')
                $occupationErrorMessage = $violation->getMessage();

        //Send response
        return $this->json([
            'occupationErrorMessage' => $occupationErrorMessage,
            'profilePictureErrorMessage' => $profilePictureErrorMessage,
            'CVErrorMessage' => $CVErrorMessage
        ]);
    }

    /**
     * @Route("/verify-email", name="verify_email")
     */
    public function verifyEmail(Request $request, UserRepository $userRepository): Response
    {
        $id = $request->get('id');
        if($id === null)
            return $this->redirectToRoute('register');

        $user = $userRepository->find($id);
        if($user === null)
            return $this->redirectToRoute('register');

        //Validation of the email by clicking the confirmation link
        try
        {
            $this->emailVerifier->handleEmailConfirmation($request, $user);
        }
        catch(VerifyEmailExceptionInterface $exception)
        {
            $this->addFlash('verify_email_error', $exception->getReason());
            return $this->redirectToRoute('register');
        }

        $this->addFlash('verification', 'Your email address has been verified.');
        return $this->redirectToRoute('my_profile');
    }

    private function handleChangePictureSubmission(Form $form, EntityManagerInterface $entityManager, FileUploader $fileUploader, Filesystem $filesystem, User $user)
    {
        if($form->isSubmitted())
        {
            if($form->isValid())
            {
                //Delete old profile picture of the user
                $filesystem->remove([$user->getProfilePicture()]);
                
                //Save new profile picture and update new path
                $profilePictureFile = $form->get('profile_picture')->getData();
                $profilePicturePath = $fileUploader->uploadPhoto($profilePictureFile);
                $user->setProfilePicture($profilePicturePath);
                
                //Save user
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('photo_changed_success', 'Your profile photo has successfully been changed.');
            }
            else
                $this->addFlash('photo_changed_error', 'A problem happened while attempting to change your profile photo : ');
        }
    }

    private function handleChangeNameSubmission(Form $form, EntityManagerInterface $entityManager, User $user)
    {
        if($form->isSubmitted())
        {
            if($form->isValid())
            {
                $first_name = $form->get('first_name')->getData();
                $last_name = $form->get('last_name')->getData();
                $user->setFirstName($first_name);
                $user->setLastName($last_name);
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('change_name_success', 'Your name has successfully been changed.');
            }
            else
                $this->addFlash('change_name_error', 'A problem occurred while attempting to change your name.');
        }
    }

    private function handleChangeOccupationSubmission(Form $form, EntityManagerInterface $entityManager, User $user)
    {
        if($form->isSubmitted())
        {
            if($form->isValid())
            {
                $occupation = $form->get('occupation')->getData();
                $user->setOccupation($occupation);
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('change_occupation_success', 'Your occupation has successfully been changed.');
            }
            else
                $this->addFlash('change_occupation_error', 'There was an error while trying to change your occupation.');
        }
    }

    private function handleChangeCVSubmission(Form $form, EntityManagerInterface $entityManager, FileUploader $fileUploader, Filesystem $filesystem, User $user)
    {
        if($form->isSubmitted())
        {
            if($form->isValid())
            {
                //Delete old CV file of the user
                $filesystem->remove([$user->getCV()]);

                //Save new CV and update new path
                $CVFile = $form->get('CV')->getData();
                $CVPath = $fileUploader->uploadCV($CVFile);
                $user->setCV($CVPath);
                $entityManager->persist($user);
                $entityManager->flush();
                $this->addFlash('change_CV_success', 'Your CV has successfully been changed.');
            }
            else
                $this->addFlash('change_CV_error', 'There was a problem while trying to change your CV.');
        }
    }

    private function handleAddSkillSubmission(Form $form, EntityManagerInterface $entityManager, User $user)
    {
        if($form->isSubmitted())
        {
            if($form->isValid())
            {
                $skill = $form->get('skill')->getData();
                $userSkills = $user->getUserSkills();
                if($userSkills->contains($skill))
                    $this->addFlash('add_skill_error', 'The user already has this skill.');
                else
                {
                    $user->addUserSkill($skill);
                    $entityManager->persist($user);
                    $entityManager->flush();
                    $this->addFlash('add_skill_success', 'The skill was successfully added to the user.');
                }
            }
            else
                $this->addFlash('add_skill_error', 'The skill submitted isn\'t valid.');
        }
    }
}
