<?php
/*
  * Copyright © Ghost Unicorns snc. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtCsv\Collector;

use Exception;
use GhostUnicorns\CrtBase\Api\CollectorInterface;
use GhostUnicorns\CrtBase\Api\CrtConfigInterface;
use GhostUnicorns\CrtBase\Exception\CrtException;
use GhostUnicorns\CrtCsv\Model\GetFileNameWithPath;
use GhostUnicorns\CrtCsv\Model\GetRowsFromCsvArray;
use GhostUnicorns\CrtCsv\Model\SetEntityByRow;
use Magento\Framework\Exception\FileSystemException;
use Monolog\Logger;
use Magento\Framework\File\Csv;

class CsvCollector implements CollectorInterface
{
    /**
     * @var int
     */
    protected $ok = 0;

    /**
     * @var int
     */
    protected $ko = 0;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CrtConfigInterface
     */
    private $config;

    /**
     * @var Csv
     */
    private $csv;

    /**
     * @var SetEntityByRow
     */
    private $setEntityByRow;

    /**
     * @var GetFileNameWithPath
     */
    private $getFileNameWithPath;

    /**
     * @var GetRowsFromCsvArray
     */
    private $getRowsFromCsvArray;

    /**
     * @var string
     */
    private $filePath;

    /**
     * @var string
     */
    private $fileName;

    /**
     * @var array
     */
    private $identifiers;

    /**
     * @param Logger $logger
     * @param CrtConfigInterface $config
     * @param Csv $csv
     * @param SetEntityByRow $setEntityByRow
     * @param GetFileNameWithPath $getFileNameWithPath
     * @param GetRowsFromCsvArray $getRowsFromCsvArray
     * @param string $filePath
     * @param string $fileName
     * @param string $identifier
     * @param array $identifiers
     */
    public function __construct(
        Logger $logger,
        CrtConfigInterface $config,
        Csv $csv,
        SetEntityByRow $setEntityByRow,
        GetFileNameWithPath $getFileNameWithPath,
        GetRowsFromCsvArray $getRowsFromCsvArray,
        string $filePath,
        string $fileName,
        string $identifier = '',
        array $identifiers = []
    ) {
        $this->logger = $logger;
        $this->config = $config;
        $this->csv = $csv;
        $this->setEntityByRow = $setEntityByRow;
        $this->getFileNameWithPath = $getFileNameWithPath;
        $this->getRowsFromCsvArray = $getRowsFromCsvArray;
        $this->fileName = $fileName;
        $this->filePath = $filePath;
        $this->identifiers = $identifiers;
        if ($identifier !== '') {
            array_unshift($this->identifiers, $identifier);
        }
    }

    /**
     * @param int $activityId
     * @param string $collectorType
     * @throws CrtException
     */
    public function execute(int $activityId, string $collectorType): void
    {
        $this->logger->info(__(
            'activityId:%1 ~ Collector ~ collectorType:%2 ~ START',
            $activityId,
            $collectorType
        ));

        try {
            $fileNameWithPath = $this->getFileNameWithPath->execute($this->filePath, $this->fileName);
        } catch (FileSystemException $e) {
            throw new CrtException(__(
                'activityId:%1 ~ Collector ~ collectorType:%2 ~ ERROR ~ error:%3',
                $activityId,
                $collectorType,
                $e->getMessage()
            ));
        }

        try {
            $data = $this->csv->getData($fileNameWithPath);
            $rows = $this->getRowsFromCsvArray->execute($data);
        } catch (Exception $e) {
            $this->logger->error(__(
                'activityId:%1 ~ Collector ~ collectorType:%2 ~ ERROR ~ error:%3',
                $activityId,
                $collectorType,
                $e->getMessage()
            ));
            return;
        }

        $this->ok = 0;
        $this->ko = 0;

        $this->setEntityByRows($rows, $activityId, $collectorType);

        $this->logger->info(__(
            'activityId:%1 ~ Collector ~ collectorType:%2 ~ okCount:%3 koCount:%4',
            $activityId,
            $collectorType,
            $this->ok,
            $this->ko
        ));
    }

    /**
     * @param array $rows
     * @param int $activityId
     * @param string $collectorType
     * @return void
     * @throws CrtException
     */
    private function setEntityByRows(array $rows, int $activityId, string $collectorType): void
    {
        foreach ($rows as $key => $row) {
            try {
                $this->setEntityByRow->execute($row, $this->identifiers, $activityId, $collectorType);
                $this->ok++;
            } catch (Exception $e) {
                $errorMessage = __(
                    'activityId:%1 ~ Collector ~ collectorType:%2 ~ KO ~ row:%3 ~ error:%4',
                    $activityId,
                    $collectorType,
                    $key + 1,
                    $e->getMessage()
                );
                if ($this->config->continueInCaseOfErrors()) {
                    $this->logger->error($errorMessage);
                    $this->ko++;
                } else {
                    throw new CrtException($errorMessage);
                }
            }
        }
    }
}
