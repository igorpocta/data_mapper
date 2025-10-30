<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\MapperOptions;
use Pocta\DataMapper\Exceptions\ValidationException;
use Pocta\DataMapper\Validation;

class NewValidatorsTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper(MapperOptions::withAutoValidation());
    }

    public function testBlankValidator(): void
    {
        $data = ['value' => ''];
        $object = $this->mapper->fromArray($data, BlankTestClass::class);
        $this->assertSame('', $object->value);

        $this->expectException(ValidationException::class);
        $this->mapper->fromArray(['value' => 'not blank'], BlankTestClass::class);
    }

    public function testNotBlankValidator(): void
    {
        $data = ['value' => 'something'];
        $object = $this->mapper->fromArray($data, NotBlankTestClass::class);
        $this->assertSame('something', $object->value);

        $this->expectException(ValidationException::class);
        $this->mapper->fromArray(['value' => ''], NotBlankTestClass::class);
    }

    public function testIsTrueValidator(): void
    {
        $data = ['value' => true];
        $object = $this->mapper->fromArray($data, IsTrueTestClass::class);
        $this->assertTrue($object->value);

        $this->expectException(ValidationException::class);
        $this->mapper->fromArray(['value' => false], IsTrueTestClass::class);
    }

    public function testIsFalseValidator(): void
    {
        $data = ['value' => false];
        $object = $this->mapper->fromArray($data, IsFalseTestClass::class);
        $this->assertFalse($object->value);

        $this->expectException(ValidationException::class);
        $this->mapper->fromArray(['value' => true], IsFalseTestClass::class);
    }

    public function testIsNullValidator(): void
    {
        $data = ['value' => null];
        $object = $this->mapper->fromArray($data, IsNullTestClass::class);
        $this->assertNull($object->value);

        $this->expectException(ValidationException::class);
        $this->mapper->fromArray(['value' => 'not null'], IsNullTestClass::class);
    }

    public function testTypeValidator(): void
    {
        $data = ['value' => 'string'];
        $object = $this->mapper->fromArray($data, TypeTestClass::class);
        $this->assertSame('string', $object->value);

        // Type validator checks after conversion, so if mapper converts int to string
        // validation will pass. Test with array instead which won't auto-convert.
        $this->expectException(ValidationException::class);
        $this->mapper->fromArray(['value' => []], TypeTestClass::class);
    }

    public function testJsonValidator(): void
    {
        $data = ['value' => '{"key":"value"}'];
        $object = $this->mapper->fromArray($data, JsonTestClass::class);
        $this->assertSame('{"key":"value"}', $object->value);

        $this->expectException(ValidationException::class);
        $this->mapper->fromArray(['value' => 'not json'], JsonTestClass::class);
    }

    public function testHostnameValidator(): void
    {
        $data = ['value' => 'example.com'];
        $object = $this->mapper->fromArray($data, HostnameTestClass::class);
        $this->assertSame('example.com', $object->value);

        $this->expectException(ValidationException::class);
        $this->mapper->fromArray(['value' => 'not a hostname!'], HostnameTestClass::class);
    }

    public function testIpValidator(): void
    {
        $data = ['value' => '192.168.1.1'];
        $object = $this->mapper->fromArray($data, IpTestClass::class);
        $this->assertSame('192.168.1.1', $object->value);

        $this->expectException(ValidationException::class);
        $this->mapper->fromArray(['value' => 'not an ip'], IpTestClass::class);
    }

    public function testEqualToValidator(): void
    {
        $data = ['value' => 10];
        $object = $this->mapper->fromArray($data, EqualToTestClass::class);
        $this->assertSame(10, $object->value);

        $this->expectException(ValidationException::class);
        $this->mapper->fromArray(['value' => 5], EqualToTestClass::class);
    }

    public function testGreaterThanValidator(): void
    {
        $data = ['value' => 15];
        $object = $this->mapper->fromArray($data, GreaterThanTestClass::class);
        $this->assertSame(15, $object->value);

        $this->expectException(ValidationException::class);
        $this->mapper->fromArray(['value' => 5], GreaterThanTestClass::class);
    }

    public function testLessThanValidator(): void
    {
        $data = ['value' => 5];
        $object = $this->mapper->fromArray($data, LessThanTestClass::class);
        $this->assertSame(5, $object->value);

        $this->expectException(ValidationException::class);
        $this->mapper->fromArray(['value' => 15], LessThanTestClass::class);
    }

    public function testNegativeValidator(): void
    {
        $data = ['value' => -5];
        $object = $this->mapper->fromArray($data, NegativeTestClass::class);
        $this->assertSame(-5, $object->value);

        $this->expectException(ValidationException::class);
        $this->mapper->fromArray(['value' => 5], NegativeTestClass::class);
    }

    public function testPositiveOrZeroValidator(): void
    {
        $data = ['value' => 0];
        $object = $this->mapper->fromArray($data, PositiveOrZeroTestClass::class);
        $this->assertSame(0, $object->value);

        $this->expectException(ValidationException::class);
        $this->mapper->fromArray(['value' => -5], PositiveOrZeroTestClass::class);
    }

    public function testDivisibleByValidator(): void
    {
        $data = ['value' => 10];
        $object = $this->mapper->fromArray($data, DivisibleByTestClass::class);
        $this->assertSame(10, $object->value);

        $this->expectException(ValidationException::class);
        $this->mapper->fromArray(['value' => 7], DivisibleByTestClass::class);
    }

    public function testDateValidator(): void
    {
        $data = ['value' => '2024-10-29'];
        $object = $this->mapper->fromArray($data, DateTestClass::class);
        $this->assertSame('2024-10-29', $object->value);

        $this->expectException(ValidationException::class);
        $this->mapper->fromArray(['value' => 'not a date'], DateTestClass::class);
    }

    public function testTimeValidator(): void
    {
        $data = ['value' => '14:30:00'];
        $object = $this->mapper->fromArray($data, TimeTestClass::class);
        $this->assertSame('14:30:00', $object->value);

        $this->expectException(ValidationException::class);
        $this->mapper->fromArray(['value' => 'not a time'], TimeTestClass::class);
    }

    public function testTimezoneValidator(): void
    {
        $data = ['value' => 'Europe/Prague'];
        $object = $this->mapper->fromArray($data, NewTimezoneTestClass::class);
        $this->assertSame('Europe/Prague', $object->value);

        $this->expectException(ValidationException::class);
        $this->mapper->fromArray(['value' => 'Invalid/Timezone'], NewTimezoneTestClass::class);
    }

    public function testWeekValidator(): void
    {
        $data = ['value' => '2024-W43'];
        $object = $this->mapper->fromArray($data, WeekTestClass::class);
        $this->assertSame('2024-W43', $object->value);

        $this->expectException(ValidationException::class);
        $this->mapper->fromArray(['value' => 'not a week'], WeekTestClass::class);
    }

    public function testChoiceValidator(): void
    {
        $data = ['value' => 'red'];
        $object = $this->mapper->fromArray($data, ChoiceTestClass::class);
        $this->assertSame('red', $object->value);

        $this->expectException(ValidationException::class);
        $this->mapper->fromArray(['value' => 'purple'], ChoiceTestClass::class);
    }

    public function testCallbackValidator(): void
    {
        $data = ['value' => 'validuser'];
        $object = $this->mapper->fromArray($data, CallbackTestClass::class);
        $this->assertSame('validuser', $object->value);

        $this->expectException(ValidationException::class);
        $this->mapper->fromArray(['value' => 'admin'], CallbackTestClass::class);
    }
}

// Test classes
class BlankTestClass
{
    #[Validation\Blank]
    public string $value;
}

class NotBlankTestClass
{
    #[Validation\NotBlank]
    public string $value;
}

class IsTrueTestClass
{
    #[Validation\IsTrue]
    public bool $value;
}

class IsFalseTestClass
{
    #[Validation\IsFalse]
    public bool $value;
}

class IsNullTestClass
{
    #[Validation\IsNull]
    public ?string $value;
}

class TypeTestClass
{
    #[Validation\Type(type: 'string')]
    public string $value;
}

class JsonTestClass
{
    #[Validation\Json]
    public string $value;
}

class HostnameTestClass
{
    #[Validation\Hostname]
    public string $value;
}

class IpTestClass
{
    #[Validation\Ip]
    public string $value;
}

class EqualToTestClass
{
    #[Validation\EqualTo(value: 10)]
    public int $value;
}

class GreaterThanTestClass
{
    #[Validation\GreaterThan(value: 10)]
    public int $value;
}

class LessThanTestClass
{
    #[Validation\LessThan(value: 10)]
    public int $value;
}

class NegativeTestClass
{
    #[Validation\Negative]
    public int $value;
}

class PositiveOrZeroTestClass
{
    #[Validation\PositiveOrZero]
    public int $value;
}

class DivisibleByTestClass
{
    #[Validation\DivisibleBy(value: 5)]
    public int $value;
}

class DateTestClass
{
    #[Validation\Date]
    public string $value;
}

class TimeTestClass
{
    #[Validation\Time]
    public string $value;
}

class NewTimezoneTestClass
{
    #[Validation\Timezone]
    public string $value;
}

class WeekTestClass
{
    #[Validation\Week]
    public string $value;
}

class ChoiceTestClass
{
    #[Validation\Choice(choices: ['red', 'green', 'blue'])]
    public string $value;
}

class CallbackTestClass
{
    #[Validation\Callback(callback: [CallbackTestClass::class, 'validateUsername'])]
    public string $value;

    public static function validateUsername(mixed $value, string $propertyName): ?string
    {
        if ($value === 'admin') {
            return "Username cannot be 'admin'";
        }
        return null;
    }
}
