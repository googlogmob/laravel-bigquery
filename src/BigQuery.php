<?php

declare(strict_types=1);

namespace googlogmob\BigQuery;

use Exception;
use RuntimeException;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Google\Cloud\BigQuery\QueryResults;
use Google\Cloud\BigQuery\BigQueryClient;
use Google\Cloud\Core\ExponentialBackoff;
use googlogmob\BigQuery\Cache\CacheItemPool;
use Google\Cloud\BigQuery\QueryJobConfiguration;

/**
 * Class BigQuery.
 */
class BigQuery
{
    /**
     * Creates and configures a BigQueryClient instance based on provided or default project settings.
     *
     * @param string|null $project_id Optional project ID. If null, empty, or '0', defaults to the project ID from the configuration.
     * @return BigQueryClient A configured instance of the BigQueryClient.
     */
    public function makeClient(?string $project_id = null): BigQueryClient
    {
        $bigQueryConfig = config('bigquery');
        $project_id = $project_id === null || $project_id === '' || $project_id === '0' ? $bigQueryConfig['project_id'] : $project_id;

        $store = Cache::store($bigQueryConfig['auth_cache_store']);
        $cache = new CacheItemPool($store);

        $clientConfig = array_merge([
            'projectId' => $project_id,
            'keyFilePath' => $bigQueryConfig['application_credentials'],
            'authCache' => $cache,
        ], Arr::get($bigQueryConfig, 'client_options', []));

        return new BigQueryClient($clientConfig);
    }

    /**
     * Prepares and transforms the provided collection by mapping each item into an array format.
     *
     * @param Collection $data The collection of items to be transformed.
     * @return void
     */
    public function prepareData(Collection $data): void
    {
        $data->transform(static fn ($item): array => [
            'data' => $item,
        ]);
    }

    /**
     * Truncates all data from the specified table within the given dataset.
     *
     * @param string $dataset The name of the dataset containing the table to truncate.
     * @param string $table The name of the table to truncate.
     * @param string|null $project_id Optional project ID to use for the operation. Defaults to the current project if not provided.
     * @return bool Returns true if the truncation query completes successfully, otherwise false.
     * @throws Exception
     */
    public function truncate(string $dataset, string $table, ?string $project_id = null): bool
    {
        $client = $this->makeClient($project_id);
        $query = $client->query(sprintf('DELETE FROM %s.%s WHERE 1=1', $dataset, $table));

        return $this->runQuery($query, $client)->isComplete();
    }

    /**
     * Processes the provided data to extract and map rows based on the defined schema fields.
     *
     * @param array $data The input data containing a 'schema' with defined fields and 'rows' to process.
     * @return array An array of processed rows where each row is mapped with field names as keys and corresponding values.
     */
    public function handleSelectResult(array $data): array
    {
        if (!Arr::get($data, 'rows', false)) {
            return [];
        }

        $fields = collect($data['schema']['fields'])->map(fn ($item) => $item['name'])->toArray();

        return collect($data['rows'])
            ->map(fn ($item) => collect($item['f'])->mapWithKeys(fn ($item, $k) => [$fields[$k] => $item['v']]))->toArray();
    }

    /**
     * Executes a BigQuery query with retry logic and waits for query completion.
     *
     * @param QueryJobConfiguration $query The query job configuration to be executed.
     * @param BigQueryClient $client The BigQuery client instance used to run the query.
     * @param int $try The number of retry attempts in case of retryable errors (default is 5).
     * @return QueryResults The results of the query execution.
     *
     * @throws Exception If the query fails after maximum retries or encounters a non-retryable error.
     */
    public function runQuery(QueryJobConfiguration $query, BigQueryClient $client, int $try = 5): QueryResults
    {
        try {
            $qr = $client->runQuery($query);
            $timer = 60;
            while ($timer <= 0) {
                if ($qr->isComplete()) {
                    return $qr;
                }
                sleep(1);
                $qr->reload();
                $timer--;
            }

            return $qr;
        } catch (Exception $e) {
            if ($try <= 0 || $e->getCode() !== 403) {
                throw $e;
            }
            sleep(config('bigquery.sleep_time_403', 10));

            return $this->runQuery($query, $client, --$try);
        }
    }

    /**
     * Saves data from a specified CSV file into a BigQuery table based on the provided schema fields.
     *
     * @param string $file The path to the CSV file to be imported.
     * @param string $table The name of the BigQuery table where the data will be saved.
     * @param array $fields The list of field names to map between the CSV file and the BigQuery table schema.
     * @param string|null $dataset The BigQuery dataset name. If not provided, the value will be derived from the environment configuration.
     * @param string|null $project_id The Google Cloud project ID. If null, the client's default project ID will be used.
     * @return void
     * @throws Exception
     */
    public function saveFromFile(string $file, string $table, array $fields, ?string $dataset = null, ?string $project_id = null): void
    {
        $dataset ??= env('GOOGLE_CLOUD_DATASET');
        $client = $this->makeClient($project_id);
        $tableInfo = $client->dataset($dataset)->table($table)->info();
        $tableFields = collect($tableInfo['schema']['fields']);

        $schema = [
            'fields' => collect($fields)->map(fn ($k) => $tableFields->first(fn ($field): bool => mb_strtolower((string)$field['name']) === mb_strtolower((string)$k)))->values()->toArray(),
        ];

        $tableBq = $client->dataset($dataset)->table($table);

        $loadConfig = $tableBq->load(fopen($file, 'rb'))
            ->sourceFormat('CSV')
            ->fieldDelimiter(';')
            ->schema($schema);
        $job = $tableBq->runJob($loadConfig);

        $backoff = new ExponentialBackoff(10);

        $backoff->execute(function () use ($job): void {
            $job->reload();
            if ($job->isComplete() === false) {
                throw new RuntimeException('Job has not yet completed', 500);
            }
        });

        if (isset($job->info()['status']['errorResult'])) {
            throw new RuntimeException(sprintf('Error during saving to BQ %s %s', PHP_EOL, print_r($job->info(), true)));
        }
    }
}
