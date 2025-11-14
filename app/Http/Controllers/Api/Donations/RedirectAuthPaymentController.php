<?php

namespace App\Http\Controllers\Api\Donations;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Tenant;
use App\Services\Models\TransactionService;
use Illuminate\Http\Request;

class RedirectAuthPaymentController extends Controller
{
    public function __invoke(Request $request, Tenant $tenant)
    {

        $project = match ($request->has('projectId')) {
            true => Project::findOrFail($request->get('projectId')),
            false => null
        };

        $tenant = match (is_null($project)) {
            true => Tenant::findOrFail($request->get('tenantId')),
            false => $project->tenant
        };

        $transactionService = new TransactionService($tenant);
        $transaction = $transactionService->createTransaction(
            related: auth()->user(),
            amount: $request->get('amount'),
            project: $project,
            failedUrl: $tenant->public_url."?p={$request->get('wpPageId')}&payment_cancel=1"
        );

        return redirect()->to($transaction->payment_url);
    }
}
