<?php

namespace MartenaSoft\PageBundle\Form;


use MartenaSoft\CommonLibrary\Dictionary\DictionaryCommonStatus;
use MartenaSoft\CommonLibrary\Dictionary\DictionaryImage;
use MartenaSoft\CommonLibrary\Dictionary\DictionaryPage;
use MartenaSoft\PageBundle\Entity\Page;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageType extends AbstractType
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
                'label' => 'Name'
            ]);

        if (!$isMainPage) {
            $builder
                ->add('isOnTopMenu')
                ->add('isOnLeftMenu')
                ->add('isOnFooterMenu')

                ->add('type', ChoiceType::class, [
                    'choices' => array_flip(DictionaryPage::TYPES),
                ]);
        }

        $builder

            ->add('preview', TextareaType::class, [
                'attr' => ['class' => 'tinymce'],
                'required' => false
            ])
            ->add('body', TextareaType::class, [
                'attr' => ['class' => 'tinymce'],
                'required' => false
            ])
        ;
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
