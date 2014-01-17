<?php
/**
 * Стартовый файл тестов
 *
 * @package Eresus
 * @subpackage Tests
 */

/**
 * Путь к папке исходные кодов
 */
define('TESTS_SRC_DIR', realpath(__DIR__ . '/../../src'));

/*
 * Автозагрузка классов модуля
 */

$pluginName = basename(realpath(TESTS_SRC_DIR . '/..'));

$autoload = __DIR__ . '/../../vendor/autoload.php';

if (file_exists($autoload))
/* Если доступен Composer, используем его автозагрузчик */
{
    /** @noinspection PhpIncludeInspection */
    $autoloader = include $autoload;
    /** @var Composer\Autoload\ClassLoader $autoloader */
    $autoloader->addClassMap(
        array($pluginName => TESTS_SRC_DIR . '/' .strtolower($pluginName) . '.php')
    );
    $autoloader->add($pluginName . '_', TESTS_SRC_DIR . '/classes');
}
else
/* Если Composer-а нет, используем свой автозагрузчик */
{
    spl_autoload_register(
        function ($class) use ($pluginName)
        {
            $filename = TESTS_SRC_DIR . '/' . strtolower($pluginName);
            if (substr($class, 0, strlen($pluginName) + 1) == $pluginName . '_')
            {
                $filename .= '/classes/'
                    . str_replace('_', '/', substr($class, strlen($pluginName)));
            }
            elseif ($class != $pluginName)
            {
                return;
            }
            $filename .= '.php';
            if (file_exists($filename))
            {
                /** @noinspection PhpIncludeInspection */
                require $filename;
            }
        }
    );
}

require_once 'stubs.php';

