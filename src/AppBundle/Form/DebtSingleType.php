<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use AppBundle\Entity\Debt;

class DebtSingleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('debtTypeId', 'hidden')
            ->add('amount', 'number', [
                'precision' => 2,
                'grouping' => true,
                'error_bubbling' => false,
                'invalid_message' => 'debt.amount.notNumeric',
            ]);

        // add textarea to debts that has more details flag set to true
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $debt = $event->getData();
            /* @var $debt Debt */
            $form = $event->getForm();

            if ($debt->getHasMoreDetails()) {
                $form->add('moreDetails', 'textarea', [
                    'required' => true
                ]);
            }
        });
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'AppBundle\Entity\Debt',
            'validation_groups' => function (FormInterface $form) {

                $data = $form->getData();
                /* @var $data \AppBundle\Entity\Debt */
                $validationGroups = ['debts'];

                if ($data->getAmount() && $data->getHasMoreDetails()) {
                    $validationGroups[] = 'debt-more-details';
                }

                return $validationGroups;
            },
            'translation_domain' => 'report-debts',
        ]);
    }

    public function getName()
    {
        return 'debt_single';
    }
}
