<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Payloads;

#[\EtruscanNode('context-footprint')]
#[\EtruscanLayer('payload')]
#[\EtruscanContext('usage')]
final readonly class ContextFootprint
{
    /**
     * @param  int  $notes  Generated notes on the map.
     * @param  int  $noteChars  Characters of markdown across those notes.
     * @param  int  $sourceChars  Characters of source in the files they index.
     * @param  int  $sourcesMissing  Notes whose source file could not be read — a stale map, counted rather than hidden.
     */
    public function __construct(
        public int $notes,
        public int $noteChars,
        public int $sourceChars,
        public int $sourcesMissing,
    ) {}

    /**
     * Source characters per note character: how much code each character of
     * map covers. Honestly zero when there is nothing to compare against.
     */
    public function ratio(): float
    {
        return $this->noteChars === 0 ? 0.0 : round($this->sourceChars / $this->noteChars, 1);
    }
}
