<?php

namespace App\Form;

use App\Entity\FAQ;
use App\Entity\Question;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssignQuestionFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $faq = $options['faq'];
        $this->setFAQ($faq);
        $builder
            ->add('questions', EntityType::class, [
                'class' => Question::class,
                'choice_label' => 'title',
                    'invalid_message' => 'The requested question doesn\'t exist or is already attributed to the faq.',
                    'multiple' => true,
                    'expanded' => true
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'faq' => null
        ]);
    }

    private function setFAQ(?FAQ $faq)
    {
        $this->faq = $faq;
    }
}
