<?php

namespace AppBundle\Form\Report;

use AppBundle\Entity\Report\BankAccount;
use AppBundle\Entity\Report\MoneyTransaction;
use AppBundle\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class MoneyTransactionType extends AbstractType
{
    /**
     * @var string
     */
    private $userRole;

    /**
     * @var  string
     */
    private $clientFirstName;

    /**
     * @var integer
     */
    private $step;

    /**
     * @var string in/out
     */
    private $type;

    /**
     * @var string
     */
    private $selectedCategory;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    private function getCategories()
    {
        $ret = [];

        foreach (MoneyTransaction::$categories as $cat) {
            $categoryId = $cat[0];
            $type = $cat[3];
            // filter by user roles (if specified), matching with `$this->userRole` passed as an option
            $allowedRoles = isset($cat[4]) ? $cat[4] : null;
            $isCategoryAllowedForThisRole = $allowedRoles === null || in_array($this->userRole, $allowedRoles);
            // filter by
            if ($type == $this->type && $isCategoryAllowedForThisRole) {
                $ret[$categoryId] = null;
            }
        }

        return $ret;
    }

    /**
     * @return bool
     */
    private function isDescriptionMandatory()
    {
        foreach (MoneyTransaction::$categories as $cat) {
            list($categoryId, $hasDetails, $groupId, $type) = $cat;
            if ($categoryId == $this->selectedCategory) {
                return $hasDetails;
            }
        }
    }

    private function translate($key)
    {
        return $this->translator->trans($key, ['%client%' => $this->clientFirstName], 'report-money-transaction');
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $this->userRole = $options['userRole'];
        $this->step = (int)$options['step'];
        $this->type = $options['type'];
        $this->selectedCategory = $options['selectedCategory'];
        $this->translator = $options['translator'];
        $this->clientFirstName = $options['clientFirstName'];

        $builder->add('id', 'hidden');

        if ($this->step === 1) {
            $builder->add('category', 'choice', [
                'choices'  => $this->getCategories(),
                'expanded' => true,
            ]);
        }

        if ($this->step === 2) {
            $builder->add('description', 'textarea', [
                'required' => $this->isDescriptionMandatory(),
            ]);

            $builder->add('amount', 'number', [
                'precision'       => 2,
                'grouping'        => true,
                'error_bubbling'  => false, // keep (and show) the error (Default behaviour). if true, error is lost
                'invalid_message' => 'moneyTransaction.form.amount.type',
            ]);

            $reportType = $options['report']->getType();

            if (!empty($options['report']->getBankAccountOptions()) && (in_array($reportType, ['102', '102-4']))) {
                $builder->add('bankAccountId', 'choice', [
                    'choices' => $options['report']->getBankAccountOptions(),
                    'empty_value' => 'Please select',
                    'label' => 'form.bankAccount.money' . ucfirst($this->type) . '.label'
                ]);
            }
        }

        $builder->add('save', 'submit');
    }

    public function getName()
    {
        return 'account';
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'selectedCategory'          => null,
            'translation_domain'        => 'report-money-transaction',
            'choice_translation_domain' => 'report-money-transaction',
            'validation_groups'         => function (FormInterface $form) {
                $validationGroups = [];
                if ($this->step === 1) {
                    $validationGroups[] = 'transaction-category';
                }
                if ($this->step === 2) {
                    $validationGroups[] = 'transaction-amount';
                    if ($this->isDescriptionMandatory()) {
                        $validationGroups[] = 'transaction-description';
                    }
                }

                return $validationGroups;
            },
        ])
            ->setRequired(['user', 'report', 'step', 'type', 'translator', 'clientFirstName', 'userRole'])
            ->setAllowedTypes('translator', TranslatorInterface::class);
    }
}
