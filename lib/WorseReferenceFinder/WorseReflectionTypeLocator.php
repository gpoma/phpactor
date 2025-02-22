<?php

namespace Phpactor\WorseReferenceFinder;

use Phpactor\ReferenceFinder\Exception\UnsupportedDocument;
use Phpactor\ReferenceFinder\TypeLocator;
use Phpactor\ReferenceFinder\Exception\CouldNotLocateType;
use Phpactor\TextDocument\ByteOffset;
use Phpactor\TextDocument\Location;
use Phpactor\TextDocument\TextDocument;
use Phpactor\TextDocument\TextDocumentUri;
use Phpactor\WorseReflection\Core\ClassName;
use Phpactor\WorseReflection\Core\Exception\NotFound;
use Phpactor\WorseReflection\Core\Inference\NodeContext;
use Phpactor\WorseReflection\Core\SourceCode;
use Phpactor\WorseReflection\Core\Type;
use Phpactor\WorseReflection\Core\Type\ArrayType;
use Phpactor\WorseReflection\Core\Type\ClassType;
use Phpactor\WorseReflection\Reflector;
use Phpactor\WorseReflection\TypeUtil;

class WorseReflectionTypeLocator implements TypeLocator
{
    private Reflector $reflector;

    public function __construct(Reflector $reflector)
    {
        $this->reflector = $reflector;
    }

    
    public function locateType(TextDocument $document, ByteOffset $byteOffset): Location
    {
        if (false === $document->language()->isPhp()) {
            throw new UnsupportedDocument('I only work with PHP files');
        }

        if ($uri = $document->uri()) {
            $sourceCode = SourceCode::fromPathAndString($uri->__toString(), $document->__toString());
        } else {
            $sourceCode = SourceCode::fromString($document->__toString());
        }

        $offset = $this->reflector->reflectOffset(
            $sourceCode,
            $byteOffset->toInt()
        );

        return $this->gotoType($offset->symbolContext());
    }

    private function gotoType(NodeContext $symbolContext): Location
    {
        $type = $symbolContext->type();

        $className = $this->resolveClassName($type);

        try {
            $class = $this->reflector->reflectClassLike($className->full());
        } catch (NotFound $e) {
            throw new CouldNotLocateType($e->getMessage(), 0, $e);
        }

        $path = $class->sourceCode()->path();

        return new Location(
            TextDocumentUri::fromString($path),
            ByteOffset::fromInt($class->position()->start())
        );
    }

    private function resolveClassName(Type $type): ClassName
    {
        $type = TypeUtil::unwrapNullableType($type);

        if ($type instanceof ArrayType) {
            return $this->resolveClassName($type->valueType);
        }

        if (!$type instanceof ClassType) {
            throw new CouldNotLocateType(sprintf(
                'Cannot goto to primitive type "%s"',
                $type->__toString()
            ));
        }
        
        return $type->name();
    }
}
