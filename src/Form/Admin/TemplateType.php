<?php

namespace MartenaSoft\PageBundle\Form\Admin;

use MartenaSoft\CommonLibrary\Dictionary\DictionaryTemplates;
use MartenaSoft\CommonLibrary\Entity\Interfaces\TemplateInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class TemplateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('template', ChoiceType::class, [
                'choices' => $options['templates'],
                'expanded' => true,
                'multiple' => false,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TemplateInterface::class,
            'templates' => DictionaryTemplates::CHOICE,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'template_subform';
    }
}