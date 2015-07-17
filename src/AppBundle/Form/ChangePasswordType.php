<?php
namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Assert;
use AppBundle\Validator\Constraints\DUserPassword;
use Symfony\Component\Validator\ExecutionContextInterface;


class ChangePasswordType extends AbstractType
{
    private $request;
    
    /**
     * @param type $request
     */
    public function __construct($request) 
    {
        $this->request = $request;
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options)
    {   
        $builder->add('current_password','password', ['constraints' => new DUserPassword([ 'message' => 'Please enter your correct current password', 
                                                                                                         'groups' => ['user_details_full']])
                                                     ])
                
                ->add('plain_password', 'repeated', [
                        'type' => 'password',
                        'invalid_message' => 'Password does not match',
                        'constraints' => [
                            new Assert\Length(['min'=> 8, 'max'=>50, 'minMessage'=>"user.password.minLength", 'maxMessage' =>"user.password.maxLength", 'groups' => ['user_details_full']]),
                            new Assert\Regex(['pattern' => "/[a-z]/", 'message' => 'user.password.noLowerCaseChars', 'groups' => 'user_details_full' ]),
                            new Assert\Regex(['pattern' => "/[A-Z]/", 'message' => 'user.password.noUpperCaseChars', 'groups' => 'user_details_full' ]),
                            new Assert\Regex(['pattern' => "/[0-9]/", 'message' => 'user.password.noNumber', 'groups' => 'user_details_full' ]),
                        ]
                    ]);
    }
    
    public function checkNewPasswordIsNotBlank($data,ExecutionContextInterface $context)
    {
        if(!empty($data['current_password']) && empty($data['plain_password'])){
             $context->addViolationAt('children[password].data.password','user.password.notBlank');
        }
    }
    
    public function getParent() 
    {
        return 'form';
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults( [
              'translation_domain' => 'user-details',
              'constraints' => [ new Assert\Callback([ 'methods' => [ [$this,'checkNewPasswordIsNotBlank']], 'groups' => [ 'user_details_full']]) ],
              'error_mapping' => [ 'children[password].data.password' => 'plain_password' ]
        ]);
    }
    
    public function getName()
    {
        return 'change_password';
    }
}
