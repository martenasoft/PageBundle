<?php

namespace MartenaSoft\PageBundle\Form\Admin;


use MartenaSoft\PageBundle\Entity\Page;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class MainPageType extends AbstractType
{
    public function __construct(
        private ParameterBagInterface $parameter,
        private TranslatorInterface $translator
    )
    {

    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'required' => false,
                'label' => 'Name',
            ])
            ->add('template',TemplateType::class)

            ->add('body', TextareaType::class, [
                'attr' => ['class' => 'tinymce'],
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Page::class,
            'languages' => [],
            'isMainPage' => false,
        ]);
    }
}
