<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Set\ValueObject\SetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Php81\Rector\Array_\FirstClassCallableRector;
use Rector\CodeQuality\Rector\If_\ExplicitBoolCompareRector;
use Rector\DeadCode\Rector\Property\RemoveUselessVarTagRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessParamTagRector;
use Rector\DeadCode\Rector\ClassMethod\RemoveUselessReturnTagRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->paths([
        __DIR__ . '/src',
    ]);

    $rectorConfig->skip([
        __DIR__ . '/vendor',
        ExplicitBoolCompareRector::class,
        FirstClassCallableRector::class,
        RemoveUselessReturnTagRector::class,
        RemoveUselessVarTagRector::class,
        RemoveUselessParamTagRector::class,
    ]);

    $rectorConfig->importNames();
    $rectorConfig->importShortClasses();
    $rectorConfig->removeUnusedImports();

    $rectorConfig->rule(Rector\Php84\Rector\Param\ExplicitNullableParamTypeRector::class);

    $rectorConfig->sets([
        LevelSetList::UP_TO_PHP_84,
        SetList::CODE_QUALITY,
        SetList::DEAD_CODE,
        SetList::TYPE_DECLARATION,
    ]);
};
