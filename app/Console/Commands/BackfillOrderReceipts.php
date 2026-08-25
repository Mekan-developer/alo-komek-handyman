<?php

namespace App\Console\Commands;

use App\Actions\IssueOrderReceiptAction;
use App\Repositories\OrderReceiptRepository;
use Illuminate\Console\Command;

/**
 * Выдаёт чеки заказам, завершённым до появления чеков в системе.
 * Запускается разово после деплоя; повторный запуск безопасен.
 */
class BackfillOrderReceipts extends Command
{
    protected $signature = 'receipts:backfill';

    protected $description = 'Issue receipts for completed orders that do not have one yet';

    public function handle(OrderReceiptRepository $repository, IssueOrderReceiptAction $action): int
    {
        $orders = $repository->completedWithoutReceipt();

        if ($orders->isEmpty()) {
            $this->info('Все завершённые заказы уже имеют чеки.');

            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($orders->count());
        $bar->start();

        foreach ($orders as $order) {
            $action->handle($order);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info("Выдано чеков: {$orders->count()}");

        return self::SUCCESS;
    }
}
