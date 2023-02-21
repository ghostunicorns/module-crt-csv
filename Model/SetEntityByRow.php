<?php
/*
  * Copyright © Ghost Unicorns snc. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtCsv\Model;

use Exception;
use GhostUnicorns\CrtEntity\Model\EntityRepository;
use GhostUnicorns\CrtEntity\Model\GetOrCreateEntity;
use Magento\Framework\Serialize\SerializerInterface;

class SetEntityByRow
{
    /**
     * @var GetEntityIdentifier
     */
    private $getEntityIdentifier;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var GetOrCreateEntity
     */
    private $getOrCreateEntity;

    /**
     * @var EntityRepository
     */
    private $entityRepository;

    /**
     * @param GetEntityIdentifier $getEntityIdentifier
     * @param SerializerInterface $serializer
     * @param GetOrCreateEntity $getOrCreateEntity
     * @param EntityRepository $entityRepository
     */
    public function __construct(
        GetEntityIdentifier $getEntityIdentifier,
        SerializerInterface $serializer,
        GetOrCreateEntity $getOrCreateEntity,
        EntityRepository $entityRepository
    ) {
        $this->getEntityIdentifier = $getEntityIdentifier;
        $this->serializer = $serializer;
        $this->getOrCreateEntity = $getOrCreateEntity;
        $this->entityRepository = $entityRepository;
    }

    /**
     * @param array $data
     * @param array $identifiers
     * @param int $activityId
     * @param string $collectorType
     * @return void
     * @throws Exception
     */
    public function execute(array $data, array $identifiers, int $activityId, string $collectorType): void
    {
        $identifier = $this->getEntityIdentifier->execute($identifiers, $data);

        $dataOriginal = $this->serializer->serialize($data);

        $entity = $this->getOrCreateEntity->execute($activityId, $identifier, $collectorType);

        $entity->setDataOriginal($dataOriginal);

        $this->entityRepository->save($entity);
    }
}
