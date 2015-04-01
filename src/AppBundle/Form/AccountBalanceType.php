<?php
namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use AppBundle\Form\Type\SortCodeType;
use AppBundle\Form\Type\AccountNumberType;

class AccountBalanceType extends AbstractType
{
     public function buildForm(FormBuilderInterface $builder, array $options)
     {
         $builder 
                 ->add('id', 'hidden')
                 ->add('closingDate', 'date',[ 'widget' => 'text',
                                                 'input' => 'datetime',
                                                 'format' => 'dd-MM-yyyy',
                                                 'invalid_message' => 'Value or character is not valid'
                                              ])
                 ->add('closingBalance', 'number', [ 'grouping' => true, 'precision' => 2 ])
                 ->add('save', 'submit')
                ;
     }
     
     public function setDefaultOptions(OptionsResolverInterface $resolver)
     {
         $resolver->setDefaults( [
             'data_class' => 'AppBundle\Entity\Account',
             'validation_groups' => ['balance'],
             'translation_domain' => 'report-account-balance',
             'csrf_protection' => false
        ]);
     }
     
     public function getName()
     {
         return 'accountBalance';
     }
     
}