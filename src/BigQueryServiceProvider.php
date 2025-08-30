<?php

declare(strict_types=1);

namespace googlogmob\BigQuery;

use Override;
use Illuminate\Support\ServiceProvider;
use Google\Cloud\BigQuery\BigQueryClient;
use Illuminate\Contracts\Support\DeferrableProvider;
use googlogmob\BigQuery\Exceptions\InvalidConfiguration;

class BigQueryServiceProvider extends ServiceProvider implements DeferrableProvider
{
    protected bool $defer = true;

    /**
     * Perform post-registration booting of services.
     *
     * @return void
     */
    public function boot(): void
    {
        $source = realpath(__DIR__ . '/config/bigquery.php');
        $this->publishes([
            $source => config_path('bigquery.php'),
        ], 'laravel-bigquery');
    }

    /**
     * Register any package services.
     *
     * @return void
     */
    #[Override]
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/config/bigquery.php', 'bigquery');

        $this->app->bind(BigQuery::class, BigQuery::class);
    }

    /**
     * Validates the provided BigQuery configuration to ensure it is properly set up and throws an exception
     * if the configuration is invalid.
     *
     * @param array|null $bigQueryConfig The BigQuery configuration array, which may include
     *                                   the path to the application credentials.
     * @return void
     *
     * @throws InvalidConfiguration If the application credentials file does not exist.
     */
    protected function guardAgainstInvalidConfiguration(?array $bigQueryConfig = null): void
    {
        if (!file_exists($bigQueryConfig['application_credentials'])) {
            throw InvalidConfiguration::credentialsJsonDoesNotExist($bigQueryConfig['application_credentials']);
        }
    }

    /**
     * Returns an array of service class names provided by this method.
     *
     * @return array An array containing the class names of the services provided.
     */
    #[Override]
    public function provides(): array
    {
        return [BigQueryClient::class];
    }
}
