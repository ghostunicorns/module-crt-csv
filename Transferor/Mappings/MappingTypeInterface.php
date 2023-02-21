<?php
/*
  * Copyright © Ghost Unicorns snc. All rights reserved.
 * See LICENSE for license details.
 */

namespace GhostUnicorns\CrtCsv\Transferor\Mappings;

interface MappingTypeInterface
{
    /**
     * @return string
     */
    public function getHead(): string;

    /**
     * @param array $data
     * @return string
     */
    public function execute(array $data): string;
}
