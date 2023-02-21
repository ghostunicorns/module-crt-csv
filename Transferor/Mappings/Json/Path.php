<?php
/*
  * Copyright © Ghost Unicorns snc. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtCsv\Transferor\Mappings\Json;

use GhostUnicorns\CrtBase\Exception\CrtException;
use GhostUnicorns\CrtCsv\Model\DotConvention;
use GhostUnicorns\CrtCsv\Transferor\Mappings\MappingTypeInterface;

class Path implements MappingTypeInterface
{
    /**
     * @var string
     */
    private $head;

    /**
     * @var string
     */
    private $path;

    /**
     * @var DotConvention
     */
    private $dotConvention;

    /**
     * @param string $head
     * @param string $path
     * @param DotConvention $dotConvention
     */
    public function __construct(
        string $head,
        string $path,
        DotConvention $dotConvention
    ) {
        $this->head = $head;
        $this->path = $path;
        $this->dotConvention = $dotConvention;
    }

    /**
     * @param array $data
     * @return string
     * @throws CrtException
     */
    public function execute(array $data): string
    {
        return (string)$this->dotConvention->getValue($data, $this->path);
    }

    /**
     * @return string
     */
    public function getHead(): string
    {
        return $this->head;
    }
}
