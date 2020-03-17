<?php

namespace AppBundle\v2\Registration\Assembler;

    use AppBundle\Entity\CasRec;
    use AppBundle\Service\DataNormaliser;
use AppBundle\v2\Registration\DTO\LayDeputyshipDto;

class SiriusToLayDeputyshipDtoAssembler implements LayDeputyshipDtoAssemblerInterface
{
    /** @var DataNormaliser */
    private $normaliser;

    /**
     * @param DataNormaliser $normaliser
     */
    public function __construct(DataNormaliser $normaliser)
    {
        $this->normaliser = $normaliser;
    }

    /**
     * @param array $data
     * @return LayDeputyshipDto
     */
    public function assembleFromArray(array $data)
    {
        if (!$this->canAssemble($data)) {
            throw new \InvalidArgumentException('Cannot assemble LayDeputyshipDto: Missing expected data');
        }

        try {
            return $this->buildDto($data);
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }

    /**
     * @param array $data
     * @return LayDeputyshipDto
     */
    private function buildDto(array $data): LayDeputyshipDto
    {
        return
            (new LayDeputyshipDto())
                ->setCaseNumber($this->normaliser->normaliseCaseNumber($data['Case']))
                ->setClientSurname($this->normaliser->normaliseSurname($data['Surname']))
                ->setDeputyNumber($this->normaliser->normaliseDeputyNo($data['Deputy No']))
                ->setDeputySurname($this->normaliser->normaliseSurname($data['Dep Surname']))
                ->setDeputyPostcode($this->normaliser->normalisePostCode($data['Dep Postcode']))
                ->setTypeOfReport($data['Typeofrep'])
                ->setCorref($this->determineCorref($data['Typeofrep']))
                ->setIsNdrEnabled(false)
                ->setSource(CasRec::SIRIUS_SOURCE);
    }

    /**
     * @param array $data
     * @return bool
     */
    private function canAssemble(array $data)
    {
        return
            array_key_exists('Case', $data) &&
            array_key_exists('Surname', $data) &&
            array_key_exists('Deputy No', $data) &&
            array_key_exists('Dep Surname', $data) &&
            array_key_exists('Dep Postcode', $data) &&
            array_key_exists('Typeofrep', $data);
    }

    /**
     * @param string $reportType
     * @return string
     */
    private function determineCorref(string $reportType): string
    {
        switch ($reportType) {
            case 'OPG102':
                return 'L2';
            case 'OPG103':
                return 'L3';
            default:
                throw new \InvalidArgumentException('Cannot assemble LayDeputyshipDto: Unexpected report type');
        }
    }
}
