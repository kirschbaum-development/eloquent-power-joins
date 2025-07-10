# Agent Guidelines for Eloquent Power Joins

## Commands
- **Test**: `composer test` or `vendor/bin/phpunit`
- **Single test**: `vendor/bin/phpunit tests/JoinRelationshipTest.php` or `vendor/bin/phpunit --filter test_method_name`
- **Test with coverage**: `composer test-coverage`
- **Lint**: `composer lint` or `vendor/bin/php-cs-fixer fix -vvv --show-progress=dots --config=.php-cs-fixer.php`

## Code Style
- **PHP Version**: 8.2+
- **Framework**: Laravel 11.42+/12.0+ package
- **Formatting**: Uses PHP-CS-Fixer with @Symfony rules + custom overrides
- **Imports**: Use global namespace imports for classes/constants/functions, ordered alphabetically
- **Arrays**: Short syntax `[]`, trailing commas in multiline
- **Quotes**: Single quotes preferred
- **Test methods**: snake_case naming with `@test` annotation
- **Namespaces**: `Kirschbaum\PowerJoins` for src, `Kirschbaum\PowerJoins\Tests` for tests
- **Type hints**: Use strict typing, compact nullable syntax `?Type`
- **PHPDoc**: Left-aligned, no empty returns, ordered tags
- **Variables**: No yoda conditions (`$var === 'value'` not `'value' === $var`)