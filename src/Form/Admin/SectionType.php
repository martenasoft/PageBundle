<?php

namespace MartenaSoft\PageBundle\Form\Admin;

use MartenaSoft\CommonLibrary\Dictionary\DictionaryPage;
use MartenaSoft\PageBundle\Entity\Page;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class SectionType extends AbstractType
{
    public function __construct(
        private ParameterBagInterface $parameter,
        private TranslatorInterface $translator
    )
    {

    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $isMainPage = $options['isMainPage'];
        $builder
            ->add('name', TextType::class, [
                'required' => false,
                'label' => 'Name',
            ])
            ->add('title')
            ->add('isOnTopMenu')
            ->add('isOnLeftMenu')
            ->add('isOnFooterMenu')

            ->add('preview', TextareaType::class, [
                'attr' => ['class' => 'tinymce'],
                'required' => false,
            ])
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
