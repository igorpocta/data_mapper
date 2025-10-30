<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Pocta\DataMapper\Validation\Validator;
use Pocta\DataMapper\Validation\NotNull;
use Pocta\DataMapper\Validation\Range;
use Pocta\DataMapper\Validation\Length;
use Pocta\DataMapper\Validation\Email;
use Pocta\DataMapper\Validation\Pattern;
use Pocta\DataMapper\Validation\Positive;
use Pocta\DataMapper\Validation\Url;
use Pocta\DataMapper\Exceptions\ValidationException;

class ValidationTest extends TestCase
{
    public function testNotNullValidator(): void
    {
        $validator = new Validator();
        $obj = new NotNullTestClass();
        $obj->name = null;

        $errors = $validator->validate($obj, throw: false);
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('name', $errors);
    }

    public function testRangeValidator(): void
    {
        $validator = new Validator();

        $obj = new RangeTestClass();
        $obj->age = 150; // Too high

        $errors = $validator->validate($obj, throw: false);
        $this->assertNotEmpty($errors);

        $obj->age = 25; // Valid
        $errors = $validator->validate($obj, throw: false);
        $this->assertEmpty($errors);
    }

    public function testLengthValidator(): void
    {
        $validator = new Validator();

        $obj = new LengthTestClass();
        $obj->username = 'ab'; // Too short

        $errors = $validator->validate($obj, throw: false);
        $this->assertNotEmpty($errors);

        $obj->username = 'validuser'; // Valid
        $errors = $validator->validate($obj, throw: false);
        $this->assertEmpty($errors);
    }

    public function testEmailValidator(): void
    {
        $validator = new Validator();

        $obj = new EmailTestClass();
        $obj->email = 'invalid-email';

        $errors = $validator->validate($obj, throw: false);
        $this->assertNotEmpty($errors);

        $obj->email = 'valid@example.com';
        $errors = $validator->validate($obj, throw: false);
        $this->assertEmpty($errors);
    }

    public function testPatternValidator(): void
    {
        $validator = new Validator();

        $obj = new PatternTestClass();
        $obj->code = '12345'; // Should be letters only

        $errors = $validator->validate($obj, throw: false);
        $this->assertNotEmpty($errors);

        $obj->code = 'ABCDE';
        $errors = $validator->validate($obj, throw: false);
        $this->assertEmpty($errors);
    }

    public function testPositiveValidator(): void
    {
        $validator = new Validator();

        $obj = new PositiveTestClass();
        $obj->amount = -10;

        $errors = $validator->validate($obj, throw: false);
        $this->assertNotEmpty($errors);

        $obj->amount = 100;
        $errors = $validator->validate($obj, throw: false);
        $this->assertEmpty($errors);
    }

    public function testUrlValidator(): void
    {
        $validator = new Validator();

        $obj = new UrlTestClass();
        $obj->website = 'not-a-url';

        $errors = $validator->validate($obj, throw: false);
        $this->assertNotEmpty($errors);

        $obj->website = 'https://example.com';
        $errors = $validator->validate($obj, throw: false);
        $this->assertEmpty($errors);
    }

    public function testAutoValidation(): void
    {
        $mapper = new Mapper(autoValidate: true);

        $this->expectException(ValidationException::class);

        // This should fail validation
        $mapper->fromArray(['age' => 150], RangeTestClass::class);
    }

    public function testAutoValidationWithFromJson(): void
    {
        $mapper = new Mapper(autoValidate: true);

        $this->expectException(ValidationException::class);

        // This should fail validation
        $json = json_encode(['age' => 150]);
        assert(is_string($json)); // Ensure json_encode succeeded
        $mapper->fromJson($json, RangeTestClass::class);
    }

    public function testManualValidation(): void
    {
        $mapper = new Mapper(); // autoValidate = false

        $obj = $mapper->fromArray(['age' => 150], RangeTestClass::class);

        // Should throw because age is invalid
        $this->expectException(ValidationException::class);
        $mapper->validate($obj);
    }

    public function testValidationWithMultipleRules(): void
    {
        $validator = new Validator();

        $obj = new MultiValidationTestClass();
        $obj->email = null; // Fails both NotNull and Email

        $errors = $validator->validate($obj, throw: false);
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('email', $errors);
    }

    public function testValidatorIsValid(): void
    {
        $validator = new Validator();

        $obj = new RangeTestClass();
        $obj->age = 25;

        $this->assertTrue($validator->isValid($obj));

        $obj->age = 150;
        $this->assertFalse($validator->isValid($obj));
    }

    public function testCustomValidationMessage(): void
    {
        $validator = new Validator();

        $obj = new CustomMessageTestClass();
        $obj->name = null;

        $errors = $validator->validate($obj, throw: false);
        $this->assertStringContainsString('Custom error', $errors['name']);
    }
}

class NotNullTestClass
{
    #[NotNull]
    public ?string $name = null;
}

class RangeTestClass
{
    #[Range(min: 18, max: 120)]
    public int $age;
}

class LengthTestClass
{
    #[Length(min: 3, max: 20)]
    public string $username;
}

class EmailTestClass
{
    #[Email]
    public string $email;
}

class PatternTestClass
{
    #[Pattern(pattern: '/^[A-Z]+$/')]
    public string $code;
}

class PositiveTestClass
{
    #[Positive]
    public int $amount;
}

class UrlTestClass
{
    #[Url]
    public string $website;
}

class MultiValidationTestClass
{
    #[NotNull]
    #[Email]
    public ?string $email = null;
}

class CustomMessageTestClass
{
    #[NotNull(message: 'Custom error message')]
    public ?string $name = null;
}
