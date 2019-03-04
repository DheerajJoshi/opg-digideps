<?php

namespace AppBundle\Form\Report;


use AppBundle\Entity\Report\ProfDeputyManagementCost;
use AppBundle\Entity\Report\Report;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type as FormTypes;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Valid;

class ProfDeputyManagementCostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('profDeputyManagementCosts', FormTypes\NumberType::class, [
                'scale' => 2,
                'grouping' => true,
                'error_bubbling' => false,
            ])
            ->add('save', FormTypes\SubmitType::class, ['label' => 'save.label']);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'validation_groups' => ['prof-deputy-management-costs'],
            'constraints' => new Valid(),
            'translation_domain' => 'report-prof-deputy-costs-estimate',
        ]);
    }

    public function getBlockPrefix()
    {
        return 'deputy_management_costs';
    }
}