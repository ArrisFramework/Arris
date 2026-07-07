<?php

namespace Arris\Helpers;

interface LegacyQueryBuilderInterface
{
    public static function makeInsertQuery(string $table, array &$dataset): string;

    public static function makeUpdateQuery(string $table, array &$dataset, array|string|null $whereCondition = null): string;

    public static function makeReplaceQuery(string $table, array &$dataset, string $where = ''): string;

    public static function buildReplaceQueryMVA(string $table, array $dataset, array $mvaAttributes): array;
}
