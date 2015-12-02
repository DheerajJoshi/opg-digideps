<?php
namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use AppBundle\Entity\Role;

class AddUserType extends AbstractType
{
    /**
     * @var array 
     */
    private $roleChoices = [];
    
    /**
     * @var string
     */
    private $roleIdEmptyValue;

    /**
     * @var boolean
     */
    private $roleIdDisabled;
    
    /**
     * @param array $options keys: array roles, roleIdEmptyValue
     */
    public function __construct(array $options)
    {
        foreach ($options['roles'] as $role) {
            $this->roleChoices[$role->getId()] = $role->getName();
        }
        $this->roleIdEmptyValue = $options['roleIdEmptyValue'];
        $this->roleIdDisabled = empty($options['roleIdDisabled']) ? false : true;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder ->add('email', 'text')
                 ->add('firstname', 'text')
                 ->add('lastname', 'text')
                 ->add('roleId', 'choice', array(
                    'choices' => $this->roleChoices,
                    'empty_value' => $this->roleIdEmptyValue,
                    'disabled'    => $this->roleIdDisabled
                  ))
                 ->add('save', 'submit');
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults( [
              'translation_domain' => 'admin',
              'validation_groups' => ['admin_add_user'],
        ]);
    }
    
    public function getName()
    {
        return 'admin';
    }
}
