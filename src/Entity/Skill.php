<?php

namespace App\Entity;

use App\Repository\SkillRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Table(name="skills")
 * @UniqueEntity(fields="name", message="This skill already exists.")
 * @ORM\Entity(repositoryClass=SkillRepository::class)
 */
class Skill
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @Assert\NotBlank(message = "Please enter a valid skill name.")
     * @ORM\Column(type="string", length=45)
     */
    private $name;

    /**
     * @ORM\ManyToMany(targetEntity=User::class, mappedBy="userSkills")
     * @ORM\JoinTable(name="shared_skills")
     */
    private $usersThatHasSkill;

    public function __construct()
    {
        $this->usersThatHasSkill = new ArrayCollection();
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

    /**
     * @return Collection|User[]
     */
    public function getUsersThatHasSkill(): Collection
    {
        return $this->usersThatHasSkill;
    }

    public function addUsersThatHasSkill(User $usersThatHasSkill): self
    {
        if (!$this->usersThatHasSkill->contains($usersThatHasSkill)) {
            $this->usersThatHasSkill[] = $usersThatHasSkill;
            $usersThatHasSkill->addUserSkill($this);
        }

        return $this;
    }

    public function removeUsersThatHasSkill(User $usersThatHasSkill): self
    {
        if ($this->usersThatHasSkill->removeElement($usersThatHasSkill)) {
            $usersThatHasSkill->removeUserSkill($this);
        }

        return $this;
    }
}
