<?php

namespace Phpactor\MapResolver\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Phpactor\MapResolver\Definition;
use Phpactor\MapResolver\InvalidMap;
use Phpactor\MapResolver\Resolver;
use stdClass;

class ResolverTest extends TestCase
{
    public function testSetsDefaults(): void
    {
        $resolver = new Resolver();
        $resolver->setDefaults([
            'one' => 1,
            'two' => 2,
        ]);
        $this->assertEquals(['one' => 1, 'two' => 2], $resolver->resolve([]));
    }

    public function testThrowsExceptionOnUnknownKey(): void
    {
        $this->expectException(InvalidMap::class);
        $this->expectExceptionMessage('Key(s) "three" are not known');

        $resolver = new Resolver();
        $resolver->setDefaults([
            'one' => 1,
            'two' => 2,
        ]);
        $resolver->resolve(['three' => 3]);
    }

    public function testIgnoresUnknownKey(): void
    {
        $resolver = new Resolver(true);
        $resolver->setDefaults([
            'one' => 1,
            'two' => 2,
        ]);
        $result = $resolver->resolve(['three' => 3]);
        self::assertEquals($result, [
            'one' => 1,
            'two' => 2
        ]);
        self::assertCount(1, $resolver->errors()->errors());
    }

    public function testMergesDefaults(): void
    {
        $resolver = new Resolver();
        $resolver->setDefaults([
            'one' => 1,
            'two' => 2,
        ]);
        $this->assertEquals(['one' => 5, 'two' => 2], $resolver->resolve(['one' => 5]));
    }

    public function testSettingDefaultsMultipleTimesMerges(): void
    {
        $resolver = new Resolver();
        $resolver->setDefaults([
            'one' => 1,
            'two' => 2,
        ]);
        $resolver->setDefaults([
            'one' => 3,
        ]);
        $this->assertEquals(['one' => 3, 'two' => 2], $resolver->resolve([]));
    }

    public function testThrowsExceptionOnMissingRequiredKeys(): void
    {
        $this->expectException(InvalidMap::class);
        $this->expectExceptionMessage('Key(s) "one" are required');

        $resolver = new Resolver();
        $resolver->setDefaults([
            'two' => 2,
        ]);
        $resolver->setRequired(['one']);
        $resolver->resolve(['two' => 3]);
    }

    public function testCallingRequiredMultipleTimesMergesRequiredKeys(): void
    {
        $resolver = new Resolver();
        $resolver->setRequired(['one']);
        $resolver->setRequired(['two']);
        $result = $resolver->resolve(['one' => 1, 'two' => 3]);
        $this->assertEquals(['one' => 1, 'two' => 3], $result);
    }

    public function testThrowsExceptionOnInvalidType(): void
    {
        $this->expectException(InvalidMap::class);
        $this->expectExceptionMessage('Type for "one" expected to be "string", got "stdClass"');

        $resolver = new Resolver();
        $resolver->setRequired(['one']);
        $resolver->setTypes([
            'one' => 'string',
        ]);
        $resolver->resolve(['one' => new stdClass]);
    }

    public function testCallback(): void
    {
        $resolver = new Resolver();
        $resolver->setDefaults([
            'one' => 'hello',
            'bar' => null,
        ]);
        $resolver->setCallback('bar', function (array $config) {
            return $config['one'];
        });

        $config = $resolver->resolve([]);
        $this->assertEquals('hello', $config['bar']);
    }

    public function testThrowsExceptionOnUnknownDescriptions(): void
    {
        $this->expectException(InvalidMap::class);
        $this->expectExceptionMessage('Description(s) for key(s) "four" are not known');

        $resolver = new Resolver();
        $resolver->setDefaults([
            'two' => 2,
        ]);
        $resolver->setDescriptions([
            'two' => 'three',
            'four' => 'five',
        ]);
        $resolver->resolve(['two' => 3]);
    }

    public function testResolvesDescriptions(): void
    {
        $resolver = new Resolver();
        $resolver->setDefaults([
            'two' => 2,
            'three' => null,
            'four' => 4,
        ]);
        $resolver->setDescriptions([
            'two' => 'Two is the number',
            'four' => 'Four is also a number',
        ]);
        self::assertEquals([
            'two' => 'Two is the number',
            'four' => 'Four is also a number',
            'three' => null,
        ], $resolver->resolveDescriptions());
    }

    public function testReturnsDefinition(): void
    {
        $resolver = new Resolver();
        $resolver->setDefaults([
            'two' => 2,
            'four' => 'hello',
        ]);
        $resolver->setRequired([
            'two'
        ]);
        $resolver->setDescriptions([
            'two' => 'The number two',
        ]);
        $resolver->setTypes([
            'two' => 'int',
        ]);

        $definitions = $resolver->definitions();

        self::assertEquals(
            new Definition('two', 2, true, 'The number two', ['int']),
            $definitions->get('two')
        );

        self::assertEquals(
            new Definition('four', 'hello', false, null, []),
            $definitions->get('four')
        );
    }
}
