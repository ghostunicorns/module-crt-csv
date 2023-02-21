<?php
/*
  * Copyright © Ghost Unicorns snc. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtCsv\Model;

use GhostUnicorns\CrtBase\Exception\CrtException;

class GetEntityIdentifier
{
    /**
     * @param array $identifiers
     * @param array $data
     * @return string
     * @throws CrtException
     */
    public function execute(array $identifiers, array $data): string
    {
        $result = '';

        foreach ($identifiers as $identifier) {
            if (!array_key_exists($identifier, $data)) {
                throw new CrtException(__('Identifier column not found:%1', $identifier));
            }
            $result .= '.' . str_replace('.','_',$data[$identifier]);
        }

        $result = ltrim($result, '.');

        if ($result === '') {
            throw new CrtException(__('Invalid identifier:%1', $result));
        }

        return $result;
    }
}
