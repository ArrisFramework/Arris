<?php

namespace Tests\Helpers;

use Arris\Helpers\LegacyQueryBuilder;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(LegacyQueryBuilder::class)]
final class LegacyQueryBuilderTest extends TestCase
{
    // ==========================================
    // makeInsertQuery
    // ==========================================

    #[DataProvider('provideInsertCases')]
    #[Test]
    public function testMakeInsertQuery(array $dataset, string $expectedSql, array $expectedMutatedDataset): void
    {
        $query = LegacyQueryBuilder::makeInsertQuery('users', $dataset);

        $this->assertSame($expectedSql, $query);
        $this->assertSame($expectedMutatedDataset, $dataset);
    }

    public static function provideInsertCases(): array
    {
        return [
            'Базовый INSERT' => [
                ['name' => 'John', 'email' => 'john@example.com'],
                'INSERT INTO `users` SET `name` = :name, `email` = :email;',
                ['name' => 'John', 'email' => 'john@example.com'],
            ],
            'INSERT с NOW()' => [
                ['name' => 'John', 'created_at' => 'NOW()'],
                'INSERT INTO `users` SET `name` = :name, `created_at` = NOW();',
                ['name' => 'John'], // created_at удален
            ],
            'INSERT с NOW() в разных регистрах' => [
                ['name' => 'John', 'updated_at' => 'now()'],
                'INSERT INTO `users` SET `name` = :name, `updated_at` = NOW();',
                ['name' => 'John'],
            ],
            'INSERT с NOW() и пробелами' => [
                ['name' => 'John', 'created_at' => '  NOW()  '],
                'INSERT INTO `users` SET `name` = :name, `created_at` = NOW();',
                ['name' => 'John'],
            ],
            'Пустой INSERT' => [
                [],
                'INSERT INTO `users` () VALUES ();',
                [],
            ],
        ];
    }

    // ==========================================
    // makeUpdateQuery
    // ==========================================

    #[DataProvider('provideUpdateCases')]
    #[Test]
    public function testMakeUpdateQuery(
        array $dataset,
        array|string|null $where,
        string $expectedSql,
        array $expectedMutatedDataset
    ): void {
        $query = LegacyQueryBuilder::makeUpdateQuery('users', $dataset, $where);

        $this->assertSame($expectedSql, $query);
        $this->assertSame($expectedMutatedDataset, $dataset);
    }

    public static function provideUpdateCases(): array
    {
        return [
            'UPDATE с массивом WHERE (один ключ)' => [
                ['status' => 'active'],
                ['id' => 5],
                "UPDATE `users` SET `status` = :status WHERE `id` = 5;",
                ['status' => 'active'],
            ],
            'UPDATE с массивом WHERE (несколько ключей)' => [
                ['status' => 'active'],
                ['id' => 5, 'role' => 'admin'],
                "UPDATE `users` SET `status` = :status WHERE `id` = 5 AND `role` = 'admin';",
                ['status' => 'active'],
            ],
            'UPDATE с массивом WHERE и null значением' => [
                ['status' => 'active'],
                ['deleted_at' => null],
                "UPDATE `users` SET `status` = :status WHERE `deleted_at` IS NULL;",
                ['status' => 'active'],
            ],
            'UPDATE со строкой WHERE (без WHERE)' => [
                ['name' => 'John'],
                'id = 5',
                'UPDATE `users` SET `name` = :name WHERE id = 5;',
                ['name' => 'John'],
            ],
            'UPDATE со строкой WHERE (с WHERE)' => [
                ['name' => 'John'],
                'WHERE id = 5',
                'UPDATE `users` SET `name` = :name WHERE id = 5;',
                ['name' => 'John'],
            ],
            'UPDATE с null WHERE' => [
                ['status' => 'active'],
                null,
                'UPDATE `users` SET `status` = :status;',
                ['status' => 'active'],
            ],
            'UPDATE с NOW()' => [
                ['status' => 'active', 'updated_at' => 'NOW()'],
                ['id' => 5],
                "UPDATE `users` SET `status` = :status, `updated_at` = NOW() WHERE `id` = 5;",
                ['status' => 'active'],
            ],
        ];
    }

    #[Test]
    public function testMakeUpdateQueryThrowsExceptionOnEmptyDataset(): void
    {
        $dataset = [];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Dataset cannot be empty for UPDATE query.');

        LegacyQueryBuilder::makeUpdateQuery('users', $dataset, ['id' => 5]);
    }

    // ==========================================
    // makeReplaceQuery
    // ==========================================

    #[DataProvider('provideReplaceCases')]
    #[Test]
    public function testMakeReplaceQuery(array $dataset, string $where, string $expectedSql): void
    {
        $query = LegacyQueryBuilder::makeReplaceQuery('users', $dataset, $where);
        $this->assertSame($expectedSql, $query);
    }

    public static function provideReplaceCases(): array
    {
        return [
            'Базовый REPLACE' => [
                ['id' => 1, 'name' => 'John'],
                '',
                'REPLACE `users` SET `id` = :id, `name` = :name;',
            ],
            'REPLACE с WHERE' => [
                ['id' => 1, 'name' => 'John'],
                'WHERE id = 1',
                'REPLACE `users` SET `id` = :id, `name` = :name WHERE id = 1;',
            ],
            'REPLACE с NOW()' => [
                ['id' => 1, 'updated_at' => 'NOW()'],
                '',
                'REPLACE `users` SET `id` = :id, `updated_at` = NOW();',
            ],
        ];
    }

    #[Test]
    public function testMakeReplaceQueryThrowsExceptionOnEmptyDataset(): void
    {
        $dataset = [];

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Dataset cannot be empty for REPLACE query.');

        LegacyQueryBuilder::makeReplaceQuery('users', $dataset);
    }

    // ==========================================
    // buildReplaceQueryMVA
    // ==========================================

    #[Test]
    public function testBuildReplaceQueryMVA(): void
    {
        $dataset = [
            'id' => 1,
            'tags' => [1, 2, 3],
            'category' => 'books',
        ];

        [$query, $cleanDataset] = LegacyQueryBuilder::buildReplaceQueryMVA(
            'products',
            $dataset,
            ['tags']
        );

        $expectedQuery = 'REPLACE INTO `products` (`id`, `tags`, `category`) VALUES (:id, (1,2,3), :category);';
        $expectedDataset = ['id' => 1, 'category' => 'books'];

        $this->assertSame($expectedQuery, $query);
        $this->assertSame($expectedDataset, $cleanDataset);
    }

    #[Test]
    public function testBuildReplaceQueryMVAWithStringMVA(): void
    {
        $dataset = [
            'id' => 1,
            'tags' => '1,2,3', // строка вместо массива
        ];

        [$query, $cleanDataset] = LegacyQueryBuilder::buildReplaceQueryMVA(
            'products',
            $dataset,
            ['tags']
        );

        $this->assertStringContainsString('(1,2,3)', $query);
        $this->assertSame(['id' => 1], $cleanDataset);
    }

    #[Test]
    public function testBuildReplaceQueryMVAWithoutMVAAttributes(): void
    {
        $dataset = ['id' => 1, 'name' => 'John'];

        [$query, $cleanDataset] = LegacyQueryBuilder::buildReplaceQueryMVA(
            'products',
            $dataset,
            [] // нет MVA-атрибутов
        );

        $expectedQuery = 'REPLACE INTO `products` (`id`, `name`) VALUES (:id, :name);';

        $this->assertSame($expectedQuery, $query);
        $this->assertSame($dataset, $cleanDataset);
    }

    #[Test]
    public function testBuildReplaceQueryMVAThrowsExceptionOnEmptyDataset(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Dataset cannot be empty for REPLACE MVA query.');

        LegacyQueryBuilder::buildReplaceQueryMVA('products', [], ['tags']);
    }

    // ==========================================
    // SQL Injection Protection
    // ==========================================

    #[Test]
    public function testSqlInjectionProtectionInColumnNames(): void
    {
        // Попытка SQL-инъекции через имя колонки
        $dataset = ['name; DROP TABLE users;--' => 'John'];

        $query = LegacyQueryBuilder::makeInsertQuery('users', $dataset);

        // Опасные символы должны быть удалены
        $this->assertStringContainsString('`nameDROPTABLEusers`', $query);
    }

    #[Test]
    public function testSqlInjectionProtectionInTableName(): void
    {
        // Попытка SQL-инъекции через имя таблицы
        $dataset = ['name' => 'John'];

        $query = LegacyQueryBuilder::makeInsertQuery('users; DROP TABLE users;--', $dataset);

        // Опасные символы должны быть удалены
        $this->assertStringNotContainsString('DROP TABLE', $query);
        $this->assertStringContainsString('`usersDROPTABLEusers`', $query);
    }

    // ==========================================
    // Dataset Mutation
    // ==========================================

    #[Test]
    public function testDatasetMutationRemovesNow(): void
    {
        $dataset = [
            'name' => 'John',
            'created_at' => 'NOW()',
            'updated_at' => 'now()',
        ];

        LegacyQueryBuilder::makeInsertQuery('users', $dataset);

        // NOW() должны быть удалены из dataset
        $this->assertArrayHasKey('name', $dataset);
        $this->assertArrayNotHasKey('created_at', $dataset);
        $this->assertArrayNotHasKey('updated_at', $dataset);
        $this->assertCount(1, $dataset);
    }
}