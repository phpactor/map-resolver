<?php

namespace Phpactor\MapResolver\Tests;

use PHPUnit\Framework\TestCase;
use Phpactor\MapResolver\InvalidMap;
use Phpactor\MapResolver\Resolver;
use stdClass;

class ResolverTest extends TestCase
{
    public function testSetsDefaults()
    {
        $resolver = new Resolver();
        $resolver->setDefaults([
            'one' => 1,
            'two' => 2,
        ]);
        $this->assertEquals(['one' => 1, 'two' => 2], $resolver->resolve([]));
    }

    public function testThrowsExceptionOnUnknownKey()
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

    public function testMergesDefaults()
    {
        $resolver = new Resolver();
        $resolver->setDefaults([
            'one' => 1,
            'two' => 2,
        ]);
        $this->assertEquals(['one' => 5, 'two' => 2], $resolver->resolve(['one' => 5]));
    }

    public function testThrowsExceptionOnMissingRequiredKeys()
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

    public function testCallingRequiredMultipleTimesMergesRequiredKeys()
    {
        $resolver = new Resolver();
        $resolver->setRequired(['one']);
        $resolver->setRequired(['two']);
        $result = $resolver->resolve(['one' => 1, 'two' => 3]);
        $this->assertEquals(['one' => 1, 'two' => 3], $result);
    }

    public function testThrowsExceptionOnInvalidType()
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

    public function testCallback()
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

    public function testThrowsExceptionOnUnknownDescriptions()
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

    public function testResolvesDescriptions()
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
}
