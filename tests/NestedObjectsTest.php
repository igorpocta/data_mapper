<?php

declare(strict_types=1);

namespace Tests;

use PHPUnit\Framework\TestCase;
use Pocta\DataMapper\Exceptions\ValidationException;
use Pocta\DataMapper\Mapper;
use Tests\Fixtures\Address;
use Tests\Fixtures\UserWithAddress;
use Tests\Fixtures\Post;
use Tests\Fixtures\Tag;
use Tests\Fixtures\StrictPost;
use Tests\Fixtures\StrictTag;

class NestedObjectsTest extends TestCase
{
    private Mapper $mapper;

    protected function setUp(): void
    {
        $this->mapper = new Mapper();
    }

    public function testFromArrayWithNestedObject(): void
    {
        $data = [
            'id' => 1,
            'name' => 'John Doe',
            'address' => [
                'street' => '123 Main St',
                'city' => 'New York',
                'zipCode' => '10001'
            ]
        ];

        $user = $this->mapper->fromArray($data, UserWithAddress::class);

        $this->assertInstanceOf(UserWithAddress::class, $user);
        $this->assertSame(1, $user->getId());
        $this->assertSame('John Doe', $user->getName());
        $this->assertInstanceOf(Address::class, $user->getAddress());
        $this->assertSame('123 Main St', $user->getAddress()->getStreet());
        $this->assertSame('New York', $user->getAddress()->getCity());
        $this->assertSame('10001', $user->getAddress()->getZipCode());
    }

    public function testToArrayWithNestedObject(): void
    {
        $address = new Address();
        $address->setStreet('456 Oak Ave');
        $address->setCity('Los Angeles');
        $address->setZipCode('90001');

        $user = new UserWithAddress();
        $user->setId(2);
        $user->setName('Jane Smith');
        $user->setAddress($address);

        $data = $this->mapper->toArray($user);

        $this->assertSame(2, $data['id']);
        $this->assertSame('Jane Smith', $data['name']);
        $this->assertIsArray($data['address']);
        $this->assertSame('456 Oak Ave', $data['address']['street']);
        $this->assertSame('Los Angeles', $data['address']['city']);
        $this->assertSame('90001', $data['address']['zipCode']);
    }

    public function testRoundTripWithNestedObject(): void
    {
        $originalData = [
            'id' => 3,
            'name' => 'Bob Johnson',
            'address' => [
                'street' => '789 Pine Rd',
                'city' => 'Chicago',
                'zipCode' => '60601'
            ]
        ];

        $user = $this->mapper->fromArray($originalData, UserWithAddress::class);
        $resultData = $this->mapper->toArray($user);

        $this->assertSame($originalData['id'], $resultData['id']);
        $this->assertSame($originalData['name'], $resultData['name']);
        $this->assertIsArray($resultData['address']);
        $this->assertSame($originalData['address']['street'], $resultData['address']['street']);
        $this->assertSame($originalData['address']['city'], $resultData['address']['city']);
        $this->assertSame($originalData['address']['zipCode'], $resultData['address']['zipCode']);
    }

    public function testFromArrayWithArrayOfObjects(): void
    {
        $data = [
            'id' => 1,
            'title' => 'My First Post',
            'content' => 'This is the content',
            'tags' => [
                ['id' => 1, 'name' => 'PHP'],
                ['id' => 2, 'name' => 'Programming'],
                ['id' => 3, 'name' => 'Tutorial']
            ]
        ];

        $post = $this->mapper->fromArray($data, Post::class);

        $this->assertInstanceOf(Post::class, $post);
        $this->assertSame(1, $post->getId());
        $this->assertSame('My First Post', $post->getTitle());
        $tags = $post->getTags();
        $this->assertCount(3, $tags);

        $this->assertInstanceOf(Tag::class, $post->getTags()[0]);
        $this->assertSame(1, $post->getTags()[0]->getId());
        $this->assertSame('PHP', $post->getTags()[0]->getName());

        $this->assertInstanceOf(Tag::class, $post->getTags()[1]);
        $this->assertSame(2, $post->getTags()[1]->getId());
        $this->assertSame('Programming', $post->getTags()[1]->getName());

        $this->assertInstanceOf(Tag::class, $post->getTags()[2]);
        $this->assertSame(3, $post->getTags()[2]->getId());
        $this->assertSame('Tutorial', $post->getTags()[2]->getName());
    }

    public function testToArrayWithArrayOfObjects(): void
    {
        $tag1 = new Tag();
        $tag1->setId(1);
        $tag1->setName('JavaScript');

        $tag2 = new Tag();
        $tag2->setId(2);
        $tag2->setName('Web Development');

        $post = new Post();
        $post->setId(2);
        $post->setTitle('Second Post');
        $post->setContent('Another content');
        $post->setTags([$tag1, $tag2]);

        $data = $this->mapper->toArray($post);

        $this->assertSame(2, $data['id']);
        $this->assertSame('Second Post', $data['title']);
        $this->assertIsArray($data['tags']);
        $this->assertCount(2, $data['tags']);

        $this->assertIsArray($data['tags'][0]);
        $this->assertSame(1, $data['tags'][0]['id']);
        $this->assertSame('JavaScript', $data['tags'][0]['name']);

        $this->assertIsArray($data['tags'][1]);
        $this->assertSame(2, $data['tags'][1]['id']);
        $this->assertSame('Web Development', $data['tags'][1]['name']);
    }

    public function testRoundTripWithArrayOfObjects(): void
    {
        $originalData = [
            'id' => 3,
            'title' => 'Third Post',
            'content' => 'Content here',
            'tags' => [
                ['id' => 10, 'name' => 'Database'],
                ['id' => 20, 'name' => 'SQL']
            ]
        ];

        $post = $this->mapper->fromArray($originalData, Post::class);
        $resultData = $this->mapper->toArray($post);

        $this->assertSame($originalData['id'], $resultData['id']);
        $this->assertSame($originalData['title'], $resultData['title']);
        $this->assertSame($originalData['content'], $resultData['content']);
        $this->assertIsArray($resultData['tags']);
        $this->assertCount(2, $resultData['tags']);
        $this->assertIsArray($resultData['tags'][0]);
        $this->assertIsArray($resultData['tags'][1]);
        $this->assertSame($originalData['tags'][0]['id'], $resultData['tags'][0]['id']);
        $this->assertSame($originalData['tags'][0]['name'], $resultData['tags'][0]['name']);
        $this->assertSame($originalData['tags'][1]['id'], $resultData['tags'][1]['id']);
        $this->assertSame($originalData['tags'][1]['name'], $resultData['tags'][1]['name']);
    }

    public function testFromArrayWithEmptyArray(): void
    {
        $data = [
            'id' => 4,
            'title' => 'Empty Post',
            'content' => 'No tags',
            'tags' => []
        ];

        $post = $this->mapper->fromArray($data, Post::class);

        $this->assertInstanceOf(Post::class, $post);
        $tags = $post->getTags();
        $this->assertCount(0, $tags);
    }

    public function testFromArrayWithMissingPropertyInArrayElement(): void
    {
        $data = [
            'id' => 5,
            'title' => 'Test Post',
            'content' => 'Content here',
            'tags' => [
                ['id' => 1, 'name' => 'PHP'],  // Valid
                ['name' => 'JavaScript'],       // Missing 'id'
                ['id' => 3]                     // Missing 'name'
            ]
        ];

        try {
            $this->mapper->fromArray($data, StrictPost::class);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();

            // Check that we have errors for the specific array indices and properties
            $this->assertArrayHasKey('tags[1].id', $errors, 'Should have error for missing id in second element');
            $this->assertArrayHasKey('tags[2].name', $errors, 'Should have error for missing name in third element');

            // Check that first element (valid) has no errors
            $this->assertArrayNotHasKey('tags[0].id', $errors);
            $this->assertArrayNotHasKey('tags[0].name', $errors);

            // Verify error messages contain useful information
            $this->assertStringContainsString('id', $errors['tags[1].id']);
            $this->assertStringContainsString('name', $errors['tags[2].name']);
        }
    }

    public function testFromArrayWithMultipleInvalidElementsInArray(): void
    {
        $data = [
            'id' => 6,
            'title' => 'Another Test',
            'content' => 'More content',
            'tags' => [
                ['id' => 'not-an-int', 'name' => 'Tag1'],  // Invalid type for id
                'invalid-element',                          // Not an array
                ['id' => 2]                                 // Missing name
            ]
        ];

        try {
            $this->mapper->fromArray($data, StrictPost::class);
            $this->fail('Expected ValidationException to be thrown');
        } catch (ValidationException $e) {
            $errors = $e->getErrors();

            // Should have error for element at index 0 (invalid type)
            $this->assertArrayHasKey('tags[0].id', $errors, 'Should have error for invalid id type in first element');

            // Should have error for element at index 1 (not an array)
            $this->assertArrayHasKey('tags[1]', $errors, 'Should have error for invalid element at index 1');

            // Should have error for element at index 2 (missing name)
            $this->assertArrayHasKey('tags[2].name', $errors, 'Should have error for missing name in third element');

            // Verify we have at least 3 errors
            $this->assertGreaterThanOrEqual(3, count($errors));
        }
    }
}
