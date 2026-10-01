<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use Illuminate\Support\Facades\File;
use WellDigit\Etruscan\Enums\IdentityFrontmatterKey;
use WellDigit\Etruscan\Payloads\NoteContent;

#[\EtruscanNode('graph-page-renderer')]
#[\EtruscanLayer('service')]
#[\EtruscanContext('graph')]
final readonly class GraphPageRenderer
{
    private const string DATA_PLACEHOLDER = '__ETRUSCAN_DATA__';

    /**
     * @param  list<NoteContent>  $notes
     */
    public function __invoke(array $notes): string
    {
        $template = File::get(__DIR__.'/../../resources/graph/page.html');

        return str_replace(self::DATA_PLACEHOLDER, $this->encodeGraph($notes), $template);
    }

    /**
     * @param  list<NoteContent>  $notes
     */
    private function encodeGraph(array $notes): string
    {
        $graphNodes = [];
        $graphEdges = [];

        foreach ($notes as $noteContent) {
            $axes = array_filter(
                $noteContent->frontmatter,
                static fn (string $frontmatterKey): bool => ! IdentityFrontmatterKey::isReserved($frontmatterKey),
                ARRAY_FILTER_USE_KEY,
            );

            $graphNodes[] = [
                'id' => $noteContent->alias,
                'class' => $noteContent->frontmatter[IdentityFrontmatterKey::ClassShortName->value] ?? null,
                'fqcn' => $noteContent->frontmatter[IdentityFrontmatterKey::Fqcn->value] ?? null,
                'extends' => $noteContent->frontmatter[IdentityFrontmatterKey::Extends->value] ?? null,
                'source' => $noteContent->frontmatter[IdentityFrontmatterKey::Source->value] ?? null,
                'description' => $noteContent->description,
                'axes' => (object) $axes,
            ];

            foreach ($noteContent->links as $link) {
                $graphEdges[] = ['source' => $noteContent->alias, 'target' => $link];
            }
        }

        return json_encode(
            ['nodes' => $graphNodes, 'edges' => $graphEdges],
            JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );
    }
}
