<?php

namespace App\Entity;

use App\Repository\QuestionRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="questions")
 * @UniqueEntity(fields="title", message="A question with this title already exists.")
 * @ORM\Entity(repositoryClass=QuestionRepository::class)
 */
class Question
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Assert\NotBlank(message = "Please enter a valid title.")
     * @ORM\Column(type="string", length=45)
     */
    private $title;

    /**
     * @Assert\NotBlank(message = "Please enter a valid content.")
     * @ORM\Column(type="text")
     */
    private $content;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="askedQuestions")
     * @ORM\JoinColumn(nullable=false)
     */
    private $creator;

    /**
     * @ORM\Column(type="datetime")
     */
    private $creationDate;

    /**
     * @ORM\ManyToMany(targetEntity=FAQ::class, inversedBy="relatedQuestions")
     * @ORM\JoinTable(name="questions_belongings")
     */
    private $belongingFAQs;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, inversedBy="likedQuestions")
     * @ORM\OrderBy({"username" = "ASC"})
     * @ORM\JoinTable(name="questions_likes")
     */
    private $likingUsers;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, inversedBy="dislikedQuestions")
     * @ORM\OrderBy({"username" = "ASC"})
     * @ORM\JoinTable(name="questions_dislikes")
     */
    private $dislikingUsers;

    /**
     * @ORM\OneToMany(targetEntity=Answer::class, mappedBy="relatedQuestion", orphanRemoval=true)
     * @ORM\OrderBy({"creationDate" = "DESC"})
     */
    private $answers;

    public function __construct()
    {
        $this->belongingFAQs = new ArrayCollection();
        $this->likingUsers = new ArrayCollection();
        $this->dislikingUsers = new ArrayCollection();
        $this->answers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getCreator(): ?User
    {
        return $this->creator;
    }

    public function setCreator(?User $creator): self
    {
        $this->creator = $creator;

        return $this;
    }

    public function getCreationDate(): ?\DateTimeInterface
    {
        return $this->creationDate;
    }

    public function setCreationDate(\DateTimeInterface $creationDate): self
    {
        $this->creationDate = $creationDate;

        return $this;
    }

    /**
     * @return Collection|FAQ[]
     */
    public function getBelongingFAQs(): Collection
    {
        return $this->belongingFAQs;
    }

    public function addBelongingFAQ(FAQ $belongingFAQ): self
    {
        if (!$this->belongingFAQs->contains($belongingFAQ)) {
            $this->belongingFAQs[] = $belongingFAQ;
        }

        return $this;
    }

    public function removeBelongingFAQ(FAQ $belongingFAQ): self
    {
        $this->belongingFAQs->removeElement($belongingFAQ);

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getLikingUsers(): Collection
    {
        return $this->likingUsers;
    }

    public function addLikingUser(User $likingUser): self
    {
        if (!$this->likingUsers->contains($likingUser)) {
            $this->likingUsers[] = $likingUser;
        }

        $this->removeDislikingUser($likingUser);

        return $this;
    }

    public function removeLikingUser(User $likingUser): self
    {
        $this->likingUsers->removeElement($likingUser);

        return $this;
    }

    /**
     * @return Collection|User[]
     */
    public function getDislikingUsers(): Collection
    {
        return $this->dislikingUsers;
    }

    public function addDislikingUser(User $dislikingUser): self
    {
        if (!$this->dislikingUsers->contains($dislikingUser)) {
            $this->dislikingUsers[] = $dislikingUser;
        }

        $this->removelikingUser($dislikingUser);

        return $this;
    }

    public function removeDislikingUser(User $dislikingUser): self
    {
        $this->dislikingUsers->removeElement($dislikingUser);

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
            $answer->setRelatedQuestion($this);
        }

        return $this;
    }

    public function removeAnswer(Answer $answer): self
    {
        if ($this->answers->removeElement($answer)) {
            // set the owning side to null (unless already changed)
            if ($answer->getRelatedQuestion() === $this) {
                $answer->setRelatedQuestion(null);
            }
        }

        return $this;
    }
}
