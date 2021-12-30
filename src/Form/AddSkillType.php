<?php

namespace App\Form;

use App\Entity\Skill;
use App\Entity\User;
use App\Repository\SkillRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Form\ChoiceList\ChoiceList;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddSkillType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $user = $options['user'];
        $this->setUser($user);
        $builder
            ->add('skill', EntityType::class, [
                'class' => Skill::class,
                'choice_label' => 'name',
                'choice_filter' => ChoiceList::filter(
                    $this,
                    function ($skill){
                        if($this->user == null)
                            return true;
                        return !$this->user->getUserSkills()->contains($skill);
                    },
                    'skill'),
                'invalid_message' => 'The requested skill doesn\'t exist or is already attributed to the user.'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'user' => null
        ]);
    }

    private function setUser(?User $user)
    {
        $this->user = $user;
    }
}
