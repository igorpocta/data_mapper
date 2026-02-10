<?php

declare(strict_types = 1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Mapper;
use Tests\Fixtures\UserWithConstructor;

class ConstructorPropertiesTest extends TestCase
{
	private Mapper $mapper;

	protected function setUp(): void
	{
		$this->mapper = new Mapper();
	}

	public function testFromArrayWithConstructorProperties(): void
	{
		$data = ['id' => 1, 'name' => 'John Doe', 'active' => true, 'email' => 'test@example.com'];

		$user = $this->mapper->fromArray($data, UserWithConstructor::class);

		$this->assertInstanceOf(UserWithConstructor::class, $user);
		$this->assertSame($data['id'], $user->getId());
		$this->assertSame($data['name'], $user->getName());
		$this->assertTrue($user->isActive());
	}

	public function testFromArrayWithConstructorDefaultValue(): void
	{
		$data = ['id' => 2, 'name' => 'Jane Doe', 'email' => 'test@example.com'];

		$user = $this->mapper->fromArray($data, UserWithConstructor::class);

		$this->assertSame($data['id'], $user->getId());
		$this->assertSame($data['name'], $user->getName());
		$this->assertTrue($user->isActive()); // Default value
	}

	public function testFromArrayWithConstructorAndNonConstructorProperty(): void
	{
		$data = ['id' => 3, 'name' => 'Bob', 'active' => false, 'email' => 'bob@example.com'];

		$user = $this->mapper->fromArray($data, UserWithConstructor::class);

		$this->assertSame($data['id'], $user->getId());
		$this->assertSame($data['name'], $user->getName());
		$this->assertFalse($user->isActive());
		$this->assertSame($data['email'], $user->getEmail());
	}

	public function testToArrayWithConstructorProperties(): void
	{
		$inputData = ['id' => 5, 'name' => 'Alice', 'active' => true, 'email' => 'alice@example.com'];
		$user = $this->mapper->fromArray($inputData, UserWithConstructor::class);

		$outputData = $this->mapper->toArray($user);

		$this->assertSame($inputData['id'], $outputData['id']);
		$this->assertSame($inputData['name'], $outputData['name']);
		$this->assertTrue($outputData['active']);
		$this->assertSame($inputData['email'], $outputData['email']);
	}

	public function testFromArrayWithMultipleProperties(): void
	{
		$data = ['id' => 10, 'name' => 'Charlie', 'active' => false, 'email' => 'test@example.com'];

		$user = $this->mapper->fromArray($data, UserWithConstructor::class);

		$this->assertSame($data['id'], $user->getId());
		$this->assertSame($data['name'], $user->getName());
		$this->assertFalse($user->isActive());
	}

	public function testToArrayConvertsCorrectly(): void
	{
		$inputData = ['id' => 11, 'name' => 'Diana', 'active' => true, 'email' => 'diana@example.com'];
		$user = $this->mapper->fromArray($inputData, UserWithConstructor::class);

		$outputData = $this->mapper->toArray($user);

		$this->assertSame($inputData['id'], $outputData['id']);
		$this->assertSame($inputData['name'], $outputData['name']);
		$this->assertTrue($outputData['active']);
		$this->assertSame($inputData['email'], $outputData['email']);
	}

	public function testRoundTripConversion(): void
	{
		$originalData = ['id' => 20, 'name' => 'Round Trip', 'active' => false, 'email' => 'round@example.com'];

		$user = $this->mapper->fromArray($originalData, UserWithConstructor::class);
		$resultData = $this->mapper->toArray($user);

		$this->assertSame($originalData['id'], $resultData['id']);
		$this->assertSame($originalData['name'], $resultData['name']);
		$this->assertSame($originalData['active'], $resultData['active']);
		$this->assertSame($originalData['email'], $resultData['email']);
	}

	public function testFromJsonStillWorks(): void
	{
		$data = ['id' => 30, 'name' => 'JSON Test', 'active' => true, 'email' => 'test@example.com'];
		$json = json_encode($data, JSON_THROW_ON_ERROR);

		$user = $this->mapper->fromJson($json, UserWithConstructor::class);

		$this->assertSame($data['id'], $user->getId());
		$this->assertSame($data['name'], $user->getName());
		$this->assertTrue($user->isActive());
	}

	public function testToJsonStillWorks(): void
	{
		$data = ['id' => 31, 'name' => 'JSON Output', 'active' => false, 'email' => 'json@test.com'];
		$user = $this->mapper->fromArray($data, UserWithConstructor::class);

		$json = $this->mapper->toJson($user);
		$resultData = json_decode($json, true);

		$this->assertIsArray($resultData);
		$this->assertSame($data['id'], $resultData['id']);
		$this->assertSame($data['name'], $resultData['name']);
		$this->assertFalse($resultData['active']);
		$this->assertSame($data['email'], $resultData['email']);
	}
}
