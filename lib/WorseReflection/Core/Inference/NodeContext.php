<?php

namespace Phpactor\WorseReflection\Core\Inference;

use Phpactor\WorseReflection\Core\Type;
use Phpactor\WorseReflection\Core\TypeFactory;
use Phpactor\WorseReflection\Core\Types;
use Phpactor\WorseReflection\Core\Reflection\ReflectionScope;

final class NodeContext
{
    /**
     * @var mixed
     */
    private $value;
    
    private Types $types;
    
    private Symbol $symbol;

    /**
     * @var Type
     */
    private ?Type $containerType = null;

    /**
     * @var string[]
     */
    private array $issues = [];

    /**
     * @var ReflectionScope
     */
    private ?ReflectionScope $scope = null;

    /**
     * @param mixed $value
     */
    private function __construct(Symbol $symbol, Types $types, $value = null, Type $containerType = null, ReflectionScope $scope = null)
    {
        $this->value = $value;
        $this->symbol = $symbol;
        $this->containerType = $containerType;
        $this->types = $types;
        $this->scope = $scope;
    }

    public static function for(Symbol $symbol): NodeContext
    {
        return new self($symbol, Types::fromTypes([ TypeFactory::unknown() ]));
    }

    /**
     * @deprecated
     * @param mixed $value
     */
    public static function fromTypeAndValue(Type $type, $value): NodeContext
    {
        return new self(Symbol::unknown(), Types::fromTypes([ $type ]), $value);
    }

    /**
     * @deprecated Types are plural
     */
    public static function fromType(Type $type): NodeContext
    {
        return new self(Symbol::unknown(), Types::fromTypes([ $type ]));
    }

    public static function none(): NodeContext
    {
        return new self(Symbol::unknown(), Types::empty());
    }

    /**
     * @param mixed $value
     */
    public function withValue($value): NodeContext
    {
        $new = clone $this;
        $new->value = $value;

        return $new;
    }

    public function withContainerType(Type $containerType): NodeContext
    {
        $new = clone $this;
        $new->containerType = $containerType;

        return $new;
    }

    /**
     * @deprecated Types are plural
     */
    public function withType(Type $type): NodeContext
    {
        $new = clone $this;
        $new->types = Types::fromTypes([ $type ]);

        return $new;
    }

    public function withTypes(Types $types): NodeContext
    {
        $new = clone $this;
        $new->types = $types;

        return $new;
    }

    public function withScope(ReflectionScope $scope): NodeContext
    {
        $new = clone $this;
        $new->scope = $scope;

        return $new;
    }

    public function withIssue(string $message): NodeContext
    {
        $new = clone $this;
        $new->issues[] = $message;

        return $new;
    }

    /**
     * @deprecated
     */
    public function type(): Type
    {
        foreach ($this->types() as $type) {
            return $type;
        }

        return TypeFactory::unknown();
    }

    public function types(): Types
    {
        return $this->types;
    }

    /**
     * @return mixed
     */
    public function value()
    {
        return $this->value;
    }

    public function symbol(): Symbol
    {
        return $this->symbol;
    }

    public function hasContainerType(): bool
    {
        return null !== $this->containerType;
    }

    /**
     * @return Type|null
     */
    public function containerType()
    {
        return $this->containerType;
    }

    /**
     * @return string[]
     */
    public function issues(): array
    {
        return $this->issues;
    }

    public function scope(): ReflectionScope
    {
        return $this->scope;
    }
}
