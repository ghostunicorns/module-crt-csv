<?php
/*
  * Copyright © Ghost Unicorns snc. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtCsv\Transferor;

use GhostUnicorns\CrtBase\Api\CrtConfigInterface;
use GhostUnicorns\CrtBase\Api\TransferorInterface;
use GhostUnicorns\CrtBase\Exception\CrtException;
use GhostUnicorns\CrtEntity\Api\EntityRepositoryInterface;
use GhostUnicorns\CrtCsv\Transferor\Csv\GetHeadersFromMappings;
use GhostUnicorns\CrtCsv\Transferor\Mappings\MappingTypeInterface;
use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\File\Csv as File;
use Magento\Framework\Filesystem\DirectoryList;
use Magento\Framework\Filesystem\DriverInterface;
use Monolog\Logger;

class CsvTransferor implements TransferorInterface
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var string
     */
    private $fileName;

    /**
     * @var string
     */
    private $filePath;

    /**
     * @var MappingTypeInterface[]
     */
    private $mappings;

    /**
     * @var EntityRepositoryInterface
     */
    private $entityRepository;

    /**
     * @var File
     */
    private $csv;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var GetHeadersFromMappings
     */
    private $getHeadersFromMappings;

    /**
     * @var CrtConfigInterface
     */
    private $config;

    /**
     * @var bool
     */
    private $addActivityIdToPath;

    /**
     * @var DriverInterface
     */
    private $driver;

    /**
     * @param Logger $logger
     * @param string $fileName
     * @param string $filePath
     * @param array $mappings
     * @param EntityRepositoryInterface $entityRepository
     * @param File $csv
     * @param DirectoryList $directoryList
     * @param GetHeadersFromMappings $getHeadersFromMappings
     * @param CrtConfigInterface $config
     * @param DriverInterface $driver
     * @param bool $addActivityIdToPath
     * @throws CrtException
     */
    public function __construct(
        Logger $logger,
        string $fileName,
        string $filePath,
        array $mappings,
        EntityRepositoryInterface $entityRepository,
        File $csv,
        DirectoryList $directoryList,
        GetHeadersFromMappings $getHeadersFromMappings,
        CrtConfigInterface $config,
        DriverInterface $driver,
        bool $addActivityIdToPath = false
    ) {
        $this->logger = $logger;
        $this->fileName = $fileName;
        $this->filePath = $filePath;
        $this->mappings = $mappings;
        foreach ($mappings as $mapping) {
            if (!$mapping instanceof MappingTypeInterface) {
                throw new CrtException(__("Invalid type for mappings"));
            }
        }

        $this->entityRepository = $entityRepository;
        $this->csv = $csv;
        $this->directoryList = $directoryList;
        $this->getHeadersFromMappings = $getHeadersFromMappings;
        $this->config = $config;
        $this->addActivityIdToPath = $addActivityIdToPath;
        $this->driver = $driver;
    }

    /**
     * @param int $activityId
     * @param string $transferorType
     * @throws CrtException
     */
    public function execute(int $activityId, string $transferorType): void
    {
        try {
            $file = $this->getFileNameWithPath($activityId);
            $this->emptyFileAndWriteHeaders($file);
        } catch (FileSystemException $e) {
            throw new CrtException(__(
                'activityId:%1 ~ Transferor ~ transferorType:%2 ~ ERROR ~ error:%3',
                $activityId,
                $transferorType,
                $e->getMessage()
            ));
        }

        $allActivityEntities = $this->entityRepository->getAllDataRefinedByActivityIdGroupedByIdentifier($activityId);
        foreach ($allActivityEntities as $entityIdentifier => $entities) {
            $this->logger->info(__(
                'activityId:%1 ~ Transferor ~ transferorType:%2 ~ entityIdentifier:%3 ~ START',
                $activityId,
                $transferorType,
                $entityIdentifier
            ));

            try {
                $this->appendChildrenFirst($file, $entities);
            } catch (FileSystemException $e) {
                $this->logger->error(__(
                    'activityId:%1 ~ Transferor ~ transferorType:%2 ~ entityIdentifier:%3 ~ ERROR ~ error:%4',
                    $activityId,
                    $transferorType,
                    $entityIdentifier,
                    $e->getMessage()
                ));

                if (!$this->config->continueInCaseOfErrors()) {
                    throw new CrtException(__(
                        'activityId:%1 ~ Transferor ~ transferorType:%2 ~ entityIdentifier:%3 ~ END ~'.
                        ' Because of continueInCaseOfErrors = false',
                        $activityId,
                        $transferorType,
                        $entityIdentifier
                    ));
                }
            }

            $this->logger->info(__(
                'activityId:%1 ~ Transferor ~ transferorType:%2 ~ entityIdentifier:%3 ~ END',
                $activityId,
                $transferorType,
                $entityIdentifier
            ));
        }
    }

    /**
     * @param int $activityId
     * @return string
     * @throws FileSystemException
     */
    private function getFileNameWithPath(int $activityId): string
    {
        $fileName = $this->fileName . '_' . (string)$activityId . '_01.csv';
        $directory = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        $path = $directory . DIRECTORY_SEPARATOR . $this->filePath . DIRECTORY_SEPARATOR;
        if ($this->addActivityIdToPath) {
            $path .= $activityId . DIRECTORY_SEPARATOR;
            $this->driver->createDirectory($path, 0755);
        }
        $path .= $fileName;
        return $path;
    }

    /**
     * @param string $file
     * @throws FileSystemException
     */
    private function emptyFileAndWriteHeaders(string $file)
    {
        $headers = [$this->getHeadersFromMappings->execute($this->mappings)];
        $this->csv->appendData($file, $headers, 'w+');
    }

    /**
     * @param string $file
     * @param array $entities
     */
    /** @codingStandardsIgnoreStart */
    protected function appendChildrenFirst(string $file, array $entities)
    {
        //redy to extend
    }
    /** @codingStandardsIgnoreEnd  */

    /**
     * @param array $entities
     * @return array[]
     * @throw CrtException
     */
    protected function getData(array $entities): array
    {
        $data = [];
        foreach ($this->mappings as $mapping) {
            $data[] = $mapping->execute($entities);
        }
        return [$data];
    }

    /**
     * @param string $file
     * @param array $data
     * @throws FileSystemException
     */
    protected function appendToFile(string $file, array $data)
    {
        $this->csv->appendData($file, $data, 'a+');
    }
}
