<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="users")
 * @UniqueEntity(fields="email", message="This email address is already used.")
 * @UniqueEntity(fields="username", message="This username is already used.")
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Assert\NotBlank(message = "Please enter a valid email address.")
     * @Assert\Email(message = "Please enter a valid email address.")
     * @ORM\Column(type="string", length=180, unique=true)
     */
    private $email;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @var string The hashed password
     * @Assert\NotBlank(message = "Please enter a valid password.")
     * @Assert\Regex(pattern="/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&-_])[A-Za-z\d@$!%*?&-_]{8,}$/", message="A password must contain at least an uppercase letter, a lower case letter, a digit and 1 special character (-,_,',...) and must be at least of length 8.")
     * @ORM\Column(type="string")
     */
    private $password;

    /**
     * @Assert\NotBlank(message = "Please enter a valid last name.")
     * @ORM\Column(type="string", length=45)
     */
    private $username;

    /**
     * @Assert\NotBlank(message = "Please enter a valid last name.")
     * @ORM\Column(type="string", length=45)
     */
    private $last_name;

    /**
     * @Assert\NotBlank(message = "Please enter a valid first name.")
     * @ORM\Column(type="string", length=45)
     */
    private $first_name;

    /**
     * @Assert\NotBlank(message = "Please enter a valid occupation.")
     * @ORM\Column(type="string", length=45)
     */
    private $occupation;

    /**
     * @Assert\NotBlank(message = "Please upload your Profile picture.")
     * @Assert\File(mimeTypes={"image/png", "image/jpeg"}, mimeTypesMessage="Please upload a picture in .jpeg or .png format.")
     * @ORM\Column(type="string", length=200)
     */
    private $profile_picture;

    /**
     * @Assert\NotBlank(message = "Please upload your CV.")
     * @Assert\File(mimeTypes={"application/pdf", "application/x-pdf"}, mimeTypesMessage="Please upload a pdf file.")
     * @ORM\Column(type="string", length=200)
     */
    private $CV;

    /**
     * @ORM\ManyToMany(targetEntity=Skill::class, inversedBy="usersThatHasSkill")
     * @ORM\JoinTable(name="shared_skills")
     */
    private $userSkills;

    /**
     * @ORM\OneToMany(targetEntity=FAQ::class, mappedBy="moderator")
     */
    private $moderatedFAQs;

    /**
     * @ORM\OneToMany(targetEntity=Question::class, mappedBy="creator", orphanRemoval=true)
     */
    private $askedQuestions;

    /**
     * @ORM\ManyToMany(targetEntity=Question::class, mappedBy="likingUsers")
     * @ORM\JoinTable(name="questions_likes")
     */
    private $likedQuestions;

    /**
     * @ORM\ManyToMany(targetEntity=Question::class, mappedBy="dislikingUsers")
     * @ORM\JoinTable(name="questions_dislikes")
     */
    private $dislikedQuestions;

    /**
     * @ORM\OneToMany(targetEntity=Answer::class, mappedBy="editor", orphanRemoval=true)
     */
    private $answers;

    /**
     * @ORM\ManyToMany(targetEntity=Answer::class, mappedBy="likingUsers")
     * @ORM\JoinTable(name="answers_likes")
     */
    private $likedAnswers;

    /**
     * @ORM\ManyToMany(targetEntity=Answer::class, mappedBy="dislikingUsers")
     * @ORM\JoinTable(name="answers_dislikes")
     */
    private $dislikedAnswers;

    /**
     * @ORM\Column(type="boolean")
     */
    private $isVerified = false;

    public function __construct()
    {
        $this->userSkills = new ArrayCollection();
        $this->moderatedFAQs = new ArrayCollection();
        $this->askedQuestions = new ArrayCollection();
        $this->likedQuestions = new ArrayCollection();
        $this->dislikedQuestions = new ArrayCollection();
        $this->answers = new ArrayCollection();
        $this->likedAnswers = new ArrayCollection();
        $this->dislikedAnswers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @deprecated since Symfony 5.3, use getUserIdentifier instead
     */
    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @see UserInterface
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Returning a salt is only needed, if you are not using a modern
     * hashing algorithm (e.g. bcrypt or sodium) in your security.yaml.
     *
     * @see UserInterface
     */
    public function getSalt(): ?string
    {
        return null;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials()
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->last_name;
    }

    public function setLastName(string $last_name): self
    {
        $this->last_name = $last_name;

        return $this;
    }

    public function getFirstName(): ?string
    {
        return $this->first_name;
    }

    public function setFirstName(string $first_name): self
    {
        $this->first_name = $first_name;

        return $this;
    }

    public function getOccupation(): ?string
    {
        return $this->occupation;
    }

    public function setOccupation(string $occupation): self
    {
        $this->occupation = $occupation;

        return $this;
    }

    public function getProfilePicture(): ?string
    {
        return $this->profile_picture;
    }

    public function setProfilePicture(string $profile_picture): self
    {
        $this->profile_picture = $profile_picture;

        return $this;
    }

    public function getCV(): ?string
    {
        return $this->CV;
    }

    public function setCV(string $CV): self
    {
        $this->CV = $CV;

        return $this;
    }

    /**
     * @return Collection|Skill[]
     */
    public function getUserSkills(): Collection
    {
        return $this->userSkills;
    }

    public function addUserSkill(Skill $userSkill): self
    {
        if (!$this->userSkills->contains($userSkill)) {
            $this->userSkills[] = $userSkill;
        }

        return $this;
    }

    public function removeUserSkill(Skill $userSkill): self
    {
        $this->userSkills->removeElement($userSkill);

        return $this;
    }

    /**
     * @return Collection|FAQ[]
     */
    public function getModeratedFAQs(): Collection
    {
        return $this->moderatedFAQs;
    }

    public function addModeratedFAQ(FAQ $moderatedFAQ): self
    {
        if(in_array('ROLE_ADMIN', $this->getRoles(), true))
        {
            if (!$this->moderatedFAQs->contains($moderatedFAQ)) {
                $this->moderatedFAQs[] = $moderatedFAQ;
                $moderatedFAQ->setModerator($this);
            }
        }
        return $this;
    }

    public function removeModeratedFAQ(FAQ $moderatedFAQ): self
    {
        if(in_array('ROLE_ADMIN', $this->getRoles(), true))
        {
            if ($this->moderatedFAQs->removeElement($moderatedFAQ)) {
                // set the owning side to null (unless already changed)
                if ($moderatedFAQ->getModerator() === $this) {
                    $moderatedFAQ->setModerator(null);
                }
            }
        }
        return $this;
    }

    /**
     * @return Collection|Question[]
     */
    public function getAskedQuestions(): Collection
    {
        return $this->askedQuestions;
    }

    public function addAskedQuestion(Question $askedQuestion): self
    {
        if (!$this->askedQuestions->contains($askedQuestion)) {
            $this->askedQuestions[] = $askedQuestion;
            $askedQuestion->setCreator($this);
        }

        return $this;
    }

    public function removeAskedQuestion(Question $askedQuestion): self
    {
        if ($this->askedQuestions->removeElement($askedQuestion)) {
            // set the owning side to null (unless already changed)
            if ($askedQuestion->getCreator() === $this) {
                $askedQuestion->setCreator(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Question[]
     */
    public function getLikedQuestions(): Collection
    {
        return $this->likedQuestions;
    }

    public function addLikedQuestion(Question $likedQuestion): self
    {
        if($likedQuestion->getCreator() != $this)
        {
            if (!$this->likedQuestions->contains($likedQuestion)) {
                $this->likedQuestions[] = $likedQuestion;
                $likedQuestion->addLikingUser($this);
            }
    
            if($this->dislikedQuestions->contains($likedQuestion))
            {
                $this->removeDislikedQuestion($likedQuestion);
                $likedQuestion->removeDislikingUser($this);
            }
        }

        return $this;
    }

    public function removeLikedQuestion(Question $likedQuestion): self
    {
        if ($this->likedQuestions->removeElement($likedQuestion)) {
            $likedQuestion->removeLikingUser($this);
        }

        return $this;
    }

    /**
     * @return Collection|Question[]
     */
    public function getDislikedQuestions(): Collection
    {
        return $this->dislikedQuestions;
    }

    public function addDislikedQuestion(Question $dislikedQuestion): self
    {
        if($dislikedQuestion->getCreator() != $this)
        {
            if (!$this->dislikedQuestions->contains($dislikedQuestion)) {
                $this->dislikedQuestions[] = $dislikedQuestion;
                $dislikedQuestion->addDislikingUser($this);
            }
    
            if($this->likedQuestions->contains($dislikedQuestion))
            {
                $this->removeLikedQuestion($dislikedQuestion);
                $dislikedQuestion->removeLikingUser($this);
            }
        }

        return $this;
    }

    public function removeDislikedQuestion(Question $dislikedQuestion): self
    {
        if ($this->dislikedQuestions->removeElement($dislikedQuestion)) {
            $dislikedQuestion->removeDislikingUser($this);
        }

        return $this;
    }

    /**
     * @return Collection|Answer[]
     */
    public function getAnswers(): Collection
    {
        return $this->answers;
    }

    public function addAnswer(Answer $answer): self
    {
        if (!$this->answers->contains($answer)) {
            $this->answers[] = $answer;
            $answer->setEditor($this);
        }

        return $this;
    }

    public function removeAnswer(Answer $answer): self
    {
        if ($this->answers->removeElement($answer)) {
            // set the owning side to null (unless already changed)
            if ($answer->getEditor() === $this) {
                $answer->setEditor(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection|Answer[]
     */
    public function getLikedAnswers(): Collection
    {
        return $this->likedAnswers;
    }

    public function addLikedAnswer(Answer $likedAnswer): self
    {
        if($likedAnswer->getEditor() != $this)
        {
            if (!$this->likedAnswers->contains($likedAnswer)) {
                $this->likedAnswers[] = $likedAnswer;
                $likedAnswer->addLikingUser($this);
            }

            if($this->dislikedAnswers->contains($likedAnswer))
            {
                $this->removeLikedAnswer($likedAnswer);
                $likedAnswer->removeLikingUser($this);
            }
        }

        return $this;
    }

    public function removeLikedAnswer(Answer $likedAnswer): self
    {
        if ($this->likedAnswers->removeElement($likedAnswer)) {
            $likedAnswer->removeLikingUser($this);
        }

        return $this;
    }

    /**
     * @return Collection|Answer[]
     */
    public function getDislikedAnswers(): Collection
    {
        return $this->dislikedAnswers;
    }

    public function addDislikedAnswer(Answer $dislikedAnswer): self
    {
        if($dislikedAnswer->getEditor() != $this)
        {
            if (!$this->dislikedAnswers->contains($dislikedAnswer)) {
                $this->dislikedAnswers[] = $dislikedAnswer;
                $dislikedAnswer->addDislikingUser($this);
            }
    
            if($this->likedAnswers->contains($dislikedAnswer))
            {
                $this->removedislikedAnswer($dislikedAnswer);
                $dislikedAnswer->removeDislikingUser($this);
            }
        }

        return $this;
    }

    public function removeDislikedAnswer(Answer $dislikedAnswer): self
    {
        if ($this->dislikedAnswers->removeElement($dislikedAnswer)) {
            $dislikedAnswer->removeDislikingUser($this);
        }

        return $this;
    }

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): self
    {
        $this->isVerified = $isVerified;

        return $this;
    }
}
