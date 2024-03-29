<?php

namespace googlogmob\BigQuery\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * Class BigQuery.
 *
 * @method static \Google\Cloud\BigQuery\BigQueryClient makeClient($project_id = null)
 * @method static void prepareData(\Illuminate\Support\Collection $data)
 * @method static bool truncate(string $dataset, string $table, string $project_id = null)
 * @method static array handleSelectResult(array $data)
 * @method static \Google\Cloud\BigQuery\QueryResults runQuery(\Google\Cloud\BigQuery\QueryJobConfiguration $query, \Google\Cloud\BigQuery\BigQueryClient $client, int $try = 5)
 * @method static void saveFromFile(string $file, string $table, array $fields, string $dataset = null, $project_id = null)
 */
class BigQuery extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \googlogmob\BigQuery\BigQuery::class;
    }
}
