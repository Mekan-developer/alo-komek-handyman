<?php

namespace App\Http\Traits;

trait WithNotification
{
    protected function notifySuccess(string $message, array $replace = []): void
    {
        session()->flash('notification', [
            'type' => 'success',
            'message' => __($message, $replace),
        ]);
    }

    protected function notifyError(string $message, array $replace = []): void
    {
        session()->flash('notification', [
            'type' => 'error',
            'message' => __($message, $replace),
        ]);
    }

    protected function notifyWarning(string $message, array $replace = []): void
    {
        session()->flash('notification', [
            'type' => 'warning',
            'message' => __($message, $replace),
        ]);
    }

    protected function notifyInfo(string $message, array $replace = []): void
    {
        session()->flash('notification', [
            'type' => 'info',
            'message' => __($message, $replace),
        ]);
    }
}
