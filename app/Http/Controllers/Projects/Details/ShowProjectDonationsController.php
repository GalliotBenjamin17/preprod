<?php

namespace App\Http\Controllers\Projects\Details;

use App\Http\Controllers\Controller;
use App\Models\Project;
use Illuminate\Http\Request;

class ShowProjectDonationsController extends Controller
{
    public function __invoke(Request $request, Project $project)
    {
        $leafSplitsQuery = $project->leafDonationSplitsQuery();
        $parentSplitsWithRemaining = $project->donationSplits()
            ->with(['donation.related', 'childrenSplits'])
            ->get()
            ->filter(function ($split) {
                $childrenSum = $split->childrenSplits->sum('amount');

                return $split->childrenSplits->isNotEmpty() && $childrenSum < $split->amount;
            })
            ->values();

        $donationsAffiliated = (clone $leafSplitsQuery)->sum('amount');
        $tonnesAffiliated = (clone $leafSplitsQuery)->sum('tonne_co2');

        $project->setAttribute('donation_splits_count', (clone $leafSplitsQuery)->count());

        return view('app.projects.details.donations')->with([
            'project' => $project,
            'donationsAffiliated' => $donationsAffiliated,
            'tonnesAffiliated' => $tonnesAffiliated,
            'parentSplitsWithRemaining' => $parentSplitsWithRemaining,
        ]);
    }
}
