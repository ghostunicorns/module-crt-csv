<?php
/*
  * Copyright © Ghost Unicorns snc. All rights reserved.
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace GhostUnicorns\CrtCsv\Model;

use Magento\Framework\Exception\FileSystemException;
use Magento\Framework\Filesystem\DirectoryList;

class GetFileNameWithPath
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param DirectoryList $directoryList
     */
    public function __construct(
        DirectoryList $directoryList
    ) {
        $this->directoryList = $directoryList;
    }

    /**
     * @param string $filePath
     * @param string $fileName
     * @return string
     * @throws FileSystemException
     */
    public function execute(string $filePath, string $fileName): string
    {
        $directory = $this->directoryList->getPath(\Magento\Framework\App\Filesystem\DirectoryList::ROOT);
        return $directory . $filePath . DIRECTORY_SEPARATOR . $fileName;
    }
}
