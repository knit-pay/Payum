<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\Import\FullyQualifiedStrictTypesFixer;
use Rector\Config\RectorConfig;
use Rector\PHPUnit\Rector\Class_\AddSeeTestAnnotationRector;
use Rector\PHPUnit\Rector\ClassMethod\AddDoesNotPerformAssertionToNonAssertingTestRector;
use Rector\PHPUnit\Set\PHPUnitLevelSetList;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Set\ValueObject\SetList;
use Rector\TypeDeclaration\Rector\Param\ParamTypeFromStrictTypedPropertyRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src'
    ]);

    $rectorConfig->importNames();
    $rectorConfig->importShortClasses();

    // define sets of rules
    $rectorConfig->sets([
        PHPUnitSetList::PHPUNIT_YIELD_DATA_PROVIDER,
        PHPUnitSetList::PHPUNIT_SPECIFIC_METHOD,
        PHPUnitSetList::PHPUNIT_EXCEPTION,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
        PHPUnitSetList::REMOVE_MOCKS,
        PHPUnitLevelSetList::UP_TO_PHPUNIT_100,
        LevelSetList::UP_TO_PHP_74,
        SetList::TYPE_DECLARATION,
    ]);

    $services = $rectorConfig->services();
    // $services->set(FullyQualifiedStrictTypesFixer::class);
    // $services->set(ParamTypeFromStrictTypedPropertyRector::class);

    $services->remove(AddDoesNotPerformAssertionToNonAssertingTestRector::class);
    $services->remove(AddSeeTestAnnotationRector::class);
};
