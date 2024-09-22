<?php

namespace WPStaging\Framework\Adapter;

interface DirectoryInterface
{
    public function getBackupDirectory(): string;

    public function getTmpDirectory(): string;

    public function getPluginUploadsDirectory(bool $refresh = false): string;

    public function getUploadsDirectory(bool $refresh = false): string;

    public function getPluginsDirectory(): string;

    public function getMuPluginsDirectory(): string;

    public function getAllThemesDirectories(): array;

    public function getActiveThemeParentDirectory(): string;

    public function getLangsDirectory(): string;

    public function getAbsPath(): string;

    public function getWpContentDirectory(): string;
}
