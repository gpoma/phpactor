<?php

namespace Phpactor\Extension\WorseReflectionExtra\Application;

use Phpactor\Extension\Core\Application\Helper\ClassFileNormalizer;
use Phpactor\WorseReflection\Core\ClassName;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\Core\Reflection\ReflectionClass;
use Phpactor\WorseReflection\Core\Reflection\ReflectionMethod;
use Phpactor\WorseReflection\Core\Reflection\ReflectionProperty;
use Phpactor\WorseReflection\TypeUtil;

class ClassReflector
{
    const FOOBAR = 'foo';

    private ClassFileNormalizer $classFileNormalizer;

    private Reflector $reflector;

    // rename compositetransformer => classToFileConverter
    public function __construct(
        ClassFileNormalizer $classFileNormalizer,
        Reflector $reflector
    ) {
        $this->classFileNormalizer = $classFileNormalizer;
        $this->reflector = $reflector;
    }

    /**
     * Move - guess if moving by class name or file.
     */
    public function reflect(string $classOrFile): array
    {
        $className = $this->classFileNormalizer->normalizeToClass($classOrFile);
        $reflection = $this->reflector->reflectClassLike(ClassName::fromString($className));

        $return  = [
            'class' => (string) $reflection->name(),
            'class_namespace' => (string) $reflection->name()->namespace(),
            'class_name' => (string) $reflection->name()->short(),
            'methods' => [],
            'properties' => [],
            'constants' => [],
        ];


        foreach ($reflection->methods() as $method) {
            assert($method instanceof ReflectionMethod);
            $methodInfo = [
                (string) $method->visibility() . ' function ' . $method->name()
            ];

            $return['methods'][$method->name()] = [
                'name' => $method->name(),
                'abstract' => $method->isAbstract(),
                'visibility' => (string) $method->visibility(),
                'parameters' => [],
                'static' => $method->isStatic() ? 1 : 0
            ];

            $paramInfos = [];
            foreach ($method->parameters() as $parameter) {
                $parameterType = $parameter->type();
                // build parameter synopsis
                $paramInfo = [];
                if (TypeUtil::isDefined($parameter->type())) {
                    $paramInfo[] = $parameter->type()->__toString();
                }
                $paramInfo[] = '$' . $parameter->name();
                if ($parameter->default()->isDefined()) {
                    $paramInfo[] = ' = ' . str_replace(PHP_EOL, '', var_export($parameter->default()->value(), true));
                }
                $paramInfos[] = implode(' ', $paramInfo);

                $return['methods'][$method->name()]['parameters'][$parameter->name()] = [
                    'name' => $parameter->name(),
                    'has_type' => TypeUtil::isDefined($parameter->type()),
                    'type' => $parameter->type()->__toString(),
                    'has_default' => $parameter->default()->isDefined(),
                    'default' => $parameter->default()->value(),
                ];
            }

            $methodInfo[] = '(' . implode(', ', $paramInfos) . ')';
            $methodType = $method->returnType();

            if (TypeUtil::isDefined($methodType)) {
                $methodInfo[] = ': ' . $methodType->__toString();
            }

            $return['methods'][$method->name()]['type'] = $methodType->__toString();

            $return['methods'][$method->name()]['synopsis'] = implode('', $methodInfo);
            $return['methods'][$method->name()]['docblock'] = $method->docblock()->formatted();
        }

        if (false === $reflection->isTrait()) {
            foreach ($reflection->constants() as $constant) {
                $return['constants'][$constant->name()] = [
                    'name' => $constant->name()
                ];
            }
        }


        if (!$reflection instanceof ReflectionClass) {
            return $return;
        }

        /** @var $property ReflectionProperty */
        foreach ($reflection->properties() as $property) {
            $propertyType = $property->inferredType();
            $return['properties'][$property->name()] = [
                'name' => $property->name(),
                'visibility' => (string) $property->visibility(),
                'static' => $property->isStatic() ? 1 : 0,
                'info' => sprintf(
                    '%s %s $%s',
                    (string) $property->visibility(),
                    $propertyType->__toString(),
                    $property->name()
                ),
            ];
        }

        return $return;
    }
}
