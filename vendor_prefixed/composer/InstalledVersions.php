<?php

namespace WPStaging\Vendor\Composer;

use WPStaging\Vendor\Composer\Semver\VersionParser;
class InstalledVersions
{
    private static $installed = array('root' => array('pretty_version' => 'dev-master', 'version' => 'dev-master', 'aliases' => array(), 'reference' => '38e77db7ecad57a32380bf366f03130a4a14f55c', 'name' => 'wp-staging/pro'), 'versions' => array('lucatume/di52' => array('pretty_version' => '2.1.3', 'version' => '2.1.3.0', 'aliases' => array(), 'reference' => 'd57c267abf32a1d0ffd97b401e755028a9e2bc78'), 'psr/log' => array('pretty_version' => '1.1.3', 'version' => '1.1.3.0', 'aliases' => array(), 'reference' => '0f73288fd15629204f9d42b7055f72dacbe811fc'), 'symfony/filesystem' => array('pretty_version' => 'v2.8.52', 'version' => '2.8.52.0', 'aliases' => array(), 'reference' => '7ae46872dad09dffb7fe1e93a0937097339d0080'), 'symfony/finder' => array('pretty_version' => 'v2.8.52', 'version' => '2.8.52.0', 'aliases' => array(), 'reference' => '1444eac52273e345d9b95129bf914639305a9ba4'), 'symfony/polyfill-ctype' => array('pretty_version' => 'v1.19.0', 'version' => '1.19.0.0', 'aliases' => array(), 'reference' => 'aed596913b70fae57be53d86faa2e9ef85a2297b'), 'wp-staging/pro' => array('pretty_version' => 'dev-master', 'version' => 'dev-master', 'aliases' => array(), 'reference' => '38e77db7ecad57a32380bf366f03130a4a14f55c'), 'xrstf/composer-php52' => array('pretty_version' => 'v1.0.20', 'version' => '1.0.20.0', 'aliases' => array(), 'reference' => 'bd41459d5e27df8d33057842b32377c39e97a5a8')));
    public static function getInstalledPackages()
    {
        return \array_keys(self::$installed['versions']);
    }
    public static function isInstalled($packageName)
    {
        return isset(self::$installed['versions'][$packageName]);
    }
    public static function satisfies(\WPStaging\Vendor\Composer\Semver\VersionParser $parser, $packageName, $constraint)
    {
        $constraint = $parser->parseConstraints($constraint);
        $provided = $parser->parseConstraints(self::getVersionRanges($packageName));
        return $provided->matches($constraint);
    }
    public static function getVersionRanges($packageName)
    {
        if (!isset(self::$installed['versions'][$packageName])) {
            throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
        }
        $ranges = array();
        if (isset(self::$installed['versions'][$packageName]['pretty_version'])) {
            $ranges[] = self::$installed['versions'][$packageName]['pretty_version'];
        }
        if (\array_key_exists('aliases', self::$installed['versions'][$packageName])) {
            $ranges = \array_merge($ranges, self::$installed['versions'][$packageName]['aliases']);
        }
        if (\array_key_exists('replaced', self::$installed['versions'][$packageName])) {
            $ranges = \array_merge($ranges, self::$installed['versions'][$packageName]['replaced']);
        }
        if (\array_key_exists('provided', self::$installed['versions'][$packageName])) {
            $ranges = \array_merge($ranges, self::$installed['versions'][$packageName]['provided']);
        }
        return \implode(' || ', $ranges);
    }
    public static function getVersion($packageName)
    {
        if (!isset(self::$installed['versions'][$packageName])) {
            throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
        }
        if (!isset(self::$installed['versions'][$packageName]['version'])) {
            return null;
        }
        return self::$installed['versions'][$packageName]['version'];
    }
    public static function getPrettyVersion($packageName)
    {
        if (!isset(self::$installed['versions'][$packageName])) {
            throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
        }
        if (!isset(self::$installed['versions'][$packageName]['pretty_version'])) {
            return null;
        }
        return self::$installed['versions'][$packageName]['pretty_version'];
    }
    public static function getReference($packageName)
    {
        if (!isset(self::$installed['versions'][$packageName])) {
            throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
        }
        if (!isset(self::$installed['versions'][$packageName]['reference'])) {
            return null;
        }
        return self::$installed['versions'][$packageName]['reference'];
    }
    public static function getRootPackage()
    {
        return self::$installed['root'];
    }
    public static function getRawData()
    {
        return self::$installed;
    }
    public static function reload($data)
    {
        self::$installed = $data;
    }
}
