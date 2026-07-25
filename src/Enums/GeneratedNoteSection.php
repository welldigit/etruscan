<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Enums;

use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;

#[EtruscanNode('generated-note-section')]
#[EtruscanLayer('enum')]
#[EtruscanContext('projection')]
enum GeneratedNoteSection: string
{
    case Description = 'Description';
    case References = 'References';

    public function heading(): string
    {
        return '## '.$this->value;
    }
}
