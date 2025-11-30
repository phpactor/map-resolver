<?php

namespace Phpactor\MapResolver;


class UnknownKeys extends InvalidMap
{
    /**
     * @param list<string> $allowedKeys
     * @param list<string> $keys
     */
    public function __construct(string $message, private array $keys, private array $allowedKeys)
    {
        parent::__construct($message);
    }

    /**
     * @return list<string>
     */
    public function additionalKeys(): array
    {
        return $this->keys;
    }

    /**
     * @return list<string>
     */
    public function allowedKeys(): array
    {
        return $this->allowedKeys;
    }

    /**
     * @param list<string> $allowedKeys
     * @param list<string> $diff
     */
    public static function fromKeysAndAllowedKeys(array $diff, array $allowedKeys): self
    {
        return new self(sprintf(
            'Key(s) "%s" are not known, known keys: "%s"',
            implode('", "', ($diff)),
            implode('", "', $allowedKeys),
        ), $diff, $allowedKeys);
    }
}
