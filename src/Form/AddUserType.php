<?php

namespace App\Form;

use App\Entity\Skill;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AddUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $skill = $options['skill'];
        $this->setSkill($skill);
        $builder
            ->add('user', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'choice_filter' => ChoiceList::filter(
                    $this,
                    function($user){
                        if($this->skill == null)
                            return true;
                        return !$this->skill->getUsersThatHasSkill()->contains($user);
                    },
                    'user'),
                    'invalid_message' => 'The requested user doesn\'t exist or is already attributed to the skill.'
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'skill' => null
        ]);
    }

    private function setSkill(?Skill $skill)
    {
        $this->skill = $skill;
    }
}
