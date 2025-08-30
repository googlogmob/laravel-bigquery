<?php

declare(strict_types=1);

namespace googlogmob\BigQuery\Facades;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;
use Google\Cloud\BigQuery\QueryResults;
use Google\Cloud\BigQuery\BigQueryClient;
use Google\Cloud\BigQuery\QueryJobConfiguration;

/**
 * Class BigQuery.
 *
 * @method static BigQueryClient makeClient($project_id = null)
 * @method static void prepareData(Collection $data)
 * @method static bool truncate(string $dataset, string $table, string $project_id = null)
 * @method static array handleSelectResult(array $data)
 * @method static QueryResults runQuery(QueryJobConfiguration $query, BigQueryClient $client, int $try = 5)
 * @method static void saveFromFile(string $file, string $table, array $fields, string $dataset = null, $project_id = null)
 */
class BigQuery extends Facade
{
    /**
     * Get the name of the binding in the container that this facade accesses.
     *
     * @return string Class name of the service being accessed.
     */
    protected static function getFacadeAccessor(): string
    {
        return \googlogmob\BigQuery\BigQuery::class;
    }
}
