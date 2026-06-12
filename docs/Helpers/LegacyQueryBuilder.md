# DB

`LegacyQueryBuilder` заменяет `DB`

```php
// INSERT с NOW()
$dataset = ['name' => 'John', 'created_at' => 'NOW()'];
$query = LegacyQueryBuilder::makeInsertQuery('users', $dataset);
// => "INSERT INTO `users` SET `name` = :name, `created_at` = NOW();"
// $dataset теперь: ['name' => 'John'] (created_at удален)

// UPDATE с массивом WHERE
$dataset = ['status' => 'active', 'updated_at' => 'NOW()'];
$query = LegacyQueryBuilder::makeUpdateQuery('users', $dataset, ['id' => 5, 'role' => 'admin']);
// => "UPDATE `users` SET `status` = :status, `updated_at` = NOW() WHERE `id` = 5 AND `role` = 'admin';"

// UPDATE со строкой WHERE
$dataset = ['name' => 'Jane'];
$query = LegacyQueryBuilder::makeUpdateQuery('users', $dataset, 'id = 5 OR email = "test@example.com"');
// => "UPDATE `users` SET `name` = :name WHERE id = 5 OR email = "test@example.com";"

// REPLACE с MVA для Sphinx
$dataset = ['id' => 1, 'tags' => [1, 2, 3], 'category' => 'books'];
[$query, $cleanDataset] = LegacyQueryBuilder::buildReplaceQueryMVA('products', $dataset, ['tags']);
// $query => "REPLACE INTO `products` (`id`, `tags`, `category`) VALUES (:id, (1,2,3), :category);"
// $cleanDataset => ['id' => 1, 'category' => 'books']
```

