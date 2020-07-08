<?php declare(strict_types=1);

namespace AppBundle\Command;

use AppBundle\Model\Sirius\QueuedChecklistData;
use AppBundle\Service\ChecklistSyncService;
use AppBundle\Service\Client\RestClient;
use AppBundle\Service\ParameterStoreService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

class ChecklistSyncCommand extends Command
{
    /** @var string */
    const FALLBACK_ROW_LIMITS = '100';

    /** @var string */
    protected static $defaultName = 'digideps:checklist-sync';

    /** @var ChecklistSyncService */
    private $syncService;

    /** @var RestClient */
    private $restClient;

    /** @var Serializer  */
    private $serializer;

    /** @var ParameterStoreService */
    private $parameterStore;

    /**
     * ChecklistSyncCommand constructor.
     * @param ChecklistSyncService $syncService
     * @param RestClient $restClient
     * @param Serializer $serializer
     * @param ParameterStoreService $parameterStore
     * @param null $name
     */
    public function __construct(
        ChecklistSyncService $syncService,
        RestClient $restClient,
        SerializerInterface $serializer,
        ParameterStoreService $parameterStore,
        $name = null
    )
    {
        $this->syncService = $syncService;
        $this->restClient = $restClient;
        $this->serializer = $serializer;
        $this->parameterStore = $parameterStore;

        parent::__construct($name);
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->isFeatureEnabled()) {
            $output->writeln('Feature disabled, sleeping');
            return 0;
        }

        /** @var array $checklists */
        $checklists = $this->getQueuedChecklistsData();

        $output->writeln(sprintf('%d checklists to upload', count($checklists)));

        foreach ($checklists as $checklist) {
            $this->syncService->sync($checklist);
        }

        if (count($this->syncService->getSyncErrorSubmissionIds()) > 0) {
            $this->syncService->setChecklistsToPermanentError();
            $this->syncService->setSyncErrorSubmissionIds([]);
        }

        if ($this->syncService->getChecklistsNotSyncedCount() > 0) {
            $output->writeln(sprintf('%d checklists failed to sync', $this->syncService->getChecklistsNotSyncedCount()));
            $this->syncService->setChecklistsNotSyncedCount(0);
        }

        return 0;
    }

    /**
     * @return bool
     */
    private function isFeatureEnabled(): bool
    {
        return $this->parameterStore->getFeatureFlag(ParameterStoreService::FLAG_CHECKLIST_SYNC) === '1';
    }

    /**
     * @return QueuedChecklistData[]
     */
    private function getQueuedChecklistsData(): array
    {
        $reports = $this->restClient->apiCall(
            'get',
            'checklist/queued',
            ['row_limit' => $this->getSyncRowLimit()],
            'Report\Report[]',
            [],
            false
        );

        $queuedChecklists = [];
        foreach ($reports as $report) {
            $queuedChecklists[] = (new QueuedChecklistData())->setReport($report);
        }

        return $queuedChecklists;
    }

    /**
     * @return string
     */
    private function getSyncRowLimit(): string
    {
        $limit = $this->parameterStore->getParameter(ParameterStoreService::PARAMETER_CHECKLIST_SYNC_ROW_LIMIT);
        return $limit ? $limit : self::FALLBACK_ROW_LIMITS;
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Uploads queued checklists to Sirius and reports back the success');
    }
}
