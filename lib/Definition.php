<?php

namespace Phpactor\MapResolver;

class Definition
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var mixed
     */
    private $defaultValue;

    /**
     * @var bool
     */
    private $required;

    /**
     * @var array<string>
     */
    private $types;

    /**
     * @var string|null
     */
    private $description;

    /**
     * @param mixed $defaultValue
     * @param array<string> $types
     */
    public function __construct(string $name, $defaultValue, bool $required, ?string $description, array $types)
    {
        $this->name = $name;
        $this->defaultValue = $defaultValue;
        $this->required = $required;
        $this->types = $types;
        $this->description = $description;
    }

    /**
     * @return array<string>
     */
    public function types(): array
    {
        return $this->types;
    }

    public function required(): bool
    {
        return $this->required;
    }

    /**
     * @return mixed
     */
    public function defaultValue()
    {
        return $this->defaultValue;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }
}
