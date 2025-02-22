<?php

namespace Phpactor\WorseReflection\Core\Inference\Resolver;

use Microsoft\PhpParser\Node;
use Microsoft\PhpParser\Node\DelimitedList\QualifiedNameList;
use Microsoft\PhpParser\Node\Expression\AnonymousFunctionCreationExpression;
use Microsoft\PhpParser\Node\MethodDeclaration;
use Microsoft\PhpParser\Node\Parameter;
use Microsoft\PhpParser\Node\QualifiedName;
use Microsoft\PhpParser\Token;
use Phpactor\WorseReflection\Core\Exception\CouldNotResolveNode;
use Phpactor\WorseReflection\Core\Exception\ItemNotFound;
use Phpactor\WorseReflection\Core\Inference\Frame;
use Phpactor\WorseReflection\Core\Inference\NodeContext;
use Phpactor\WorseReflection\Core\Inference\NodeContextFactory;
use Phpactor\WorseReflection\Core\Inference\Resolver;
use Phpactor\WorseReflection\Core\Inference\Symbol;
use Phpactor\WorseReflection\Core\Inference\NodeContextResolver;
use Phpactor\WorseReflection\Core\TypeFactory;
use Phpactor\WorseReflection\Core\Util\NodeUtil;
use Phpactor\WorseReflection\Core\Util\QualifiedNameListUtil;
use Phpactor\WorseReflection\Reflector;

class ParameterResolver implements Resolver
{
    public function resolve(NodeContextResolver $resolver, Frame $frame, Node $node): NodeContext
    {
        assert($node instanceof Parameter);

        /** @var MethodDeclaration|null $method */
        $method = $node->getFirstAncestor(AnonymousFunctionCreationExpression::class, MethodDeclaration::class);

        if ($method instanceof MethodDeclaration) {
            return $this->resolveParameterFromReflection($resolver->reflector(), $frame, $method, $node);
        }

        $typeDeclaration = $node->typeDeclarationList;
        $type = TypeFactory::unknown();
        if ($typeDeclaration instanceof QualifiedNameList) {
            $typeDeclaration = QualifiedNameListUtil::firstQualifiedNameOrToken($typeDeclaration);
        }

        if ($typeDeclaration instanceof QualifiedName) {
            $type = $resolver->resolveNode($frame, $typeDeclaration)->type();
        }

        if ($typeDeclaration instanceof Token) {
            $type = TypeFactory::fromStringWithReflector(
                (string)$typeDeclaration->getText($node->getFileContents()),
                $resolver->reflector(),
            );
        }

        if ($node->questionToken) {
            $type = TypeFactory::nullable($type);
        }

        $value = null;
        if ($node->default) {
            $value = $resolver->resolveNode($frame, $node->default)->value();
        }

        return NodeContextFactory::create(
            (string)$node->variableName->getText($node->getFileContents()),
            $node->variableName->getStartPosition(),
            $node->variableName->getEndPosition(),
            [
                'symbol_type' => Symbol::VARIABLE,
                'type' => $type,
                'value' => $value,
            ]
        );
    }

    private function resolveParameterFromReflection(Reflector $reflector, Frame $frame, MethodDeclaration $method, Parameter $node): NodeContext
    {
        $class = NodeUtil::nodeContainerClassLikeDeclaration($node);

        if (null === $class) {
            throw new CouldNotResolveNode(sprintf(
                'Cannot find class context "%s" for parameter',
                $node->getName()
            ));
        }

        $reflectionClass = $reflector->reflectClassLike($class->getNamespacedName()->__toString());

        try {
            $reflectionMethod = $reflectionClass->methods()->get($method->getName());
        } catch (ItemNotFound $notFound) {
            throw new CouldNotResolveNode(sprintf(
                'Could not find method "%s" in class "%s"',
                $method->getName(),
                $reflectionClass->name()->__toString()
            ), 0, $notFound);
        }

        if (null === $node->getName()) {
            throw new CouldNotResolveNode(
                'Node name for parameter resolved to NULL'
            );
        }

        if (!$reflectionMethod->parameters()->has($node->getName())) {
            throw new CouldNotResolveNode(sprintf(
                'Cannot find parameter "%s" for method "%s" in class "%s"',
                $node->getName(),
                $reflectionMethod->name(),
                $reflectionClass->name()
            ));
        }

        $reflectionParameter = $reflectionMethod->parameters()->get($node->getName());

        return NodeContextFactory::create(
            (string)$node->variableName->getText($node->getFileContents()),
            $node->variableName->getStartPosition(),
            $node->variableName->getEndPosition(),
            [
                'symbol_type' => Symbol::VARIABLE,
                'type' => $reflectionParameter->inferredType(),
                'value' => $reflectionParameter->default()->value(),
            ]
        );
    }
}
