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
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;

class XlsxCollector implements CollectorInterface
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
     * @var Xlsx
     */
    private Xlsx $xlsxReader;

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
     * @param Xlsx $xlsxReader
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
        Xlsx $xlsxReader,
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
        $this->xlsxReader = $xlsxReader;
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

        $this->ok = 0;
        $this->ko = 0;

        try {
            $this->xlsxReader->setIncludeCharts(false);
            $this->xlsxReader->setReadDataOnly(true);
            $this->xlsxReader->setReadEmptyCells(false);
            $data = $this->xlsxReader->load($fileNameWithPath);

            $sheets = $data->getAllSheets();

            foreach ($sheets as $sheet) {
                $sheetTitle = $sheet->getTitle();
                $sheetData = $sheet->toArray();
                $rows = $this->getRowsFromCsvArray->execute($sheetData);
                array_walk($rows, function (&$value, $key) use ($sheetTitle) {
                    $value['sheet_title'] = $sheetTitle;
                });
                $this->setEntityByRows($rows, $activityId, $collectorType);
            }

        } catch (Exception $e) {
            $this->logger->error(__(
                'activityId:%1 ~ Collector ~ collectorType:%2 ~ ERROR ~ error:%3',
                $activityId,
                $collectorType,
                $e->getMessage()
            ));
            return;
        }

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
