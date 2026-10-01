<?php

declare(strict_types=1);

use WellDigit\Etruscan\Attributes\EtruscanAxis;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

/**
 * The scanner never instantiates an attribute, so nothing else in the suite
 * proves the global twins carry usable #[Attribute] flags.
 */
test('the global twins are real attributes: class-targeted, repeatable, and instantiable', function () {
    $annotated = new #[EtruscanNode('runtime-node')]
    #[\EtruscanLayer('action')]
    #[\EtruscanLayer('service')]
    #[EtruscanContext('billing')]
    #[EtruscanDomain('booking')]
    #[EtruscanSlice('checkout')]
    class {};

    $reflection = new ReflectionObject($annotated);

    $node = $reflection->getAttributes(EtruscanNode::class)[0]->newInstance();

    $axes = array_map(
        static fn (ReflectionAttribute $attribute): EtruscanAxis => $attribute->newInstance(),
        $reflection->getAttributes(EtruscanAxis::class, ReflectionAttribute::IS_INSTANCEOF),
    );

    expect($node->alias)->toBe('runtime-node')
        ->and(array_map(static fn (EtruscanAxis $axis): string => $axis->value, $axes))
        ->toBe(['action', 'service', 'billing', 'booking', 'checkout']);
});

test('a global twin is not an instance of its namespaced sibling', function () {
    $annotated = new #[\EtruscanLayer('action')] class {};

    $layer = new ReflectionObject($annotated)->getAttributes(\EtruscanLayer::class)[0]->newInstance();

    // Runtime readers should ask for EtruscanAxis with IS_INSTANCEOF, which covers
    // both spellings and any custom axis.
    expect($layer)->toBeInstanceOf(EtruscanAxis::class)
        ->and($layer)->not->toBeInstanceOf(EtruscanLayer::class);
});
