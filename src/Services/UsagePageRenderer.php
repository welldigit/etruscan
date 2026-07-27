<?php

declare(strict_types=1);

namespace WellDigit\Etruscan\Services;

use Illuminate\Support\Facades\File;
use WellDigit\Etruscan\Attributes\EtruscanNode;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanContext;
use WellDigit\Etruscan\Attributes\Vocabulary\EtruscanLayer;
use WellDigit\Etruscan\Payloads\UsageReport;

#[EtruscanNode('usage-page-renderer')]
#[EtruscanLayer('service')]
#[EtruscanContext('usage')]
final readonly class UsagePageRenderer
{
    private const string DATA_PLACEHOLDER = '__ETRUSCAN_DATA__';

    public function __invoke(UsageReport $usageReport): string
    {
        $template = File::get(__DIR__.'/../../resources/usage/page.html');

        $data = json_encode([
            'window_days' => $usageReport->windowDays,
            'events' => $usageReport->events,
            'by_type' => (object) $usageReport->byType,
            'by_day' => (object) $usageReport->byDay,
            'hits' => $usageReport->hits,
            'misses' => $usageReport->misses,
            'top_nodes' => (object) $usageReport->topNodes,
            'missed_subjects' => (object) $usageReport->missedSubjects,
            'empty_searches' => (object) $usageReport->emptySearches,
            'distinct_nodes_consulted' => $usageReport->distinctNodesConsulted,
            'skipped' => [
                'malformed' => $usageReport->malformedLines,
                'newer_schema' => $usageReport->newerSchemaLines,
            ],
        ], JSON_THROW_ON_ERROR | JSON_HEX_TAG | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return str_replace(self::DATA_PLACEHOLDER, $data, $template);
    }
}
