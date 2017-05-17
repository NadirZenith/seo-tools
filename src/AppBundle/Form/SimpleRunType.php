<?php

namespace AppBundle\Form;

use AppBundle\Entity\FieldsGroup;
use Sonata\AdminBundle\Form\Type\CollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SimpleRunType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add(
                'urls', TextareaType::class, [
                    'data' => $options['test_data'],
                    'label' => false,
                    'attr' => [
                        //                    'style' => 'width: 95%; height: 200px; '
                    ]
                ]
            );

        $builder
            ->add(
                'submit', SubmitType::class, array(
                    'label' => 'form.label.submit'
                )
            )//
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            array(//            'data_class'     => FieldsGroup::class,
            )
        );

        $resolver->setRequired('test_data');
    }
}
