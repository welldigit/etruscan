<?php

declare(strict_types=1);

use WellDigit\Etruscan\Enums\IdentityFrontmatterKey;

test('every identity frontmatter key is reserved for custom axes', function (IdentityFrontmatterKey $identityFrontmatterKey) {
    expect(IdentityFrontmatterKey::isReserved($identityFrontmatterKey->value))->toBeTrue();
})->with(IdentityFrontmatterKey::cases());

test('bundled and custom axis keys are not reserved', function (string $axisKey) {
    expect(IdentityFrontmatterKey::isReserved($axisKey))->toBeFalse();
})->with(['layer', 'domain', 'context', 'slice', 'criticality']);
