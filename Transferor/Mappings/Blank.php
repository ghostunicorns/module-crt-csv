<?php
/*
  * Copyright © Ghost Unicorns snc. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtCsv\Transferor\Mappings;

class Blank implements MappingTypeInterface
{
    /**
     * @var string
     */
    private $head;

    /**
     * @param string $head
     */
    public function __construct(
        string $head
    ) {
        $this->head = $head;
    }

    /**
     * @param array $data
     * @return string
     */
    public function execute(array $data): string
    {
        return '';
    }

    /**
     * @return string
     */
    public function getHead(): string
    {
        return $this->head;
    }
}
