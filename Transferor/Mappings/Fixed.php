<?php
/*
  * Copyright © Ghost Unicorns snc. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtCsv\Transferor\Mappings;

class Fixed implements MappingTypeInterface
{
    /**
     * @var string
     */
    private $head;

    /**
     * @var string
     */
    private $value;

    /**
     * @param string $head
     * @param string $value
     */
    public function __construct(
        string $head,
        string $value
    ) {
        $this->head = $head;
        $this->value = $value;
    }

    /**
     * @param array $data
     * @return string
     */
    public function execute(array $data): string
    {
        return $this->value;
    }

    /**
     * @return string
     */
    public function getHead(): string
    {
        return $this->head;
    }
}
