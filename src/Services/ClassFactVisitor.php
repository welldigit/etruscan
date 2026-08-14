<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use PhpParser\Node;
use PhpParser\Node\Attribute;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\Node\Stmt\GroupUse;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitorAbstract;
use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

/**
 * @phpstan-type ClassFact array{fqcn: string, extends: string|null, attributes: list<array{fqcn: string, args: list<string>}>}
 */
#[EtruscanNode('class-fact-visitor')]
#[EtruscanLayer('visitor')]
#[EtruscanContext('scan')]
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

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Use_) {
            $this->collectUses(groupType: $node->type, prefix: null, uses: $node->uses);

            return NodeVisitor::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof GroupUse) {
            $this->collectUses(groupType: $node->type, prefix: $node->prefix->toString(), uses: $node->uses);

            return NodeVisitor::DONT_TRAVERSE_CHILDREN;
        }

        if ($node instanceof Name) {
            if (! $node->isSpecialClassName()) {
                $this->references[] = $this->resolveName($node);
            }

            return null;
        }

        if ($node instanceof ClassLike) {
            $this->collectClass($node);
        }

        return null;
    }

    /**
     * @param  array<int, Node\UseItem>  $uses
     */
    private function collectUses(int $groupType, ?string $prefix, array $uses): void
    {
        foreach ($uses as $use) {
            $type = $use->type !== Use_::TYPE_UNKNOWN ? $use->type : $groupType;

            if ($type !== Use_::TYPE_NORMAL && $type !== Use_::TYPE_UNKNOWN) {
                continue;
            }

            $name = $use->name->toString();
            $this->references[] = $prefix === null ? $name : $prefix.'\\'.$name;
        }
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
