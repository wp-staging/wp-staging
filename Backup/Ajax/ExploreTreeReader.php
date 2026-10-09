<?php

namespace WPStaging\Backup\Ajax;

use WPStaging\Framework\Filesystem\FileObject;




class ExploreTreeReader
{
 
    private $file;

 
    private $folderStart;

 
    private $folderCount;

 
    private $offsetTableStart;

 
    private $offsetWidth;

    public function __construct(FileObject $file, int $folderStart, int $folderCount, int $offsetTableStart, int $offsetWidth)
    {
        $this->file             = $file;
        $this->folderStart      = $folderStart;
        $this->folderCount      = $folderCount;
        $this->offsetTableStart = $offsetTableStart;
        $this->offsetWidth      = $offsetWidth;
    }






    public static function joinPath(string $folder, string $name): string
    {
        return $folder === '' ? $name : $folder . '/' . $name;
    }








    public static function escapeField(string $field): string
    {
        return strtr($field, ["\t" => '\\t', "\n" => '\\n', "\r" => '\\r']);
    }





    private static function unescapeField(string $field): string
    {
        return strtr($field, ['\\t' => "\t", '\\n' => "\n", '\\r' => "\r"]);
    }





    public function countEntries(string $folder): int
    {
        $record = $this->findFolderRecord($folder);
        if ($record === null) {
            return 0;
        }

        return $record['subfolderCount'] + $record['fileCount'];
    }







    public function readEntries(string $folder, int $offset, int $limit): array
    {
        $record = $this->findFolderRecord($folder);
        if ($record === null) {
            return [];
        }

        $end = min($record['subfolderCount'] + $record['fileCount'], $offset + $limit);
        if ($offset >= $end) {
            return [];
        }

        $this->file->fseek($record['entriesStart']);
        $this->skipEntryLines($offset);

        $entries = [];
        for ($line = $offset; $line < $end; $line++) {
            $fields    = $this->readEntryFields();
            $entries[] = $line < $record['subfolderCount'] ? $this->buildSubfolderEntry($folder, $fields) : $this->buildFileEntry($folder, $fields);
        }

        return $entries;
    }






    public function readSubfolders(string $folder, int $limit): array
    {
        $record = $this->findFolderRecord($folder);
        if ($record === null) {
            return [];
        }

        $this->file->fseek($record['entriesStart']);

        $subfolders = [];
        $count      = min($record['subfolderCount'], $limit);
        for ($line = 0; $line < $count; $line++) {
            $subfolder    = $this->buildSubfolderEntry($folder, $this->readEntryFields());
            $subfolders[] = [
                'name'        => $subfolder['name'],
                'path'        => $subfolder['path'],
                'hasChildren' => $subfolder['hasChildren'],
            ];
        }

        return $subfolders;
    }





    public function readTotalsBelow(string $folder): array
    {
        $record = $this->findFolderRecord($folder);
        if ($record === null) {
            return ['count' => 0, 'size' => 0];
        }

        return ['count' => $record['totalFileCount'], 'size' => $record['totalSize']];
    }








    public function readFilesBelow(string $folder): array
    {
        $files  = [];
        $record = $this->findFolderRecord($folder);
        if ($record === null) {
            return $files;
        }

        $this->appendFilesOfFolder($files, $record);

        $prefix = $folder === '' ? '' : $folder . '/';
        $index  = $folder === '' ? 1 : $this->findFirstRecordIndexAtOrAfter($prefix);
        for (; $index < $this->folderCount; $index++) {
            $record = $this->readFolderRecord($index);
            if (strncmp($record['path'], $prefix, strlen($prefix)) !== 0) {
                break;
            }

            $this->appendFilesOfFolder($files, $record);
        }

        return $files;
    }






    private function appendFilesOfFolder(array &$files, array $record)
    {
        $this->file->fseek($record['entriesStart']);
        $this->skipEntryLines($record['subfolderCount']);
        for ($line = 0; $line < $record['fileCount']; $line++) {
            $fields  = $this->readEntryFields();
            $files[] = [
                'offset' => (int)$fields[2],
                'path'   => self::joinPath($record['path'], $fields[0]),
                'size'   => (int)$fields[1],
            ];
        }
    }





    private function findFolderRecord(string $folder)
    {
        $index = $this->findFirstRecordIndexAtOrAfter($folder);
        if ($index >= $this->folderCount) {
            return null;
        }

        $record = $this->readFolderRecord($index);

        return $record['path'] === $folder ? $record : null;
    }







    private function findFirstRecordIndexAtOrAfter(string $path): int
    {
        $low  = 0;
        $high = $this->folderCount;
        while ($low < $high) {
            $middle = intdiv($low + $high, 2);
            if (strcmp($this->readFolderRecord($middle)['path'], $path) < 0) {
                $low = $middle + 1;
                continue;
            }

            $high = $middle;
        }

        return $low;
    }





    private function readFolderRecord(int $index): array
    {
        $this->file->fseek($this->offsetTableStart + $index * $this->offsetWidth);
        $this->file->fseek($this->folderStart + (int)$this->file->fread($this->offsetWidth));
        $fields = explode("\t", rtrim($this->file->readAndMoveNext(), "\n"));

        return [
            'path'           => self::unescapeField($fields[0]),
            'entriesStart'   => (int)$fields[1],
            'subfolderCount' => (int)$fields[2],
            'fileCount'      => (int)$fields[3],
            'totalFileCount' => (int)$fields[4],
            'totalSize'      => (int)$fields[5],
        ];
    }






    private function buildSubfolderEntry(string $folder, array $fields): array
    {
        $items = (int)$fields[1];

        return [
            'type'        => 'dir',
            'name'        => $fields[0],
            'path'        => self::joinPath($folder, $fields[0]),
            'items'       => $items,
            'hasChildren' => $items > 0,
        ];
    }






    private function buildFileEntry(string $folder, array $fields): array
    {
        $size = (int)$fields[1];

        return [
            'type'          => 'file',
            'name'          => $fields[0],
            'path'          => self::joinPath($folder, $fields[0]),
            'size'          => $size,
            'sizeFormatted' => size_format($size, 2),
            'offset'        => (int)$fields[2],
        ];
    }





    private function skipEntryLines(int $count)
    {
        for ($line = 0; $line < $count; $line++) {
            $this->file->readAndMoveNext();
        }
    }




    private function readEntryFields(): array
    {
        $fields    = explode("\t", rtrim($this->file->readAndMoveNext(), "\n"));
        $fields[0] = self::unescapeField($fields[0]);

        return $fields;
    }
}
