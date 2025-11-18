<?php

namespace App\Http\Controllers\Projects\Details;

use App\Http\Controllers\Controller;
use App\Models\DonationSplit;
use App\Models\Project;
use Illuminate\Http\Request;

class ShowProjectDonationsController extends Controller
{
    public function __invoke(Request $request, Project $project)
    {
        $leafSplitsQuery = DonationSplit::query()
            ->whereIn('project_id', $project->descendantAndSelfIds())
            ->whereDoesntHave('childrenSplits');

        $donationsAffiliated = (clone $leafSplitsQuery)->sum('amount');
        $tonnesAffiliated = (clone $leafSplitsQuery)->sum('tonne_co2');

        $project->setAttribute('donation_splits_count', (clone $leafSplitsQuery)->count());

        return view('app.projects.details.donations')->with([
            'project' => $project,
            'donationsAffiliated' => $donationsAffiliated,
            'tonnesAffiliated' => $tonnesAffiliated,
        ]);
    }
}
