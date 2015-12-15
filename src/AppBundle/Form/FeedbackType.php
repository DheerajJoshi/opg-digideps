<?php
namespace AppBundle\Form;

use AppBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints as Constraints;

class FeedbackType extends AbstractType
{
    use Traits\HasTranslatorTrait;
    use Traits\HasSecurityContextTrait;

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $satisfactionLevelChoices = array_filter(explode("\n", $this->translate('satisfactionLevelsChoices', [], 'feedback')));
        $helpChoices = array_filter(explode("\n", $this->translate('helpChoices', [], 'feedback')));

        $builder->add('difficulty', 'textarea')
                ->add('ideas', 'textarea')
                ->add('email', 'email', [
                    'constraints' => [
                       new Constraints\Email(['message' => 'login.email.inValid'])
                    ],
                    'data' => $this->getLoggedUserEmail(),
                ])
                ->add('satisfactionLevel', 'choice', array(
                    'choices' => array_combine($satisfactionLevelChoices, $satisfactionLevelChoices),
                    'expanded' => true,
                    'multiple' => false
                  ))
                  ->add('help', 'choice', array(
                     'choices' => array_combine($helpChoices, $helpChoices),
                     'expanded' => true,
                     'multiple' => false
                   ))
                   ->add('save', 'submit');
    }
    
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults( [
              'translation_domain' => 'feedback'
        ]);
    }
    
    public function getName()
    {
        return 'feedback';
    }
}
