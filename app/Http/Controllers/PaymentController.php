<?php

namespace App\Http\Controllers;

use App\Actions\RecordMasterPayoutAction;
use App\Exceptions\PaymentException;
use App\Http\Requests\PayoutMasterRequest;
use App\Http\Resources\MasterPayoutResource;
use App\Http\Resources\MasterResource;
use App\Http\Traits\WithNotification;
use App\PaymentModel;
use App\Repositories\MasterRepository;
use App\Repositories\PaymentRepository;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PaymentController extends Controller
{
    use WithNotification;

    public function __construct(
        private readonly PaymentRepository $payments,
        private readonly MasterRepository $masters,
    ) {}

    public function index(): Response
    {
        $user = request()->user();

        return Inertia::render('Payments/Index', [
            'mastersWithBalance' => MasterResource::collection($this->masters->withOutstandingBalance()),
            'payouts' => MasterPayoutResource::collection($this->payments->history()),
            'stats' => [
                'total_paid' => $this->payments->totalPaid(),
                'pending_payouts' => $this->masters->totalOutstandingBalance(),
                'masters_with_balance' => $this->masters->countWithOutstandingBalance(),
            ],
            'paymentModels' => collect(PaymentModel::cases())->map(fn ($m) => [
                'value' => $m->value,
                'label' => $m->label(),
            ]),
            'canPayout' => $user->isAdministrator(),
        ]);
    }

    public function payout(PayoutMasterRequest $request, int $id, RecordMasterPayoutAction $action): RedirectResponse
    {
        $master = $this->masters->findOrFail($id);
        $validated = $request->validated();

        try {
            $action->handle(
                $master,
                $request->user(),
                $validated['note'] ?? null,
                isset($validated['amount']) ? (float) $validated['amount'] : null,
            );
            $this->notifySuccess('payments.notifications.paid');
        } catch (PaymentException $e) {
            $this->notifyError($e->getMessage());
        }

        return redirect()->route('payments.index');
    }
}
