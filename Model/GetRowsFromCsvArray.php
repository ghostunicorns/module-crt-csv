<?php
/*
  * Copyright © Ghost Unicorns snc. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtCsv\Model;

class GetRowsFromCsvArray
{
    /**
     * @param array $data
     * @return array
     */
    public function execute(array $data): array
    {
        $headers = array_shift($data);
        $rows = [];

        foreach ($data as $row) {
            $rows[] = array_combine($headers, $row);
        }
        return $rows;
    }
}
