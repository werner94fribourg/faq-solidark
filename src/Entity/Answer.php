<?php

namespace App\Entity;

use App\Repository\AnswerRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="answers")
 * @ORM\Entity(repositoryClass=AnswerRepository::class)
 */
class Answer
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Assert\NotBlank(message = "Please enter a valid content.")
     * @ORM\Column(type="text")
     */
    private $content;

    /**
     * @ORM\ManyToOne(targetEntity=Question::class, inversedBy="answers")
     * @ORM\JoinColumn(nullable=false)
     */
    private $relatedQuestion;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="answers")
     * @ORM\JoinColumn(nullable=false)
     */
    private $editor;

    /**
     * @ORM\Column(type="datetime")
     */
    private $creationDate;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, inversedBy="likedAnswers")
     * @ORM\OrderBy({"username" = "ASC"})
     * @ORM\JoinTable(name="answers_likes")
     */
    private $likingUsers;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, inversedBy="dislikedAnswers")
     * @ORM\OrderBy({"username" = "ASC"})
     * @ORM\JoinTable(name="answers_dislikes")
     */
    private $dislikingUsers;

    public function __construct()
    {
        $this->likingUsers = new ArrayCollection();
        $this->dislikingUsers = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
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
    
    public function getRelatedQuestion(): ?Question
    {
        return $this->relatedQuestion;
    }

    public function setRelatedQuestion(?Question $relatedQuestion): self
    {
        $this->relatedQuestion = $relatedQuestion;

        return $this;
    }

    public function getEditor(): ?User
    {
        return $this->editor;
    }

    public function setEditor(?User $editor): self
    {
        $this->editor = $editor;

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

        $this->removeLikingUser($dislikingUser);
        
        return $this;
    }

    public function removeDislikingUser(User $dislikingUser): self
    {
        $this->dislikingUsers->removeElement($dislikingUser);

        return $this;
    }
}
