<?php

namespace AppBundle\EventListener;

use AppBundle\Entity as EntityDir;
use Doctrine\ORM\Event\LifecycleEventArgs;

class DoctrineListener
{
    public function prePersist(LifecycleEventArgs $args)
    {
        $entity = $args->getEntity();
        $entityManager = $args->getEntityManager();

        // add empty transactions to report at creation time
        if ($entity instanceof EntityDir\Report && !$entity->getId()) {
            $entityManager->getRepository('AppBundle\Entity\Report')->addTransactionsToReportIfMissing($entity);
        }

        // add empty debts to report at creation time
        if ($entity instanceof EntityDir\Report && !$entity->getId()) {
            $entityManager->getRepository('AppBundle\Entity\Report')->addDebtsToReportIfMissing($entity);
        }

        // create ODR + debts when client gets created
        if ($entity instanceof EntityDir\Odr\Odr && !$entity->getId()) {
            $entityManager->getRepository('AppBundle\Entity\Odr\Odr')->addDebtsToReportIfMissing($entity);
        }
    }
}
