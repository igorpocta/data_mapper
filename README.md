# Data Mapper

[![CI](https://github.com/igorpocta/data_mapper/actions/workflows/ci.yml/badge.svg)](https://github.com/igorpocta/data-mapper/actions/workflows/ci.yml)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue)](https://php.net)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-level%209-brightgreen)](https://phpstan.org)
[![Tests](https://img.shields.io/badge/tests-572%20passing-success)](.)

High-performance and type-safe PHP library for bidirectional data mapping between JSON/arrays/CSV and objects.

## Requirements

- PHP 8.1 or higher
- Composer

## Installation

```bash
composer require igorpocta/data-mapper
```

## Quick Start

```php
use Pocta\DataMapper\Mapper;

class User
{
    public function __construct(
        public int $id,
        public string $name,
        public bool $active
    ) {}
}

$mapper = new Mapper();

// JSON → Object
$user = $mapper->fromJson('{"id": 1, "name": "John", "active": true}', User::class);

// Object → JSON
$json = $mapper->toJson($user); // {"id":1,"name":"John","active":true}

// Array → Object
$user = $mapper->fromArray(['id' => 1, 'name' => 'John', 'active' => true], User::class);

// Object → Array
$array = $mapper->toArray($user); // ['id' => 1, 'name' => 'John', 'active' => true]
```

### Configuration

```php
use Pocta\DataMapper\MapperOptions;

$mapper = new Mapper(MapperOptions::development()); // Strict validation for development
$mapper = new Mapper(MapperOptions::production());   // Lenient for production
$mapper = new Mapper(MapperOptions::strict());       // Both auto-validation and strict mode
```

## Key Features

### Mapping
- **Bidirectional mapping**: JSON/array/CSV ↔ objects with automatic conversion
- **CSV Support**: Import/export CSV files with automatic type conversion and custom column mapping
- **Batch processing**: Efficient mapping of collections with `fromArrayCollection()`, `toJsonCollection()`, `fromCsv()`, etc.
- **Property path resolver**: Map nested values using dot notation (e.g., `user.address.street`)
- **Type safety**: Full support for PHP 8.1+ types including union and intersection types

### Data Types
- **Basic types**: int, float, string, bool, array
- **DateTime**: DateTimeImmutable and DateTime with formats and timezones
- **Enum**: BackedEnum and UnitEnum (PHP 8.1+)
- **Objects**: Nested objects and arrays of objects

### Advanced
- **Constructor properties**: Full support for promoted properties
- **Partial updates**: Merge partial data into existing objects with `merge()`
- **Object-to-DTO mapping**: Map from Doctrine entities to DTOs using `fromObject()`
- **Discriminator mapping**: Polymorphic object mapping based on discriminator fields
- **Filters**: 70+ built-in filters (security, formatting, case conversion, etc.)
- **Hydration**: Custom functions for data transformation using `MapPropertyWithFunction`
- **Event System**: Hooks for pre/post processing (logging, transformations, error handling)
- **Validation**: 40+ Assert attributes (NotNull, Range, Email, Count, Valid, Callback, etc.)
- **Validation Groups**: Conditional validation with `GroupSequenceProviderInterface`
- **Recursive Validation**: `#[Valid]` for nested objects with dot-notation error paths

### Code Quality
- **PHPStan Level 9**: Strictest static analysis
- **572 unit tests**, 1494 assertions
- **Zero external dependencies**
- **Debug & Profiling**: Integrated tools for performance analysis

## Documentation

| Topic | Description |
|-------|-------------|
| [Configuration](docs/configuration.md) | MapperOptions, supported types |
| [Usage](docs/usage.md) | Basic usage, batch processing, CSV, merge, Object-to-DTO, discriminator, class definitions |
| [Advanced Features](docs/advanced.md) | Property paths, filters, hydration, nullable, custom names, type conversion |
| [Event System](docs/events.md) | Event listeners, priorities, practical examples |
| [Validation](docs/validation.md) | Validators, groups, recursive validation, callback, custom validators |
| [Cache System](docs/cache.md) | Array, File, Redis, custom cache implementations |
| [Debug & Profiling](docs/debugging.md) | Debugger, profiler, performance analysis |
| [Architecture](docs/architecture.md) | Custom types, separate components, folder structure, examples |

## Testing

```bash
# Run all tests
composer test

# Run PHPStan (level 9)
composer phpstan
```

## License

MIT
