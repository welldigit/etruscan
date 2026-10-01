<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Enums;

/**
 * The structured sections a note is parsed and rendered by. References and
 * Referenced by are derived from code and rewritten on every generation;
 * Description is human-written and only ever carried, never rewritten.
 */
#[\EtruscanNode('note-section')]
#[\EtruscanLayer('enum')]
#[\EtruscanContext('projection')]
enum NoteSection: string
{
    case Description = 'Description';
    case References = 'References';
    case ReferencedBy = 'Referenced by';

    public function heading(): string
    {
        return '## '.$this->value;
    }
}
