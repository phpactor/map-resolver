<?php

namespace Phpactor\MapResolver;

use Closure;

class Resolver
{
    /**
     * @var array<string>
     */
    private $required = [];

    /**
     * @var array<string,mixed>
     */
    private $defaults = [];

    /**
     * @var array<string,string>
     */
    private $types = [];

    /**
     * @var array<string,callable>
     */
    private $callbacks = [];

    /**
     * @var array<string,string>
     */
    private $descriptions = [];

    /**
     * @var bool
     */
    private $ignoreErrors;

    /**
     * @var InvalidMap[]
     */
    private $errors;

    public function __construct(bool $ignoreErrors = false)
    {
        $this->ignoreErrors = $ignoreErrors;
    }

    public function setCallback(string $field, Closure $callable): void
    {
        $this->callbacks[$field] = $callable;
    }

    /**
     * @param array<string> $fields
     */
    public function setRequired(array $fields): void
    {
        $this->required = array_merge($this->required, $fields);
    }

    /**
     * @param array<string,mixed> $defaults
     */
    public function setDefaults(array $defaults): void
    {
        $this->defaults = array_merge($this->defaults, $defaults);
    }

    /**
     * @param array<string,string> $typeMap
     */
    public function setTypes(array $typeMap): void
    {
        $this->types = $typeMap;
    }

    /**
     * @param array<string,string> $descriptions
     */
    public function setDescriptions(array $descriptions): void
    {
        $this->descriptions = $descriptions;
    }

    /**
     * @return array<string,string>
     */
    public function resolveDescriptions(): array
    {
        $keys = $this->resolveAllowedKeys();
        return (array)array_combine($keys, array_map(function (string $key) {
            return $this->descriptions[$key] ?? null;
        }, $keys));
    }

    /**
     * @param array<string,mixed> $config
     *
     * @return array<string,mixed>
     */
    public function resolve(array $config): array
    {
        $allowedKeys = $this->resolveAllowedKeys();

        if ($diff = array_diff(array_keys($config), $allowedKeys)) {
            $this->throwOrLogError(
                new InvalidMap(sprintf(
                    'Key(s) "%s" are not known, known keys: "%s"',
                    implode('", "', ($diff)),
                    implode('", "', $allowedKeys)
                ))
            );

            $config = $this->removeKeys($config, $diff);
        }

        if ($diff = array_diff(array_keys($this->descriptions), $allowedKeys)) {
            throw new InvalidMap(sprintf(
                'Description(s) for key(s) "%s" are not known, known keys: "%s"',
                implode('", "', ($diff)),
                implode('", "', $allowedKeys)
            ));
        }

        $config = array_merge($this->defaults, $config);

        if ($diff = array_diff($this->required, array_keys($config))) {
            $this->throwOrLogError(new InvalidMap(sprintf(
                'Key(s) "%s" are required',
                implode('", "', $diff)
            )));
            $config = $this->removeKeys($config, $diff);
        }

        foreach ($config as $key => $value) {
            if (isset($this->types[$key])) {
                $valid = true;
                $type = null;

                if (is_object($value)) {
                    $type = get_class($value);
                    $valid = $value instanceof $this->types[$key];
                }

                if (false === is_object($value)) {
                    $type = gettype($value);
                    $valid = $this->types[$key] === gettype($value);
                }

                if (false === $valid) {
                    $this->throwOrLogError(new InvalidMap(sprintf(
                        'Type for "%s" expected to be "%s", got "%s"',
                        $key,
                        $this->types[$key],
                        $type
                    )));
                }
            }
        }

        foreach ($config as $key => $value) {
            if (!isset($this->callbacks[$key])) {
                continue;
            }
            $callback = $this->callbacks[$key];
            $config[$key] = $callback($config);
        }

        return $config;
    }

    public function merge(Resolver $schema): Resolver
    {
        foreach ($schema->required as $required) {
            $this->required[] = $required;
        }

        foreach ($schema->defaults as $key => $value) {
            $this->defaults[$key] = $value;
        }

        foreach ($schema->types as $key => $types) {
            $this->types[$key] = $types;
        }

        foreach ($schema->callbacks as $key => $callback) {
            $this->callbacks[$key] = $callback;
        }

        return $this;
    }

    /**
     * @return array<string>
     */
    private function resolveAllowedKeys(): array
    {
        $allowedKeys = array_merge(array_keys($this->defaults), $this->required);
        return $allowedKeys;
    }

    public function definitions(): Definitions
    {
        $definitions = [];

        foreach ($this->resolveAllowedKeys() as $key) {
            $definitions[] = new Definition(
                $key,
                $this->defaults[$key] ?? null,
                in_array($key, $this->required),
                $this->descriptions[$key] ?? null,
                isset($this->types[$key]) ? [$this->types[$key]] : []
            );
        }

        return new Definitions($definitions);
    }

    private function throwOrLogError(InvalidMap $error): void
    {
        if (!$this->ignoreErrors) {
            throw $error;
        }

        $this->errors[] = $error;
    }

    private function removeKeys(array $config, array $keys): array
    {
        foreach ($keys as $remove) {
            unset($config[$remove]);
        }

        return $config;
    }
}
