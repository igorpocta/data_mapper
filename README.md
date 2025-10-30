# Data Mapper

[![CI](https://github.com/igorpocta/data-mapper/actions/workflows/ci.yml/badge.svg)](https://github.com/igorpocta/data-mapper/actions/workflows/ci.yml)
[![PHP Version](https://img.shields.io/badge/PHP-8.1%2B-blue)](https://php.net)
[![PHPStan Level](https://img.shields.io/badge/PHPStan-level%209-brightgreen)](https://phpstan.org)
[![Tests](https://img.shields.io/badge/tests-274%20passing-success)](.)

High-performance and type-safe PHP library for bidirectional data mapping between JSON/arrays and objects. Supports constructors, nullable types, enums, DateTime, nested objects, discriminator mapping for polymorphism, filters, and much more.

## Table of Contents

- [Requirements](#requirements)
- [Installation](#installation)
- [Key Features](#key-features)
- [Quick Start](#quick-start)
- [Configuration (MapperOptions)](#configuration-mapperoptions)
- [Supported Types](#supported-types)
- [Class Definitions](#class-definitions)
- [Advanced Features](#advanced-features)
- [Event System](#event-system)
- [Validation System](#validation-system)
- [Cache System](#cache-system)
- [Debug & Profiling](#debug--profiling)
- [Testing](#testing)
- [Architecture](#architecture)

## Requirements

- PHP 8.1 or higher
- Composer

## Installation

```bash
composer require igorpocta/data-mapper
```

## Key Features

### Mapping
- **Bidirectional mapping**: JSON/array ↔ objects with automatic conversion
- **Batch processing**: Efficient mapping of collections with `fromArrayCollection()`, `toJsonCollection()`, etc.
- **Type safety**: Full support for PHP 8.1+ types including union and intersection types
- **Nullable types**: Automatic handling of `?int`, `?string`, etc.
- **Custom names**: Map to different keys in JSON using attributes

### Data Types
- **Basic types**: int, float, string, bool, array
- **DateTime**: Support for DateTimeImmutable and DateTime with formats and timezones
- **Enum**: BackedEnum and UnitEnum (PHP 8.1+)
- **Objects**: Nested objects and arrays of objects
- **Mixed arrays**: Associative arrays with arbitrary values

### Advanced Features
- **Constructor properties**: Full support for promoted properties
- **Partial updates**: Merge partial data into existing objects with `merge()` method
- **Discriminator mapping**: Polymorphic object mapping based on discriminator fields (vehicles, events, payment methods)
- **Filters**: 60+ built-in filters for data transformation (trim, lowercase, slugify, etc.)
- **Hydration**: Custom functions for data transformation using `MapPropertyWithFunction`
- **Event System**: Hooks for pre/post processing (logging, transformations, error handling)
- **Validation**: 30+ Assert attributes (NotNull, Range, Email, Choice, Callback, Type, IsTrue, Ip, etc.)
- **Auto-validation**: Automatic object validation after denormalization
- **Strict mode**: Validates that input contains only known keys, preventing unknown data
- **Flexible architecture**: Normalizer and Denormalizer as separate components

### Code Quality
- **PHPStan Level 9**: Strictest static analysis
- **100% tested**: 274 unit tests, 902 assertions
- **Extensibility**: Easy addition of custom data types, filters, and validators
- **Debug & Profiling**: Integrated tools for performance analysis and optimization

## Quick Start

### Basic Example

```php
use Pocta\DataMapper\Mapper;

// Define a class
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

### Configuration with MapperOptions

```php
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\MapperOptions;

// Use predefined configurations
$mapper = new Mapper(MapperOptions::development()); // Strict validation for development
$mapper = new Mapper(MapperOptions::production());   // Lenient for production
$mapper = new Mapper(MapperOptions::strict());       // Both auto-validation and strict mode

// Custom configuration
$options = new MapperOptions(
    autoValidate: true,
    strictMode: true,
    throwOnMissingData: true,
    skipNullValues: false,
    preserveNumericStrings: false
);
$mapper = new Mapper($options);

// Modify existing options
$newOptions = $options->with(strictMode: false);
$mapper = new Mapper($newOptions);
```

## Configuration (MapperOptions)

The `MapperOptions` class provides a clean way to configure the Mapper behavior.

### Available Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `autoValidate` | `bool` | `false` | Automatically validate objects after denormalization |
| `strictMode` | `bool` | `false` | Throw validation error if unknown keys are present in input |
| `throwOnMissingData` | `bool` | `true` | Throw exception when required data is missing |
| `skipNullValues` | `bool` | `false` | Skip null values during normalization (don't include in output) |
| `preserveNumericStrings` | `bool` | `false` | Keep numeric strings as strings instead of converting to numbers |

### Factory Methods

```php
// Quick configurations for common scenarios
MapperOptions::withAutoValidation()  // Enable auto-validation only
MapperOptions::withStrictMode()      // Enable strict mode only
MapperOptions::strict()              // Enable both auto-validation and strict mode
MapperOptions::development()         // Strict validation for development
MapperOptions::production()          // Lenient configuration for production
```

### Custom Configuration

```php
$options = new MapperOptions(
    autoValidate: true,
    strictMode: true,
    skipNullValues: true
);

$mapper = new Mapper($options);
```

### Immutable Updates

Use the `with()` method to create modified copies:

```php
$baseOptions = MapperOptions::production();
$strictOptions = $baseOptions->with(strictMode: true);

// $baseOptions is unchanged
// $strictOptions has strictMode enabled
```

## Supported Types

### Scalar Types
- `int` / `integer` - Integers
- `float` / `double` - Floating-point numbers
- `string` - Text strings
- `bool` / `boolean` - Boolean values

### Date and Time
- `DateTimeImmutable` - Immutable date/time object (recommended)
- `DateTime` - Mutable date/time object
- Format support: ISO 8601, RFC 3339, custom formats
- Timezones: Automatic conversion between timezones

### Enum (PHP 8.1+)
- `BackedEnum` - Enum with values (string or int)
- `UnitEnum` - Simple enum without values

### Complex Types
- `array` - Array with arbitrary content
- `array<ClassName>` - Array of objects using `arrayOf` attribute
- Custom objects - Nested objects of arbitrary depth

### Nullable Types
All types support nullable variants:
- `?int`, `?string`, `?bool`
- `?DateTimeImmutable`, `?DateTime`
- `?MyCustomClass`

## Basic Usage

### 1. Mapping from JSON to Object

```php
use Pocta\DataMapper\Mapper;

$mapper = new Mapper();

// From JSON string
$json = '{"id": 1, "name": "John Doe", "active": true}';
$user = $mapper->fromJson($json, User::class);

// From array
$data = ['id' => 1, 'name' => 'John Doe', 'active' => true];
$user = $mapper->fromArray($data, User::class);
```

### 2. Mapping from Object to JSON/Array

```php
$user = new User(1, 'Jane Doe', true);

// To JSON string
$json = $mapper->toJson($user);

// To array
$array = $mapper->toArray($user);
```

### 3. Batch Processing (Collections)

Working with multiple objects at once is more efficient than processing them individually:

```php
use Pocta\DataMapper\Mapper;

$mapper = new Mapper();

// From array of arrays to array of objects
$data = [
    ['id' => 1, 'name' => 'John', 'active' => true],
    ['id' => 2, 'name' => 'Jane', 'active' => false],
    ['id' => 3, 'name' => 'Bob', 'active' => true],
];
$users = $mapper->fromArrayCollection($data, User::class);
// Returns: User[]

// From JSON array to array of objects
$json = '[
    {"id": 1, "name": "John", "active": true},
    {"id": 2, "name": "Jane", "active": false}
]';
$users = $mapper->fromJsonCollection($json, User::class);
// Returns: User[]

// From array of objects to array of arrays
$users = [new User(1, 'John', true), new User(2, 'Jane', false)];
$data = $mapper->toArrayCollection($users);
// Returns: [['id' => 1, 'name' => 'John', ...], ...]

// From array of objects to JSON array
$json = $mapper->toJsonCollection($users);
// Returns: '[{"id":1,"name":"John",...},{"id":2,"name":"Jane",...}]'
```

**Benefits of batch processing:**
- Cleaner code - no manual loops
- Consistent error handling across all items
- Better integration with profiler and debugger
- Type-safe with PHPStan generics

**Validation in collections:**
```php
// With strict mode, all items are validated
$mapper = new Mapper(MapperOptions::withStrictMode());

$data = [
    ['id' => 1, 'name' => 'John'],
    ['id' => 2, 'name' => 'Jane', 'unknown' => 'error'], // This will throw
];

try {
    $users = $mapper->fromArrayCollection($data, User::class);
} catch (ValidationException $e) {
    // Error during processing second item
    echo $e->getMessage();
}
```

### 4. Partial Updates / Merge

Update only specific properties of an existing object without recreating it:

```php
use Pocta\DataMapper\Mapper;

$mapper = new Mapper();

// Existing object from database
$user = $userRepository->find(1); // User{id: 1, name: 'John', email: 'john@example.com', age: 30}

// Partial update from API request (PATCH endpoint)
$partialData = ['name' => 'Jane']; // Only update name
$mapper->merge($partialData, $user);

// Result: User{id: 1, name: 'Jane', email: 'john@example.com', age: 30}
// Only name was updated, other properties remain unchanged
```

**Skip null values:**
```php
// API sends null for fields that shouldn't be updated
$partialData = [
    'name' => 'Jane',
    'email' => null,  // Keep current email, don't update to null
    'age' => 35
];

$mapper->merge($partialData, $user, skipNull: true);

// Result: name and age updated, email unchanged
```

**With strict mode:**
```php
$mapper = new Mapper(MapperOptions::withStrictMode());

$partialData = [
    'name' => 'Jane',
    'unknown_field' => 'value' // This will throw ValidationException
];

try {
    $mapper->merge($partialData, $user);
} catch (ValidationException $e) {
    // Unknown key error
}
```

**Real-world PATCH endpoint example:**
```php
#[Route('/api/users/{id}', methods: ['PATCH'])]
public function updateUser(int $id, Request $request): Response
{
    $user = $this->userRepository->find($id);

    // Get partial data from request body
    $partialData = json_decode($request->getContent(), true);

    // Merge changes into existing entity
    $this->mapper->merge($partialData, $user, skipNull: true);

    // Validate and save
    $this->validator->validate($user);
    $this->em->flush();

    return $this->json($user);
}
```

**Benefits:**
- Perfect for PATCH endpoints
- Preserves unchanged properties
- Works with any object (not just newly created ones)
- Respects all type conversions and validations
- Optional strict mode for security
- Can skip null values to prevent accidental deletions

### 5. Discriminator Mapping (Polymorphism)

The `DiscriminatorMap` attribute enables polymorphic object mapping based on a discriminator field. This is useful when deserializing data that can represent different concrete classes (e.g., different vehicle types, payment methods, or event types).

**Basic Example:**

```php
use Pocta\DataMapper\Attributes\DiscriminatorMap;
use Pocta\DataMapper\Attributes\DiscriminatorProperty;

#[DiscriminatorMap(
    property: 'type',
    mapping: [
        'car' => Car::class,
        'bike' => Bike::class,
        'truck' => Truck::class,
    ]
)]
abstract class Vehicle
{
    #[DiscriminatorProperty]
    protected string $type;

    protected string $brand;
    protected int $year;

    public function __construct(string $type, string $brand, int $year)
    {
        $this->type = $type;
        $this->brand = $brand;
        $this->year = $year;
    }
}

class Car extends Vehicle
{
    private int $doors;

    public function __construct(string $brand, int $year, int $doors = 4)
    {
        parent::__construct('car', $brand, $year);
        $this->doors = $doors;
    }
}

class Bike extends Vehicle
{
    private bool $electric;

    public function __construct(string $brand, int $year, bool $electric = false)
    {
        parent::__construct('bike', $brand, $year);
        $this->electric = $electric;
    }
}
```

**Usage:**

```php
$mapper = new Mapper();

// The mapper inspects 'type' field and instantiates the correct class
$carData = ['type' => 'car', 'brand' => 'Toyota', 'year' => 2020, 'doors' => 4];
$vehicle = $mapper->fromArray($carData, Vehicle::class);
// Result: Car instance with all properties set

$bikeData = ['type' => 'bike', 'brand' => 'Trek', 'year' => 2021, 'electric' => true];
$vehicle = $mapper->fromArray($bikeData, Vehicle::class);
// Result: Bike instance with all properties set
```

**With Collections:**

```php
$data = [
    ['type' => 'car', 'brand' => 'Toyota', 'year' => 2020, 'doors' => 4],
    ['type' => 'bike', 'brand' => 'Trek', 'year' => 2021, 'electric' => true],
    ['type' => 'truck', 'brand' => 'Ford', 'year' => 2019, 'capacity' => 5000]
];

$vehicles = $mapper->fromArrayCollection($data, Vehicle::class);
// Result: [Car, Bike, Truck] instances
```

**Custom Property Names:**

You can use `#[MapProperty]` on the discriminator field if your JSON uses a different name:

```php
use Pocta\DataMapper\Attributes\MapProperty;

#[DiscriminatorMap(
    property: 'event_type',
    mapping: [
        'user_created' => UserCreatedEvent::class,
        'order_placed' => OrderPlacedEvent::class,
    ]
)]
abstract class Event
{
    #[MapProperty(name: 'event_type')]
    protected string $eventType;

    protected string $timestamp;
}
```

**Error Handling:**

The mapper validates discriminator values and provides clear error messages:

```php
// Missing discriminator field
$data = ['brand' => 'Toyota', 'year' => 2020];
$mapper->fromArray($data, Vehicle::class);
// ValidationException: "Missing discriminator property 'type'"

// Unknown discriminator value
$data = ['type' => 'airplane', 'brand' => 'Boeing', 'year' => 2022];
$mapper->fromArray($data, Vehicle::class);
// ValidationException: "Unknown discriminator value 'airplane'. Available values: car, bike, truck"
```

**Benefits:**
- Clean polymorphic deserialization without manual type checking
- Type-safe: Each concrete class is properly typed
- Works with all mapper features (validation, filters, nested objects)
- Integrates seamlessly with collections (`fromArrayCollection`, `fromJsonCollection`)
- Clear error messages for debugging

**Use Cases:**
- API payloads with different entity types (vehicles, products, users)
- Event sourcing with multiple event types
- Payment processing with different payment methods
- Notification systems with various notification types
- Multi-tenant systems with different entity variants

## Class Definitions

### With MapProperty Attribute (recommended for custom names)

```php
use Pocta\DataMapper\Attributes\MapProperty;

class User
{
    #[MapProperty]
    private int $id;

    #[MapProperty]
    private string $name;

    #[MapProperty]
    private bool $active;

    // Custom name in JSON
    #[MapProperty(name: 'user_age')]
    private int $age;

    // Getters and setters...
}
```

### Without Attribute (automatic detection)

```php
class Product
{
    // All properties are automatically mapped based on their type
    private int $id;
    private string $title;
    private bool $enabled;
    private ?string $description;  // Nullable property

    // Getters and setters...
}
```

### With Constructor (Promoted Properties)

```php
use Pocta\DataMapper\Attributes\MapProperty;

class UserWithConstructor
{
    // Property outside constructor
    #[MapProperty]
    private string $email;

    public function __construct(
        #[MapProperty]
        private int $id,
        #[MapProperty]
        private string $name,
        #[MapProperty]
        private bool $active = true  // Default value
    ) {
    }

    // Getters and setters...
}
```

## Advanced Features

### Filters (post-processing)

Filters are attributes that modify values after mapping. They are applied:
- during normalization (object → array/JSON) after type conversion,
- during denormalization (array/JSON → object) before type conversion.

Usage on properties (attribute order is application order):

```php
use Pocta\DataMapper\Attributes\Filters\{StringTrimFilter,StringToLowerFilter,StripTagsFilter,ToNullFilter};

class Article
{
    #[StringTrimFilter]
    #[StringToLowerFilter]
    public string $slug;

    #[StripTagsFilter('<b><i>')]
    public string $excerpt;

    #[ToNullFilter(values: ['','N/A'])]
    public ?string $subtitle = null;
}
```

Available filters (overview):

- Strings: `StringTrimFilter`, `StringToLowerFilter`, `StringToUpperFilter`, `CollapseWhitespaceFilter`, `TitleCaseFilter`, `CapitalizeFirstFilter`, `EnsurePrefixFilter`, `EnsureSuffixFilter`, `SubstringFilter`, `TrimLengthFilter`, `PadLeftFilter`, `PadRightFilter`, `ReplaceDiacriticsFilter`, `SlugifyFilter`, `NormalizeUnicodeFilter`.
- Numbers: `ClampFilter`, `RoundNumberFilter`, `CeilNumberFilter`, `FloorNumberFilter`, `AbsNumberFilter`, `ScaleNumberFilter`, `ToDecimalStringFilter`.
- Boolean: `ToBoolStrictFilter`, `NullIfTrueFilter`, `NullIfFalseFilter`.
- Arrays/Collections: `EachFilter`, `UniqueArrayFilter`, `SortArrayFilter`, `SortArrayByKeyFilter`, `ReverseArrayFilter`, `FilterKeysFilter`, `SliceArrayFilter`, `LimitArrayFilter`, `FlattenArrayFilter`, `ArrayCastFilter`.
- Date/Time: `ToTimezoneFilter`, `StartOfDayFilter`, `EndOfDayFilter`, `TruncateDateTimeFilter`, `AddIntervalFilter`, `SubIntervalFilter`, `ToUnixTimestampFilter`, `EnsureImmutableFilter`.
- Formatting: `JsonDecodeFilter`, `JsonEncodeFilter`, `UrlEncodeFilter`, `UrlDecodeFilter`, `HtmlEntitiesEncodeFilter`, `HtmlEntitiesDecodeFilter`.

Example of combining multiple filters:

```php
use Pocta\DataMapper\Attributes\Filters\{SlugifyFilter,TrimLengthFilter,EnsurePrefixFilter};

class Article
{
    // URL-friendly slug with prefix and trimming
    #[SlugifyFilter('-')]
    #[TrimLengthFilter(80, '…')]
    #[EnsurePrefixFilter('art-')]
    public string $slug;
}
```

Filters over arrays (process each item through filter):

```php
use Pocta\DataMapper\Attributes\Filters\{EachFilter,StringTrimFilter,UniqueArrayFilter,SortArrayFilter};

class Tags
{
    #[EachFilter(StringTrimFilter::class)]
    #[UniqueArrayFilter]
    #[SortArrayFilter]
    public array $tags = [];
}
```

Note: Filters are applied in declaration order. Each filter is null-safe and type-conservative (leaves unsupported types unchanged).

### Value Hydration (MapPropertyWithFunction)

Using the `MapPropertyWithFunction` attribute, you can "hydrate" a value with a custom function. The function is called with a single parameter (payload) and its return value is then type-processed by the mapper. Hydration occurs even if the key for the property is missing in the source JSON.

Payload modes (`HydrationMode`):

- `VALUE` – passes the current property value to the function
- `PARENT` – passes the parent payload (array for current object)
- `FULL` – passes the root payload (top-level input array)

Usage with string callable:

```php
use Pocta\DataMapper\Attributes\MapPropertyWithFunction;
use Pocta\DataMapper\Attributes\HydrationMode;

class User
{
    // Passes current email value to strtoupper
    #[MapPropertyWithFunction(function: 'strtoupper', mode: HydrationMode::VALUE)]
    public string $email;
}
```

Static method (callable-string):

```php
class Transformer
{
    public static function makeUsername(mixed $payload): string
    {
        // $payload is parent payload (e.g. ['first' => 'John', 'last' => 'Doe'])
        return strtolower(($payload['first'] ?? '') . '.' . ($payload['last'] ?? ''));
    }
}

class User
{
    public string $first;
    public string $last;

    #[MapPropertyWithFunction(function: Transformer::class . '::makeUsername', mode: HydrationMode::PARENT)]
    public string $username;
}
```

Array callable (e.g. `[self::class, 'method']`):

```php
class User
{
    #[MapPropertyWithFunction(function: [self::class, 'normalizeEmail'], mode: HydrationMode::VALUE)]
    public string $email;

    public static function normalizeEmail(mixed $value): string
    {
        return is_string($value) ? strtolower(trim($value)) : '';
    }
}
```

Hydration from root payload (`FULL`):

```php
class Profile
{
    public string $name;

    // Extracts e.g. meta.source from root payload
    #[MapPropertyWithFunction(function: [self::class, 'extractSource'], mode: HydrationMode::FULL)]
    public string $source;

    public static function extractSource(mixed $payload): string
    {
        return is_array($payload) ? (string)($payload['meta']['source'] ?? '') : '';
    }
}
```

Note: `MapPropertyWithFunction` is called before type conversion in denormalization (pre-denormalize). If a post-denormalize phase is needed (e.g. on already created `DateTimeInterface`), it can be added as an extension.

### Nullable Properties

```php
class Product
{
    #[MapProperty]
    private int $id;  // Required

    #[MapProperty]
    private ?string $description;  // Optional

    #[MapProperty]
    private ?int $stock;  // Optional
}

// JSON with null values
$json = '{"id": 1, "description": null, "stock": 100}';
$product = $mapper->fromJson($json, Product::class);

$product->getDescription();  // null
$product->getStock();        // 100
```

### Custom Property Names

```php
class Order
{
    #[MapProperty(name: 'order_id')]
    private int $id;

    #[MapProperty(name: 'customer_name')]
    private string $customerName;
}

// JSON uses different keys
$json = '{"order_id": 123, "customer_name": "Alice"}';
$order = $mapper->fromJson($json, Order::class);
```

### Explicit Type

```php
class Config
{
    // If JSON contains value as string but we want int
    #[MapProperty(type: 'int')]
    private int $port;

    // If JSON contains "1"/"0" as strings
    #[MapProperty(type: 'bool')]
    private bool $enabled;
}
```

## Automatic Type Conversion

The mapper can automatically convert values:

### Integer
- `"42"` → `42`
- `42.7` → `42`

### Boolean
- `"true"`, `"1"`, `1` → `true`
- `"false"`, `"0"`, `0`, `""` → `false`

### String
- Any scalar value → string

### Null Values
- `null` → `null` (only for nullable properties)
- Missing values → `null` or default value from constructor

## Event System

The Event System provides hooks for custom logic during mapping. You can listen to events and modify data or objects at various stages of the process.

### Available Events

#### 1. PreDenormalizeEvent
Triggered before denormalization (array → object):

```php
use Pocta\DataMapper\Events\PreDenormalizeEvent;

$mapper->addEventListener(PreDenormalizeEvent::class, function(PreDenormalizeEvent $event) {
    // Access data
    $data = $event->data;
    $className = $event->className;

    // Modify data before mapping
    $event->data['created_at'] = date('Y-m-d H:i:s');

    // Stop propagation (other listeners won't run)
    $event->stopPropagation();
});
```

#### 2. PostDenormalizeEvent
Triggered after successful denormalization:

```php
use Pocta\DataMapper\Events\PostDenormalizeEvent;

$mapper->addEventListener(PostDenormalizeEvent::class, function(PostDenormalizeEvent $event) {
    // Access created object
    $object = $event->object;
    $originalData = $event->originalData;

    // Modify object
    if ($object instanceof User) {
        $object->lastMappedAt = new DateTime();
    }

    // Replace object with another
    $event->setObject($modifiedObject);
});
```

#### 3. PreNormalizeEvent
Triggered before normalization (object → array):

```php
use Pocta\DataMapper\Events\PreNormalizeEvent;

$mapper->addEventListener(PreNormalizeEvent::class, function(PreNormalizeEvent $event) {
    $object = $event->object;

    // Modify object before conversion
    if ($object instanceof Product) {
        $object->price = round($object->price, 2);
    }
});
```

#### 4. PostNormalizeEvent
Triggered after normalization:

```php
use Pocta\DataMapper\Events\PostNormalizeEvent;

$mapper->addEventListener(PostNormalizeEvent::class, function(PostNormalizeEvent $event) {
    $data = $event->data;
    $originalObject = $event->originalObject;

    // Add extra data to output
    $event->data['_type'] = $event->getClassName();
    $event->data['_timestamp'] = time();
});
```

#### 5. DenormalizationErrorEvent
Triggered on error during denormalization:

```php
use Pocta\DataMapper\Events\DenormalizationErrorEvent;

$mapper->addEventListener(DenormalizationErrorEvent::class, function(DenormalizationErrorEvent $event) {
    $exception = $event->exception;
    $data = $event->data;
    $className = $event->className;

    // Error logging
    logger()->error("Mapping failed for {$className}", [
        'data' => $data,
        'error' => $exception->getMessage()
    ]);

    // Suppress exception (won't be re-thrown)
    // $event->suppressException();
});
```

#### 6. ValidationEvent
Triggered during validation:

```php
use Pocta\DataMapper\Events\ValidationEvent;

$mapper->addEventListener(ValidationEvent::class, function(ValidationEvent $event) {
    $object = $event->object;
    $errors = $event->errors;

    // Custom validation logic
    if ($object instanceof User && $object->age < 0) {
        $event->addError('age', 'Age cannot be negative');
    }

    // Remove error
    $event->removeError('someField');

    // Clear all errors
    // $event->clearErrors();
});
```

### Listener Priorities

Listeners are called according to priority (higher = earlier):

```php
// High priority (100) - called first
$mapper->addEventListener(PreDenormalizeEvent::class, function($event) {
    // ...
}, priority: 100);

// Medium priority (50)
$mapper->addEventListener(PreDenormalizeEvent::class, function($event) {
    // ...
}, priority: 50);

// Low priority (0) - default
$mapper->addEventListener(PreDenormalizeEvent::class, function($event) {
    // ...
});
```

### Practical Examples

#### Audit Logging

```php
$mapper->addEventListener(PostDenormalizeEvent::class, function($event) {
    auditLog()->log('object_created', [
        'class' => $event->className,
        'data' => $event->originalData,
        'user' => Auth::user()->id
    ]);
});
```

#### Data Sanitization

```php
$mapper->addEventListener(PreDenormalizeEvent::class, function($event) {
    // XSS protection
    array_walk_recursive($event->data, function(&$value) {
        if (is_string($value)) {
            $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
        }
    });
});
```

#### Error Tracking

```php
$mapper->addEventListener(DenormalizationErrorEvent::class, function($event) {
    // Bugsnag, Sentry, etc.
    bugsnag()->notifyException($event->exception, [
        'data' => $event->data,
        'class' => $event->className
    ]);
});
```

## Validation System

The Validation System provides declarative validation using Assert attributes directly on properties.

### Auto-validation

Automatic validation after denormalization:

```php
use Pocta\DataMapper\Validation\NotNull;
use Pocta\DataMapper\Validation\Range;
use Pocta\DataMapper\Validation\Email;

class User
{
    #[NotNull]
    #[Range(min: 1)]
    public int $id;

    #[NotNull]
    #[Email]
    public string $email;

    #[Range(min: 18, max: 120)]
    public int $age;
}

// Auto-validation enabled
$mapper = new Mapper(autoValidate: true);

try {
    $user = $mapper->fromArray(['id' => 0, 'email' => 'invalid'], User::class);
} catch (ValidationException $e) {
    $errors = $e->getErrors();
    // ['id' => "must be at least 1", 'email' => "must be valid email"]
}
```

### Strict Mode

Strict mode validates that input data contains only known keys defined in the target class. When enabled, any unknown keys will cause a `ValidationException`.

```php
// Enable strict mode in constructor
$mapper = new Mapper(strictMode: true);

class User
{
    public function __construct(
        public int $id,
        public string $name
    ) {}
}

// This will throw ValidationException because 'unknown_field' is not defined in User
try {
    $data = ['id' => 1, 'name' => 'John', 'unknown_field' => 'value'];
    $user = $mapper->fromArray($data, User::class);
} catch (ValidationException $e) {
    $errors = $e->getErrors();
    // ['unknown_field' => "Unknown key 'unknown_field' at path 'unknown_field' is not allowed in strict mode"]
}

// Works fine - all keys are known
$data = ['id' => 1, 'name' => 'John'];
$user = $mapper->fromArray($data, User::class); // OK

// Dynamic control
$mapper->setStrictMode(false); // Disable
$mapper->setStrictMode(true);  // Enable

if ($mapper->isStrictMode()) {
    // Strict mode is enabled
}
```

**Benefits:**
- Catches typos in input data keys
- Prevents accidental data leakage
- Ensures API contracts are strictly followed
- Useful for debugging and development

**Note:** By default, strict mode is disabled (`false`) to maintain backward compatibility.

### Manual Validation

```php
$mapper = new Mapper(); // autoValidate = false

$user = $mapper->fromArray($data, User::class);

// Validation without exception
$errors = $mapper->validate($user, throw: false);
if (!empty($errors)) {
    // Handle errors
}

// Validation with exception
try {
    $mapper->validate($user);
} catch (ValidationException $e) {
    // Handle validation errors
}
```

### Error Messages with Nested Paths

When validating nested objects and arrays of objects, error messages contain the full path to the erroneous field:

```php
class Address
{
    public function __construct(
        public string $street,
        public string $city,
        public string $country,
        public string $postalCode
    ) {}
}

class User
{
    public function __construct(
        public int $id,
        public string $name,
        #[MapProperty(arrayOf: Address::class)]
        public array $addresses
    ) {}
}

$data = [
    'id' => 1,
    'name' => 'John Doe',
    'addresses' => [
        [
            'street' => '123 Main St',
            'city' => 'New York',
            'country' => 'US',
            'postalCode' => '10001'
        ],
        [
            'street' => '456 Oak Ave',
            'city' => 'Los Angeles',
            // Missing 'country'!
            'postalCode' => '90001'
        ]
    ]
];

try {
    $user = $mapper->fromArray($data, User::class);
} catch (ValidationException $e) {
    $errors = $e->getErrors();
    // ['addresses[1].country' => "Missing required parameter 'country' at path 'addresses[1].country'"]

    // You can see exactly that the problem is in the second address (index 1), in the 'country' field
}
```

### Export to API Response Format

`ValidationException` provides a `toApiResponse()` method for structured JSON output:

```php
try {
    $user = $mapper->fromArray($data, User::class);
} catch (ValidationException $e) {
    // Export to structured format
    $response = $e->toApiResponse();

    // Result:
    // [
    //     'message' => 'Invalid request data',
    //     'code' => 422,
    //     'context' => [
    //         'validation' => [
    //             'addresses[1].country' => [
    //                 "Missing required parameter 'country' at path 'addresses[1].country'"
    //             ]
    //         ]
    //     ]
    // ]

    // Custom message and code
    $response = $e->toApiResponse('Validation failed', 400);

    // For API response
    return response()->json($response, $response['code']);
}
```

Benefits of this format:
- **Full paths**: `addresses[1].country` instead of just `country` - you know exactly where the problem is
- **Structured output**: Consistent format for all API responses
- **All errors at once**: Get all validation errors in one response
- **Array values**: Each field has an array of error messages, allowing multiple errors per field

### Available Validators

#### NotNull
```php
#[NotNull]
#[NotNull(message: 'Custom error message')]
public ?string $name;
```

#### Range
```php
#[Range(min: 0, max: 100)]
#[Range(min: 18)] // Only minimum
#[Range(max: 65)] // Only maximum
public int $age;
```

#### Length
```php
#[Length(min: 3, max: 50)]
#[Length(exact: 10)] // Exactly 10 characters
public string $username;
```

#### Email
```php
#[Email]
#[Email(message: 'Please enter valid email')]
public string $email;
```

#### Pattern (Regex)
```php
#[Pattern(pattern: '/^[A-Z]{3}\d{3}$/')]
#[Pattern(pattern: '/^\+\d{1,3}\s\d+$/', message: 'Invalid phone format')]
public string $code;
```

#### Positive
```php
#[Positive]
public int|float $amount;
```

#### Url
```php
#[Url]
public string $website;
```

#### Other Validators
- `Blank` - Must be empty string or null
- `NotBlank` - Must not be empty/blank
- `IsTrue` / `IsFalse` - Must be exactly true/false
- `IsNull` - Must be null
- `Type` - Must be of specific type
- `Json` - Must be valid JSON string
- `Hostname` - Must be valid hostname
- `Ip` - Must be valid IP address (supports V4/V6)
- Comparison validators: `EqualTo`, `NotEqualTo`, `IdenticalTo`, `GreaterThan`, `GreaterThanOrEqual`, `LessThan`, `LessThanOrEqual`
- Number validators: `Negative`, `NegativeOrZero`, `PositiveOrZero`, `DivisibleBy`
- Date/Time validators: `Date`, `DateTime`, `Time`, `Timezone`, `Week`
- `Choice` - Value must be one of allowed choices
- `Callback` - Custom validation function

### Combining Validators

You can combine multiple validators:

```php
class Product
{
    #[NotNull]
    #[Length(min: 3, max: 100)]
    public string $name;

    #[NotNull]
    #[Positive]
    #[Range(max: 1000000)]
    public float $price;
}
```

### Custom Validator

```php
use Pocta\DataMapper\Validation\AssertInterface;
use Attribute;

#[Attribute(Attribute::TARGET_PROPERTY | Attribute::TARGET_PARAMETER)]
class UniqueEmail implements AssertInterface
{
    public function validate(mixed $value, string $propertyName): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!is_string($value)) {
            return "Must be string";
        }

        // Custom logic
        if (User::where('email', $value)->exists()) {
            return "Email {$value} is already taken";
        }

        return null; // Valid
    }
}

// Usage
class User
{
    #[Email]
    #[UniqueEmail]
    public string $email;
}
```

## Cache System

Data Mapper contains an advanced cache system for performance optimization. Cache stores class metadata (reflection data), which significantly speeds up repeated mapping of the same classes.

### Basic Usage

```php
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\Cache\ArrayCache;

// Default: ArrayCache (in-memory cache for single request)
$mapper = new Mapper();

// Explicit ArrayCache
$cache = new ArrayCache();
$mapper = new Mapper(cache: $cache);

// Mapping - metadata is automatically cached
$user = $mapper->fromArray(['id' => 1, 'name' => 'John'], User::class);
```

### Available Cache Implementations

#### 1. ArrayCache (default)
In-memory cache, ideal for single-request caching:

```php
use Pocta\DataMapper\Cache\ArrayCache;

$cache = new ArrayCache();
$cache->set('key', 'value');
$value = $cache->get('key');        // 'value'
$exists = $cache->has('key');        // true
$size = $cache->size();              // Number of items
```

**Advantages**: Very fast, no dependencies
**Disadvantages**: Data is lost after request ends

#### 2. NullCache
Disable caching (for debugging):

```php
use Pocta\DataMapper\Cache\NullCache;

$mapper = new Mapper(cache: new NullCache());
```

### Custom Cache Implementation (PSR-16 compatible)

```php
use Pocta\DataMapper\Cache\CacheInterface;

class RedisCache implements CacheInterface
{
    public function __construct(private \Redis $redis) {}

    public function get(string $key, mixed $default = null): mixed
    {
        $value = $this->redis->get($key);
        return $value !== false ? unserialize($value) : $default;
    }

    public function set(string $key, mixed $value, ?int $ttl = null): bool
    {
        return $ttl === null
            ? $this->redis->set($key, serialize($value))
            : $this->redis->setex($key, $ttl, serialize($value));
    }

    public function has(string $key): bool
    {
        return $this->redis->exists($key) > 0;
    }

    public function delete(string $key): bool
    {
        return $this->redis->del($key) > 0;
    }

    public function clear(): bool
    {
        return $this->redis->flushDB();
    }
}

// Usage
$redis = new \Redis();
$redis->connect('127.0.0.1', 6379);
$mapper = new Mapper(cache: new RedisCache($redis));
```

### Cache Management

```php
// Clear cache for specific class
$mapper->clearCache(User::class);

// Clear entire cache
$mapper->clearCache();

// Access metadata factory
$factory = $mapper->getMetadataFactory();
$metadata = $factory->getMetadata(User::class);
```

### Performance Tips

1. **Production cache**: Use Redis/Memcached instead of ArrayCache
2. **Cache warmup**: Pre-generate metadata at application startup

```php
// Cache warmup
$classes = [User::class, Product::class, Order::class];
foreach ($classes as $class) {
    $mapper->getMetadataFactory()->getMetadata($class);
}
```

## Debug & Profiling

Data Mapper includes a powerful debug and profiling system for analyzing and optimizing the performance of your mapping operations.

### Basic Usage

```php
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\Debug\Debugger;
use Pocta\DataMapper\Debug\Profiler;

// Create debugger and profiler
$debugger = new Debugger(enabled: true, debugMode: true);
$profiler = new Profiler(enabled: true);

// Create mapper with debugger and profiler
$mapper = new Mapper(
    debugger: $debugger,
    profiler: $profiler
);

// Normal mapper usage
$user = $mapper->fromArray($data, User::class);
```

### Debugger - What It Logs and How to Get Data

The debugger records **all important operations** during mapping:

#### 1. Mapping Operations

**What it logs:**
- All calls to `fromArray()`, `toArray()`, `fromJson()`, `toJson()`
- Type of input data (array, object, string)
- Target class for denormalization

**How to get data:**

```php
// Get all logs
$logs = $debugger->getLogs();
// Result: [
//     ['type' => 'operation', 'operation' => 'fromArray', 'className' => 'User', 'dataType' => 'array', 'timestamp' => 1234567890.123],
//     ['type' => 'operation', 'operation' => 'toJson', 'className' => null, 'dataType' => 'object', 'timestamp' => 1234567890.456],
//     ...
// ]

// Get only mapping operations
$operations = $debugger->getLogsByType('operation');

// What it tells you:
// - How many times and when individual mapping methods were called
// - What data (types) you're working with
// - Which classes you map most frequently
```

#### 2. Event Tracking

**What it logs:**
- All dispatched events (PreDenormalizeEvent, PostDenormalizeEvent, etc.)
- Count of individual events

**How to get data:**

```php
// Get events
$events = $debugger->getLogsByType('event');

// Event statistics
$stats = $debugger->getEventStats();
// Result: [
//     'Pocta\DataMapper\Events\PreDenormalizeEvent' => 15,
//     'Pocta\DataMapper\Events\PostDenormalizeEvent' => 15,
//     'Pocta\DataMapper\Events\ValidationEvent' => 5,
//     ...
// ]

// What it tells you:
// - Which events are triggered and how often
// - How many active listeners you have
// - Whether your event listeners work correctly
```

#### 3. Metadata and Cache Info

**What it logs:**
- Metadata loading for individual classes
- Cache hits (metadata taken from cache)
- Cache misses (metadata had to be loaded)

**How to get data:**

```php
// Get metadata logs
$metadata = $debugger->getLogsByType('metadata');
// Result: [
//     ['type' => 'metadata', 'className' => 'User', 'fromCache' => false, 'propertyCount' => 5, 'timestamp' => ...],
//     ['type' => 'metadata', 'className' => 'User', 'fromCache' => true, 'propertyCount' => 5, 'timestamp' => ...],
//     ...
// ]

// Cache operations
$cache = $debugger->getLogsByType('cache');

// What it tells you:
// - Which classes are mapped most frequently
// - How efficiently cache works (how many hits vs. misses)
// - How many properties individual classes have
```

#### 4. Summary Overview

**How to get data:**

```php
$summary = $debugger->getSummary();
// Result: [
//     'totalLogs' => 150,              // Total number of records
//     'operations' => 50,              // Number of mapping operations
//     'events' => 80,                  // Number of events
//     'eventTypes' => 6,               // Number of different event types
//     'metadataLoads' => 15,           // Number of metadata loads
//     'cacheHits' => 35,               // Number of cache hits
//     'cacheMisses' => 15,             // Number of cache misses
//     'cacheHitRatio' => 70.0          // Cache hit ratio in %
// ]

// What it tells you:
// - Overall mapper activity
// - Cache efficiency (70% hit ratio = good!)
// - Where optimization is possible
```

#### Debug Mode - Detailed Output

```php
// Debug mode with output to STDERR
$debugger = new Debugger(enabled: true, debugMode: true);

// Each operation is printed:
// [DEBUG] Operation: fromArray -> User
// [DEBUG] Data: Array(...)
// [DEBUG] Event: Pocta\DataMapper\Events\PreDenormalizeEvent
// [DEBUG] Metadata [CACHE HIT]: User (5 properties)

// Change output stream
$file = fopen('/tmp/debug.log', 'w');
$debugger->setOutputStream($file);

// Disable debug mode
$debugger->setDebugMode(false);
```

### Profiler - Performance Measurement

The profiler **measures time and memory** of all operations:

#### 1. What It Measures

- **Operation time**: How long each operation takes (microsecond precision)
- **Memory usage**: How much memory each operation consumes
- **Call count**: How many times an operation was called
- **Averages**: Average time and memory per operation

**Tracked operations:**
- `fromJson` - JSON → object (including JSON parsing)
- `fromArray` - Array → object
- `toJson` - Object → JSON (including JSON encoding)
- `toArray` - Object → array
- `denormalize` - Denormalization itself (without pre/post events)
- `normalize` - Normalization itself
- `validation` - Object validation (if enabled)

#### 2. How to Get Data

```php
// Metrics for specific operation
$metrics = $profiler->getMetrics('fromArray');
// Result: [
//     'count' => 50,                   // Number of calls
//     'totalTime' => 0.234,            // Total time (seconds)
//     'totalMemory' => 1024000,        // Total memory (bytes)
//     'avgTime' => 0.00468,            // Average time (seconds)
//     'avgMemory' => 20480.0           // Average memory (bytes)
// ]

// What it tells you:
// - fromArray was called 50 times
// - Total took 234ms
// - Average 4.68ms per call
// - Average consumes 20KB memory
```

```php
// All metrics at once
$allMetrics = $profiler->getAllMetrics();
// Result: [
//     'fromArray' => ['count' => 50, 'totalTime' => 0.234, ...],
//     'toArray' => ['count' => 30, 'totalTime' => 0.156, ...],
//     'denormalize' => ['count' => 50, 'totalTime' => 0.189, ...],
//     ...
// ]

// What it tells you:
// - Overview of all operations
// - Which operation is slowest
// - Which operation consumes most memory
```

```php
// Summary statistics
$summary = $profiler->getSummary();
// Result: [
//     'totalOperations' => 130,        // Total number of operations
//     'totalTime' => 0.579,            // Total time (579ms)
//     'totalMemory' => 2560000,        // Total memory (2.56MB)
//     'peakMemory' => 12582912         // Peak memory (12MB)
// ]

// What it tells you:
// - Overall performance
// - Application memory footprint
// - Where to optimize (if totalTime is high)
```

#### 3. Formatted Report

```php
// Text report (human-readable)
$report = $profiler->getReport();
echo $report->toText();

// Output:
// === PROFILING REPORT ===
//
// Summary:
//   Total Operations: 130
//   Total Time: 579.00 ms
//   Total Memory: 2.44 MB
//   Peak Memory: 12.00 MB
//
// Detailed Metrics:
// ----------------------------------------------------------------------------------------------------
// Operation                                | Count    | Total Time      | Avg Time        | Avg Memory
// ----------------------------------------------------------------------------------------------------
// fromArray                                | 50       | 234.00 ms       | 4.68 ms         | 20.00 KB
// toArray                                  | 30       | 156.00 ms       | 5.20 ms         | 18.50 KB
// denormalize                              | 50       | 189.00 ms       | 3.78 ms         | 15.00 KB
// ----------------------------------------------------------------------------------------------------

// What it tells you:
// - Which operations are slowest
// - Where to optimize
// - What the memory overhead is
```

```php
// JSON report (for monitoring/logging)
$jsonReport = $report->toJson();
// Result: Structured JSON with metrics

// Array report (for programmatic processing)
$arrayReport = $report->toArray();

// What to do with it:
// - Save to log for long-term analysis
// - Send to monitoring system (Grafana, New Relic)
// - Compare performance between versions
```

#### 4. Sorting and Top Operations

```php
$report = $profiler->getReport();

// Top 5 slowest operations
$slowest = $report->getTopByTime(5);

// Top 5 operations with most memory
$memoryHeavy = $report->getTopByMemory(5);

// Sort by call count
$mostCalled = $report->getSortedByCount();

// What it tells you:
// - Which operations to optimize first
// - Where memory leaks are
// - Which operations are called unnecessarily often
```

#### 5. Custom Measurement

```php
// Measure custom operation
$profiler->start('custom_operation');
// ... your code ...
$profiler->stop('custom_operation');

// Or with callable
$result = $profiler->profile('my_task', function() {
    // Some heavy computation
    return expensiveOperation();
});

// Metrics
$metrics = $profiler->getMetrics('my_task');
```

### Practical Examples

#### Example 1: Performance Debugging

```php
$debugger = new Debugger(enabled: true);
$profiler = new Profiler(enabled: true);
$mapper = new Mapper(debugger: $debugger, profiler: $profiler);

// Your mapping
foreach ($bigDataset as $item) {
    $mapper->fromArray($item, Product::class);
}

// Analysis
$summary = $profiler->getSummary();
$cacheStats = $debugger->getSummary();

if ($summary['totalTime'] > 1.0) {
    echo "⚠️ Mapping is slow ({$summary['totalTime']}s)\n";

    $report = $profiler->getReport();
    echo $report->toText();

    // Cache problem?
    if ($cacheStats['cacheHitRatio'] < 50) {
        echo "💡 Cache hit ratio is low ({$cacheStats['cacheHitRatio']}%)\n";
        echo "   Consider using persistent cache (Redis/Memcached)\n";
    }
}
```

#### Example 2: Production Monitoring

```php
$profiler = new Profiler(enabled: true);
$mapper = new Mapper(profiler: $profiler);

// Your API endpoint
$data = processRequest();
$result = $mapper->fromArray($data, Response::class);

// Log to monitoring system
$metrics = $profiler->getSummary();
if ($metrics['totalTime'] > 0.1) {  // 100ms threshold
    logger()->warning('Slow mapper operation', [
        'time' => $metrics['totalTime'],
        'memory' => $metrics['totalMemory'],
        'operations' => $metrics['totalOperations']
    ]);
}
```

#### Example 3: Development Debugging

```php
// Enable only in dev mode
$debugger = new Debugger(
    enabled: $_ENV['APP_ENV'] === 'development',
    debugMode: true
);

$mapper = new Mapper(debugger: $debugger);

// Console output during development
// [DEBUG] Operation: fromArray -> User
// [DEBUG] Event: PreDenormalizeEvent
// [DEBUG] Metadata [CACHE MISS]: User (12 properties)
```

#### Example 4: Complete Analysis

```php
$debugger = new Debugger(enabled: true);
$profiler = new Profiler(enabled: true);
$mapper = new Mapper(
    autoValidate: true,
    debugger: $debugger,
    profiler: $profiler
);

// Your operation
$user = $mapper->fromArray($userData, User::class);

// === DEBUGGER ANALYSIS ===
echo "=== DEBUGGER REPORT ===\n";
$debugSummary = $debugger->getSummary();
echo "Total logs: {$debugSummary['totalLogs']}\n";
echo "Operations: {$debugSummary['operations']}\n";
echo "Events dispatched: {$debugSummary['events']}\n";
echo "Cache hit ratio: {$debugSummary['cacheHitRatio']}%\n\n";

// Event breakdown
echo "Event breakdown:\n";
foreach ($debugger->getEventStats() as $event => $count) {
    $shortName = substr($event, strrpos($event, '\\') + 1);
    echo "  - {$shortName}: {$count}×\n";
}

// === PROFILER ANALYSIS ===
echo "\n=== PROFILER REPORT ===\n";
$report = $profiler->getReport();
echo $report->toText();

// Specific metrics
if ($metrics = $profiler->getMetrics('validation')) {
    echo "\n⚠️ Validation takes: {$metrics['avgTime']}s per object\n";
}

// What the entire output tells you:
// 1. How many operations occurred
// 2. How efficient the cache is
// 3. Which events were triggered
// 4. How much time each operation takes
// 5. Where performance bottlenecks are
// 6. How much memory is consumed
```

### When to Use Debug vs. Profiling

**Use Debugger when:**
- ✅ You need to know **WHAT** is happening
- ✅ You want to see operation flow
- ✅ You're debugging event listeners
- ✅ You're analyzing cache efficiency
- ✅ You need an audit trail

**Use Profiler when:**
- ⏱️ You need to know **HOW FAST** it runs
- ⏱️ You're optimizing performance
- ⏱️ You're looking for memory leaks
- ⏱️ You're measuring impact of changes
- ⏱️ You're monitoring production performance

**Use both when:**
- 🔍 Complex performance problem
- 🔍 Optimizing entire system
- 🔍 Long-term monitoring
- 🔍 Production debugging

### Performance Overhead

- **Debugger**: Minimal (~1-2% overhead)
- **Profiler**: Minimal (~2-3% overhead)
- **Debug mode**: Medium (~5-10% overhead due to I/O)

**Recommendations:**
- ✅ Production: Profiler enabled, Debugger disabled
- ✅ Development: Both enabled with debug mode
- ✅ Testing: Both disabled (for clean metrics)

## Architecture

The library is built on clean architecture with separation of concerns:

### Data Types (Types)

Each supported data type has its own class that handles value conversion:

```php
use Pocta\DataMapper\Types\IntType;
use Pocta\DataMapper\Types\StringType;
use Pocta\DataMapper\Types\BoolType;
use Pocta\DataMapper\Types\TypeResolver;

// Register custom type
$typeResolver = new TypeResolver();

// Use type directly
$intType = new IntType();
$value = $intType->denormalize("42", "fieldName", false); // 42 (int)
$normalized = $intType->normalize(42); // 42 (int)
```

#### Implementing Custom Type

```php
use Pocta\DataMapper\Types\AbstractType;

class FloatType extends AbstractType
{
    public function getName(): string
    {
        return 'float';
    }

    public function getAliases(): array
    {
        return ['float', 'double'];
    }

    protected function denormalizeValue(mixed $value, string $fieldName): float
    {
        if (is_float($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        throw new \InvalidArgumentException(
            "Cannot cast value of field '{$fieldName}' to float"
        );
    }

    protected function normalizeValue(mixed $value): float
    {
        if (is_float($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return 0.0;
    }
}

// Register custom type
$typeResolver = new TypeResolver();
$typeResolver->registerType(new FloatType());

$denormalizer = new Denormalizer($typeResolver);
$normalizer = new Normalizer($typeResolver);
$mapper = new Mapper($denormalizer, $normalizer);
```

### Separate Components

```php
use Pocta\DataMapper\Normalizer\Normalizer;
use Pocta\DataMapper\Denormalizer\Denormalizer;
use Pocta\DataMapper\Types\TypeResolver;

// TypeResolver: Type management
$typeResolver = new TypeResolver();

// Normalizer: Object → Array/JSON
$normalizer = new Normalizer($typeResolver);
$array = $normalizer->normalize($object);

// Denormalizer: Array/JSON → Object
$denormalizer = new Denormalizer($typeResolver);
$object = $denormalizer->denormalize($array, User::class);

// Mapper: Facade combining both components
$mapper = new Mapper($denormalizer, $normalizer);
```

### Folder Structure

```
src/
├── Attributes/
│   ├── MapProperty.php          # Attribute for property mapping
│   ├── Filters/                 # 60+ filter attributes
│   └── ...
├── Cache/
│   ├── CacheInterface.php       # Cache interface
│   ├── ArrayCache.php           # In-memory cache
│   ├── NullCache.php            # No-op cache
│   └── ClassMetadataFactory.php # Metadata factory
├── Debug/
│   ├── Debugger.php             # Debug logger
│   ├── Profiler.php             # Performance profiler
│   └── ProfileReport.php        # Formatted reports
├── Denormalizer/
│   └── Denormalizer.php         # Data to object conversion
├── Events/
│   ├── EventInterface.php       # Event interface
│   ├── EventDispatcher.php      # Event dispatcher
│   └── *Event.php               # Event classes
├── Exceptions/
│   └── ValidationException.php  # Validation exception
├── Normalizer/
│   └── Normalizer.php           # Object to data conversion
├── Types/
│   ├── TypeInterface.php        # Type interface
│   ├── AbstractType.php         # Abstract base class
│   ├── IntType.php              # Integer type
│   ├── StringType.php           # String type
│   ├── BoolType.php             # Boolean type
│   └── TypeResolver.php         # Type manager
├── Validation/
│   ├── AssertInterface.php      # Validator interface
│   ├── Validator.php            # Validator
│   └── *Assert.php              # 30+ validator attributes
└── Mapper.php                   # Main facade
```

## Error Handling

```php
use InvalidArgumentException;
use JsonException;

try {
    $user = $mapper->fromJson($json, User::class);
} catch (JsonException $e) {
    // Invalid JSON format
} catch (InvalidArgumentException $e) {
    // Mapping error (invalid type, missing required field, etc.)
}
```

## Examples

### Round-trip Conversion

```php
$originalJson = '{"id": 1, "name": "Test", "active": true}';

// JSON → Object
$user = $mapper->fromJson($originalJson, User::class);

// Object → JSON
$newJson = $mapper->toJson($user);

// Result is equivalent to original JSON
```

### Working with Collections

```php
$usersData = [
    ['id' => 1, 'name' => 'Alice'],
    ['id' => 2, 'name' => 'Bob'],
    ['id' => 3, 'name' => 'Charlie']
];

$users = array_map(
    fn($data) => $mapper->fromArray($data, User::class),
    $usersData
);
```

## Testing

```bash
# Run all tests
composer test

# Run PHPStan (level 9)
composer phpstan
```

## Performance and Design

- **Minimal overhead**: Reflection used only where necessary
- **Type-safe**: Strict typing ensures data validity
- **Lazy initialization**: Properties initialized only when needed
- **SOLID principles**: Clean architecture with separation of concerns
- **Strategy pattern**: Data types as conversion strategies
- **Dependency Injection**: TypeResolver is injectable for testing

## License

MIT
