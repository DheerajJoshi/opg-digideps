<?php
namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use AppBundle\Entity\AccountTransaction;

class AccountTransactionSingleType extends AbstractType
{
     public function buildForm(FormBuilderInterface $builder, array $options)
     {
         $builder 
                 ->add('id', 'hidden')
                 ->add('type', 'hidden')
                 ->add('amount', 'number', ['error_bubbling' => false, 'grouping' => true, 'precision' => 2 ]);
         
         $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $accountTransaction = $event->getData(); /* @var $accountTransaction AccountTransaction */
            $form = $event->getForm();
            
            if ($accountTransaction->hasMoreDetails()) {
                $form->add('moreDetails', 'textarea');
            }
        });
     }
     
     public function setDefaultOptions(OptionsResolverInterface $resolver)
     {
         $resolver->setDefaults( [
             'data_class' => 'AppBundle\Entity\AccountTransaction',
             'validation_groups' => ['transactions']
        ]);
     }
     
     public function getName()
     {
         return 'transaction_single';
     }
     
}