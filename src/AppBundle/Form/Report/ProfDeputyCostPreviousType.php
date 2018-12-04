<?php

namespace AppBundle\Form\Report;

use AppBundle\Entity\Report\MoneyShortCategory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as FormTypes;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfDeputyCostPreviousType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('save', FormTypes\SubmitType::class, ['label' => 'save.label']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
             'translation_domain' => 'report-prof-deputy-costs',
        ]);
    }

    public function getBlockPrefix()
    {
        return 'deputy_costs_previous';
    }
}
