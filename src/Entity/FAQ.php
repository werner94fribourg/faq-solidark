<?php

namespace App\Entity;

use App\Repository\FAQRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table(name="faqs")
 * @UniqueEntity(fields="name", message="A FAQ with this name already exists.")
 * @ORM\Entity(repositoryClass=FAQRepository::class)
 */
class FAQ
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Assert\NotBlank(message = "Please enter a valid FAQ name.")
     * @ORM\Column(type="string", length=45)
     */
    private $name;

    /**
     * @ORM\Column(type="text")
     */
    private $description;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="moderatedFAQs")
     */
    private $moderator;

    /**
     * @ORM\ManyToMany(targetEntity=Question::class, mappedBy="belongingFAQs")
     * @ORM\JoinTable(name="questions_belongings")
     */
    private $relatedQuestions;

    public function __construct()
    {
        $this->relatedQuestions = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getModerator(): ?User
    {
        return $this->moderator;
    }

    public function setModerator(?User $moderator): self
    {
        if($moderator == null || in_array('ROLE_ADMIN', $moderator->getRoles(), true))
        {
            $this->moderator = $moderator;
        }
        return $this;
    }

    /**
     * @return Collection|Question[]
     */
    public function getRelatedQuestions(): Collection
    {
        return $this->relatedQuestions;
    }

    public function addRelatedQuestion(Question $relatedQuestion): self
    {
        if (!$this->relatedQuestions->contains($relatedQuestion)) {
            $this->relatedQuestions[] = $relatedQuestion;
            $relatedQuestion->addBelongingFAQ($this);
        }

        return $this;
    }

    public function removeRelatedQuestion(Question $relatedQuestion): self
    {
        if ($this->relatedQuestions->removeElement($relatedQuestion)) {
            $relatedQuestion->removeBelongingFAQ($this);
        }

        return $this;
    }
}
