<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use PhpParser\Node\Stmt;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\NodeVisitor\ParentConnectingVisitor;

/**
 * @phpstan-import-type ClassFact from ClassFactVisitor
 */
#[\EtruscanNode('class-fact-collector')]
#[\EtruscanLayer('service')]
#[\EtruscanContext('scan')]
final readonly class ClassFactCollector
{
    /**
     * @param  array<Stmt>  $ast
     * @return array{classes: list<ClassFact>, references: list<string>}
     */
    public function __invoke(array $ast): array
    {
        $classFactVisitor = new ClassFactVisitor;

        $nodeTraverser = new NodeTraverser;
        $nodeTraverser->addVisitor(new NameResolver(null, ['replaceNodes' => false]));
        $nodeTraverser->addVisitor(new ParentConnectingVisitor);
        $nodeTraverser->addVisitor($classFactVisitor);
        $nodeTraverser->traverse($ast);

        return [
            'classes' => $classFactVisitor->classes,
            'references' => $classFactVisitor->references,
        ];
    }
}
