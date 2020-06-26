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
    private $checklistSyncService;

    /** @var RestClient */
    private $restClient;

    /** @var Serializer  */
    private $serializer;

    /** @var ParameterStoreService */
    private $parameterStore;

    /**
     * ChecklistSyncCommand constructor.
     * @param ChecklistSyncService $checklistSyncService
     * @param RestClient $restClient
     * @param Serializer $serializer
     * @param ParameterStoreService $parameterStore
     * @param null $name
     */
    public function __construct(
        ChecklistSyncService $checklistSyncService,
        RestClient $restClient,
        SerializerInterface $serializer,
        ParameterStoreService $parameterStore,
        $name = null
    )
    {
        $this->checklistSyncService = $checklistSyncService;
        $this->restClient = $restClient;
        $this->serializer = $serializer;
        $this->parameterStore = $parameterStore;

        parent::__construct($name);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$this->isFeatureEnabled()) {
            $output->writeln('Feature disabled, sleeping');
            return 0;
        }

        /** @var QueuedChecklistData[] $checklists */
        $checklists = $this->getQueuedChecklistsData();

        $output->writeln(sprintf('%d checklists to upload', count($checklists)));

        foreach ($checklists as $checklist) {
            $this->checklistSyncService->sync($checklist);
        }

        return 0;
    }

    private function isFeatureEnabled(): bool
    {
        return $this->parameterStore->getFeatureFlag(ParameterStoreService::FLAG_CHECKLIST_SYNC) === '1';
    }

    private function getQueuedChecklistsData(): array
    {
        $queuedDocumentData = $this->restClient->apiCall(
            'get',
            'checklist/queued',
            ['row_limit' => $this->getSyncRowLimit()],
            'array',
            [],
            false
        );

        return $this->serializer->deserialize($queuedDocumentData, 'AppBundle\Model\Sirius\QueuedChecklistData[]', 'json');
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
