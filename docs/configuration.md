# Configuration (MapperOptions)

[← Back to README](../README.md)

The `MapperOptions` class provides a clean way to configure the Mapper behavior.

## Available Options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `autoValidate` | `bool` | `false` | Automatically validate objects after denormalization |
| `strictMode` | `bool` | `false` | Throw validation error if unknown keys are present in input |
| `throwOnMissingData` | `bool` | `true` | Throw exception when required data is missing |
| `skipNullValues` | `bool` | `false` | Skip null values during normalization (don't include in output) |
| `preserveNumericStrings` | `bool` | `false` | Keep numeric strings as strings instead of converting to numbers |

## Factory Methods

```php
// Quick configurations for common scenarios
MapperOptions::withAutoValidation()  // Enable auto-validation only
MapperOptions::withStrictMode()      // Enable strict mode only
MapperOptions::strict()              // Enable both auto-validation and strict mode
MapperOptions::development()         // Strict validation for development
MapperOptions::production()          // Lenient configuration for production
```

## Custom Configuration

```php
$options = new MapperOptions(
    autoValidate: true,
    strictMode: true,
    skipNullValues: true
);

$mapper = new Mapper($options);
```

## Immutable Updates

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
- Input format support: ISO 8601, RFC 3339, MySQL datetime, custom formats via `format` parameter
- Output format: Configurable via `outputFormat` parameter (default: ISO 8601 with microseconds)
- Timezones: Automatic conversion between timezones

### Enum (PHP 8.1+)
- `BackedEnum` - Enum with values (string or int)
- `UnitEnum` - Simple enum without values

### Complex Types
- `array` - Array with arbitrary content
- `array<int>`, `array<string>`, `array<float>`, `array<bool>` - Array of scalars using `arrayOf` attribute (use `ArrayElementType` enum)
- `array<ClassName>` - Array of objects using `arrayOf` attribute
- Custom objects - Nested objects of arbitrary depth

### Nullable Types
All types support nullable variants:
- `?int`, `?string`, `?bool`
- `?DateTimeImmutable`, `?DateTime`
- `?MyCustomClass`
