<?php

declare(strict_types=1);

namespace Jblairy\PhpBenchmark\Domain\PhpVersion\Enum;

enum PhpVersion: string
{
    case PHP_5_6 = 'php56';
    case PHP_7_0 = 'php70';
    case PHP_7_1 = 'php71';
    case PHP_7_2 = 'php72';
    case PHP_7_3 = 'php73';
    case PHP_7_4 = 'php74';
    case PHP_8_0 = 'php80';
    case PHP_8_1 = 'php81';
    case PHP_8_2 = 'php82';
    case PHP_8_3 = 'php83';
    case PHP_8_4 = 'php84';
    case PHP_8_5 = 'php85';
}
