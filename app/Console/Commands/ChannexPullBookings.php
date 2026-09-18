<?php

namespace App\Console\Commands;

use App\Services\ChannexBookingImportService;
use Illuminate\Console\Command;
use Throwable;

class ChannexPullBookings extends Command
{
    protected $signature =
        'channex:pull-bookings
        {--limit= : Maximum number of feed revisions to process}
        {--revision= : Import one specific Channex Booking Revision ID}';

    protected $description =
        'Pull Channex booking revisions into CityHouse PMS';

    public function handle(
        ChannexBookingImportService $importService
    ): int {
        $revisionId =
            trim(
                (string) $this->option('revision')
            );

        if ($revisionId !== '') {
            return $this->handleSpecificRevision(
                $importService,
                $revisionId
            );
        }

        $limitOption =
            $this->option('limit');

        $limit = null;

        if (
            $limitOption !== null
            &&
            $limitOption !== ''
        ) {
            $limit =
                max(
                    1,
                    (int) $limitOption
                );
        }

        $this->info(
            'Pulling Channex Booking Revision Feed...'
        );

        if ($limit !== null) {
            $this->line(
                "Feed processing limit: {$limit}"
            );
        }

        try {
            $summary =
                $importService
                    ->pullNewBookings(
                        $limit
                    );

        } catch (Throwable $exception) {
            $this->newLine();

            $this->error(
                $exception->getMessage()
            );

            return self::FAILURE;
        }

        $this->renderSummary(
            $summary
        );

        return ($summary['failed'] ?? 0) > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    private function handleSpecificRevision(
        ChannexBookingImportService $importService,
        string $revisionId
    ): int {
        $this->info(
            'Pulling one Channex Booking Revision...'
        );

        $this->line(
            'Revision ID: ' . $revisionId
        );

        try {
            $summary =
                $importService
                    ->pullRevisionById(
                        $revisionId
                    );

        } catch (Throwable $exception) {
            $this->newLine();

            $this->error(
                $exception->getMessage()
            );

            return self::FAILURE;
        }

        $this->renderSummary(
            $summary
        );

        return ($summary['failed'] ?? 0) > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    private function renderSummary(
        array $summary
    ): void {
        $this->newLine();

        $this->table(
            [
                'Received',
                'Imported',
                'Acknowledged',
                'Skipped',
                'Failed',
            ],
            [[
                $summary['received'] ?? 0,
                $summary['imported'] ?? 0,
                $summary['acknowledged'] ?? 0,
                $summary['skipped'] ?? 0,
                $summary['failed'] ?? 0,
            ]]
        );

        if (
            !empty(
                $summary['messages']
            )
        ) {
            $this->newLine();

            foreach (
                $summary['messages']
                as $message
            ) {
                $this->line(
                    '• ' . $message
                );
            }
        }
    }
}
