<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;

class SimpleRunType extends AbstractType
{
    const NEW_LINE = "\r\n";

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter) ($options)
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {

        $builder
            ->add(
                'urls', TextareaType::class, [
                    'label' => false,
                ]
            );

        $builder
            ->add(
                'submit', SubmitType::class, [
                    'label' => 'form.label.submit'
                ]
            )//
        ;

        $builder->get('urls')
            ->addModelTransformer(new CallbackTransformer(
                function ($urlsAsArray = []) {
                    if (is_string($urlsAsArray)) {
                        return $urlsAsArray;
                    }
                    // transform into string
                    return !$urlsAsArray ? '' : implode(self::NEW_LINE, $urlsAsArray);
                },
                function ($urlsAsString) {
                    // transform into array
                    return array_values(explode(self::NEW_LINE, $urlsAsString));
                }
            ));
    }
}
