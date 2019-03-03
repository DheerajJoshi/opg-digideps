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
            ->add('profDeputyManagementCosts', FormTypes\CollectionType::class, [
                'entry_type' => ProfDeputyManagementCostSingleType::class,
                'constraints' => new Valid(),
            ])
            ->add('save', FormTypes\SubmitType::class);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'AppBundle\Entity\Report\Report',
            'validation_groups' => ['prof-deputy-management-costs'],
            'constraints' => new Valid(),
            'translation_domain' => 'report-prof-deputy-management-costs',
        ]);
    }
}