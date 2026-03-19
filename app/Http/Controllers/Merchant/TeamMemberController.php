<?php
// app/Http/Controllers/Merchant/TeamMemberController.php

namespace App\Http\Controllers\Merchant;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Merchant\TeamMemberService;
use App\Http\Requests\Merchant\StoreTeamMemberRequest;
use App\Http\Requests\Merchant\UpdateTeamMemberRequest;

class TeamMemberController extends Controller
{
    public function __construct(
        private TeamMemberService $service
    ) {}

    public function index()
    {
        $teamMembers = $this->service->getAllPaginated(10); // 10 items per page
        
        return view('merchant.pages.team-member.index', compact('teamMembers'));
    }

    public function create()
    {
        return view('merchant.pages.team-member.create');
    }

    public function store(StoreTeamMemberRequest $request)
    {
        $this->service->create($request->validated());

        return redirect()->route('merchant.team-members.index')
            ->with('success', 'Team member created successfully');
    }

    public function edit($id)
    {
        $teamMember = $this->service->find($id);
        
        return view('merchant.pages.team-member.edit', compact('teamMember'));
    }

    public function update(UpdateTeamMemberRequest $request, User $teamMember)
    {
        // Check if the team member belongs to the logged-in merchant
        if ($teamMember->merchant_id !== auth()->id()) {
            abort(403, 'Unauthorized action.');
        }
        
        $this->service->update($teamMember, $request->validated());

        return redirect()->route('merchant.team-members.index')
            ->with('success', 'Team member updated successfully');
    }

    public function destroy(User $teamMember)
    {
        // Ensure the team member belongs to the logged-in merchant and has correct role
        if ($teamMember->merchant_id !== auth()->id() || !in_array($teamMember->role_id, [9, 10])) {
            abort(403, 'Unauthorized action.');
        }
        
        $this->service->delete($teamMember);

        return redirect()->route('merchant.team-members.index')
            ->with('success', 'Team member deleted successfully');
    }
}