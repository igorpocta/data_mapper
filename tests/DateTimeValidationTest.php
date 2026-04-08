<?php

declare(strict_types=1);

namespace Tests;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Validation\DateTimeGreaterThan;
use Pocta\DataMapper\Validation\DateTimeLessThan;
use Pocta\DataMapper\Validation\DateTimeRange;
use Pocta\DataMapper\Validation\Validator;

class DateTimeValidationTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    // --- DateTimeGreaterThan ---

    public function testGreaterThanPassesWhenAfter(): void
    {
        $obj = new DateTimeGreaterThanTestClass();
        $obj->date = new DateTimeImmutable('2025-06-01');

        $errors = $this->validator->validate($obj, throw: false);
        $this->assertEmpty($errors);
    }

    public function testGreaterThanFailsWhenBefore(): void
    {
        $obj = new DateTimeGreaterThanTestClass();
        $obj->date = new DateTimeImmutable('2024-12-31');

        $errors = $this->validator->validate($obj, throw: false);
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('date', $errors);
    }

    public function testGreaterThanFailsWhenEqual(): void
    {
        $obj = new DateTimeGreaterThanTestClass();
        $obj->date = new DateTimeImmutable('2025-01-01 00:00:00');

        $errors = $this->validator->validate($obj, throw: false);
        $this->assertNotEmpty($errors);
    }

    public function testGreaterThanSkipsNull(): void
    {
        $obj = new DateTimeGreaterThanNullableTestClass();
        $obj->date = null;

        $errors = $this->validator->validate($obj, throw: false);
        $this->assertEmpty($errors);
    }

    // --- DateTimeLessThan ---

    public function testLessThanPassesWhenBefore(): void
    {
        $obj = new DateTimeLessThanTestClass();
        $obj->date = new DateTimeImmutable('2024-12-31');

        $errors = $this->validator->validate($obj, throw: false);
        $this->assertEmpty($errors);
    }

    public function testLessThanFailsWhenAfter(): void
    {
        $obj = new DateTimeLessThanTestClass();
        $obj->date = new DateTimeImmutable('2025-06-01');

        $errors = $this->validator->validate($obj, throw: false);
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('date', $errors);
    }

    public function testLessThanFailsWhenEqual(): void
    {
        $obj = new DateTimeLessThanTestClass();
        $obj->date = new DateTimeImmutable('2025-01-01 00:00:00');

        $errors = $this->validator->validate($obj, throw: false);
        $this->assertNotEmpty($errors);
    }

    public function testLessThanSkipsNull(): void
    {
        $obj = new DateTimeLessThanNullableTestClass();
        $obj->date = null;

        $errors = $this->validator->validate($obj, throw: false);
        $this->assertEmpty($errors);
    }

    // --- DateTimeRange ---

    public function testRangePassesWhenWithin(): void
    {
        $obj = new DateTimeRangeTestClass();
        $obj->date = new DateTimeImmutable('2025-06-15');

        $errors = $this->validator->validate($obj, throw: false);
        $this->assertEmpty($errors);
    }

    public function testRangePassesAtMinBoundary(): void
    {
        $obj = new DateTimeRangeTestClass();
        $obj->date = new DateTimeImmutable('2025-01-01 00:00:00');

        $errors = $this->validator->validate($obj, throw: false);
        $this->assertEmpty($errors);
    }

    public function testRangePassesAtMaxBoundary(): void
    {
        $obj = new DateTimeRangeTestClass();
        $obj->date = new DateTimeImmutable('2025-12-31 00:00:00');

        $errors = $this->validator->validate($obj, throw: false);
        $this->assertEmpty($errors);
    }

    public function testRangeFailsWhenBeforeMin(): void
    {
        $obj = new DateTimeRangeTestClass();
        $obj->date = new DateTimeImmutable('2024-12-31');

        $errors = $this->validator->validate($obj, throw: false);
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('date', $errors);
    }

    public function testRangeFailsWhenAfterMax(): void
    {
        $obj = new DateTimeRangeTestClass();
        $obj->date = new DateTimeImmutable('2026-01-01');

        $errors = $this->validator->validate($obj, throw: false);
        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('date', $errors);
    }

    public function testRangeWithOnlyMin(): void
    {
        $obj = new DateTimeRangeMinOnlyTestClass();
        $obj->date = new DateTimeImmutable('2020-01-01');

        $errors = $this->validator->validate($obj, throw: false);
        $this->assertNotEmpty($errors);

        $obj->date = new DateTimeImmutable('2025-06-01');
        $errors = $this->validator->validate($obj, throw: false);
        $this->assertEmpty($errors);
    }

    public function testRangeWithOnlyMax(): void
    {
        $obj = new DateTimeRangeMaxOnlyTestClass();
        $obj->date = new DateTimeImmutable('2030-01-01');

        $errors = $this->validator->validate($obj, throw: false);
        $this->assertNotEmpty($errors);

        $obj->date = new DateTimeImmutable('2024-06-01');
        $errors = $this->validator->validate($obj, throw: false);
        $this->assertEmpty($errors);
    }

    public function testRangeSkipsNull(): void
    {
        $obj = new DateTimeRangeNullableTestClass();
        $obj->date = null;

        $errors = $this->validator->validate($obj, throw: false);
        $this->assertEmpty($errors);
    }

    // --- Custom messages ---

    public function testCustomMessage(): void
    {
        $obj = new DateTimeCustomMessageTestClass();
        $obj->date = new DateTimeImmutable('2024-01-01');

        $errors = $this->validator->validate($obj, throw: false);
        $this->assertSame('Date must be in the future', $errors['date']);
    }

    // --- Non-DateTimeInterface value ---

    public function testGreaterThanFailsOnNonDateTime(): void
    {
        $validator = new DateTimeGreaterThan('2025-01-01');
        $error = $validator->validate('not-a-datetime', 'field');
        $this->assertNotNull($error);
    }

    public function testLessThanFailsOnNonDateTime(): void
    {
        $validator = new DateTimeLessThan('2025-01-01');
        $error = $validator->validate('not-a-datetime', 'field');
        $this->assertNotNull($error);
    }

    public function testRangeFailsOnNonDateTime(): void
    {
        $validator = new DateTimeRange(min: '2025-01-01');
        $error = $validator->validate('not-a-datetime', 'field');
        $this->assertNotNull($error);
    }
}

// --- Test DTO classes ---

class DateTimeGreaterThanTestClass
{
    #[DateTimeGreaterThan('2025-01-01')]
    public DateTimeImmutable $date;
}

class DateTimeGreaterThanNullableTestClass
{
    #[DateTimeGreaterThan('2025-01-01')]
    public ?DateTimeImmutable $date = null;
}

class DateTimeLessThanTestClass
{
    #[DateTimeLessThan('2025-01-01')]
    public DateTimeImmutable $date;
}

class DateTimeLessThanNullableTestClass
{
    #[DateTimeLessThan('2025-01-01')]
    public ?DateTimeImmutable $date = null;
}

class DateTimeRangeTestClass
{
    #[DateTimeRange(min: '2025-01-01', max: '2025-12-31')]
    public DateTimeImmutable $date;
}

class DateTimeRangeMinOnlyTestClass
{
    #[DateTimeRange(min: '2025-01-01')]
    public DateTimeImmutable $date;
}

class DateTimeRangeMaxOnlyTestClass
{
    #[DateTimeRange(max: '2025-12-31')]
    public DateTimeImmutable $date;
}

class DateTimeRangeNullableTestClass
{
    #[DateTimeRange(min: '2025-01-01', max: '2025-12-31')]
    public ?DateTimeImmutable $date = null;
}

class DateTimeCustomMessageTestClass
{
    #[DateTimeGreaterThan('2025-01-01', message: 'Date must be in the future')]
    public DateTimeImmutable $date;
}
