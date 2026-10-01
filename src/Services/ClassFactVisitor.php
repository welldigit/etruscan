<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\Instanceof_;
use PhpParser\Node\Expr\New_;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\FunctionLike;
use PhpParser\Node\IntersectionType;
use PhpParser\Node\Name;
use PhpParser\Node\NullableType;
use PhpParser\Node\Param;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Catch_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Property;
use PhpParser\Node\Stmt\TraitUse;
use PhpParser\Node\Stmt\TraitUseAdaptation;
use PhpParser\Node\UnionType;
use PhpParser\NodeVisitorAbstract;
use WellDigit\Etruscan\Payloads\ReferenceEvidence;

/**
 * @phpstan-type ClassFact array{fqcn: string, extends: string|null, attributes: list<array{fqcn: string, args: list<string>}>, evidence: list<ReferenceEvidence>}
 */
#[\EtruscanNode('class-fact-visitor')]
#[\EtruscanLayer('visitor')]
#[\EtruscanContext('scan')]
final class ClassFactVisitor extends NodeVisitorAbstract
{
    /**
     * @var list<string>
     */
    public array $references = [];

    /**
     * @var list<ClassFact>
     */
    public array $classes = [];

    /** @var list<int|null> Named class index, or null inside an anonymous class. */
    private array $classStack = [];

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof ClassLike) {
            $this->classStack[] = $node->name === null ? null : count($this->classes);
            $this->collectClass($node);
        }

        $index = $this->classStack === [] ? null : $this->classStack[array_key_last($this->classStack)];

        if ($node instanceof Name && $index !== null) {
            $kind = $this->referenceKind($node);

            if ($kind !== null) {
                $target = $this->resolveName($node);

                if ($node->isSpecialClassName()) {
                    $target = strtolower($target) === 'parent'
                        ? ($this->classes[$index]['extends'] ?? '')
                        : $this->classes[$index]['fqcn'];
                }

                if ($target !== '') {
                    $this->references[] = $target;
                    $this->classes[$index]['evidence'][] = new ReferenceEvidence($target, $kind, $node->getStartLine());
                }
            }
        }

        return null;
    }

    public function leaveNode(Node $node): ?int
    {
        if ($node instanceof ClassLike) {
            array_pop($this->classStack);
        }

        return null;
    }

    private function referenceKind(Name $name): ?string
    {
        $parent = $name->getAttribute('parent');

        return match (true) {
            $parent instanceof Attribute => 'attribute',
            $parent instanceof Class_ && $parent->extends === $name => 'extends',
            $parent instanceof Interface_ => 'extends',
            $parent instanceof ClassLike => 'implements',
            $parent instanceof TraitUse,
            $parent instanceof TraitUseAdaptation => 'trait',
            $parent instanceof New_ && $parent->class === $name => 'new',
            $parent instanceof StaticCall && $parent->class === $name => 'static-call',
            $parent instanceof StaticPropertyFetch && $parent->class === $name => 'static-property',
            $parent instanceof ClassConstFetch && $parent->class === $name => 'class-constant',
            $parent instanceof Instanceof_ && $parent->class === $name => 'instanceof',
            $parent instanceof Param,
            $parent instanceof Property,
            $parent instanceof ClassConst,
            $parent instanceof FunctionLike,
            $parent instanceof NullableType,
            $parent instanceof UnionType,
            $parent instanceof IntersectionType,
            $parent instanceof Catch_ => 'type',
            default => null,
        };
    }

    private function collectClass(ClassLike $node): void
    {
        if ($node->name === null) {
            return;
        }

        $fqcn = $node->namespacedName?->toString() ?? $node->name->toString();

        $attributes = [];

        foreach ($node->attrGroups as $group) {
            foreach ($group->attrs as $attribute) {
                $attributes[] = [
                    'fqcn' => $this->resolveName($attribute->name),
                    'args' => $this->extractStringArgs($attribute),
                ];
            }
        }

        $this->classes[] = [
            'fqcn' => $fqcn,
            'extends' => $node instanceof Class_ && $node->extends instanceof Name
                ? $this->resolveName($node->extends)
                : null,
            'attributes' => $attributes,
            'evidence' => [],
        ];
    }

    private function resolveName(Name $name): string
    {
        $resolved = $name->getAttribute('resolvedName');

        if ($resolved instanceof Name) {
            return $resolved->toString();
        }

        $namespaced = $name->getAttribute('namespacedName');

        if ($namespaced instanceof Name) {
            return $namespaced->toString();
        }

        return $name->toString();
    }

    /**
     * @return list<string>
     */
    private function extractStringArgs(Attribute $attribute): array
    {
        $args = [];

        foreach ($attribute->args as $arg) {
            if ($arg->value instanceof String_) {
                $args[] = $arg->value->value;
            }
        }

        return $args;
    }
}
