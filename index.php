<?php

require __DIR__.'/vendor/autoload.php';

use Composer\Autoload\ClassLoader;
use Nikic\IncludeInterceptor\Interceptor;

function getClassLoader(): ClassLoader {
    /** @var ClassLoader $loader */
    static $loader;

    if ($loader) {
        return $loader;
    }

    $loaders = ClassLoader::getRegisteredLoaders();
    if (count($loaders) === 0) {
        throw new RuntimeException('No class loader available.');
    }

    $vendorPath = realpath(__DIR__.'/vendor');
    if (!isset($loaders[$vendorPath])) {
        throw new RuntimeException('No class loader found for the vendor directory.');
    }

    return $loader = $loaders[$vendorPath];
}

function lookupFileFromPrefixes(string $fileToLookup, array $prefixes): bool {
    foreach ($prefixes as $prefix => $files) {
        foreach ($files as $file) {
            $file = realpath($file);
            if (str_starts_with($file, $fileToLookup)) {
                return true;
            }
        }
    }
    return false;
}

function getInterceptorHook(): Closure {
    return static function(string $interceptedPath) {
        $isClassFile = lookupFileFromPrefixes($interceptedPath, getClassLoader()->getPrefixesPsr4())
            || lookupFileFromPrefixes($interceptedPath, getClassLoader()->getPrefixes())
        ;

        if (!$isClassFile) {
            return null;
        }

        $content = file_get_contents($interceptedPath);

        // Update class content at runtime
        $content = str_replace('// TODO', 'echo "Overriden!\n";', $content);

        return $content;
    };
}

function getInterceptor(): Interceptor {
    return new Interceptor(getInterceptorHook(), ['file']);
}

$interceptor = getInterceptor();

$interceptor->setUp(); // Start intercepting includes

// <Your code>
$talker = new \App\Talker();
$talker->talk();
// </Your code>

$interceptor->tearDown(); // Stop intercepting includes