<?php

// autoload_static.php @generated by Composer

namespace Composer\Autoload;

class ComposerStaticInitbc93e31808076faa538201e6aad6d276
{
    public static $prefixLengthsPsr4 = array (
        'O' => 
        array (
            'Orhanerday\\OpenAi\\' => 18,
        ),
    );

    public static $prefixDirsPsr4 = array (
        'Orhanerday\\OpenAi\\' => 
        array (
            0 => __DIR__ . '/..' . '/orhanerday/open-ai/src',
        ),
    );

    public static $classMap = array (
        'Composer\\InstalledVersions' => __DIR__ . '/..' . '/composer/InstalledVersions.php',
    );

    public static function getInitializer(ClassLoader $loader)
    {
        return \Closure::bind(function () use ($loader) {
            $loader->prefixLengthsPsr4 = ComposerStaticInitbc93e31808076faa538201e6aad6d276::$prefixLengthsPsr4;
            $loader->prefixDirsPsr4 = ComposerStaticInitbc93e31808076faa538201e6aad6d276::$prefixDirsPsr4;
            $loader->classMap = ComposerStaticInitbc93e31808076faa538201e6aad6d276::$classMap;

        }, null, ClassLoader::class);
    }
}