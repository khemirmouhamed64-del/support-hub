<?php

namespace App\Http\Controllers;

use App\Models\TeamMember;
use App\Models\ModuleExpertise;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TeamController extends Controller
{
    // Modules are configured in config/support.php
    private static function availableModules(): array
    {
        return array_column(config('support.modules', []), 'key');
    }

    public function index()
    {
        $members = TeamMember::with('moduleExpertise')
            ->withCount('assignedTickets')
            ->orderBy('name')
            ->get();

        return view('team.index', compact('members'));
    }

    public function create()
    {
        return view('team.form', [
            'member'  => new TeamMember(),
            'modules' => self::availableModules(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:team_members,email',
            'role'     => 'required|in:dev,qa,lead,pm',
            'expertise'   => 'nullable|array',
            'expertise.*' => 'in:' . implode(',', self::availableModules()),
            'primary_module' => 'nullable|in:' . implode(',', self::availableModules()),
        ]);

        $plainPassword = Str::random(12);

        $member = TeamMember::create([
            'name'                 => $data['name'],
            'email'                => $data['email'],
            'password'             => Hash::make($plainPassword),
            'role'                 => $data['role'],
            'must_change_password' => true,
        ]);

        $this->syncExpertise($member, $data);

        return redirect()->route('team.index')->with([
            'success'            => __('team.member_created'),
            'generated_password' => $plainPassword,
            'generated_for'      => $member->name,
        ]);
    }

    public function edit(TeamMember $member)
    {
        $member->load('moduleExpertise');

        return view('team.form', [
            'member'  => $member,
            'modules' => self::availableModules(),
        ]);
    }

    public function update(Request $request, TeamMember $member)
    {
        $data = $request->validate([
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:team_members,email,' . $member->id,
            'password' => 'nullable|string|min:6',
            'role'     => 'required|in:dev,qa,lead,pm',
            'expertise'   => 'nullable|array',
            'expertise.*' => 'in:' . implode(',', self::availableModules()),
            'primary_module' => 'nullable|in:' . implode(',', self::availableModules()),
        ]);

        $member->update([
            'name'  => $data['name'],
            'email' => $data['email'],
            'role'  => $data['role'],
        ]);

        if (!empty($data['password'])) {
            $member->update(['password' => Hash::make($data['password'])]);
        }

        $this->syncExpertise($member, $data);

        return redirect()->route('team.index')->with('success', __('team.member_updated'));
    }

    public function toggleActive(TeamMember $member)
    {
        $member->update(['is_active' => !$member->is_active]);

        return back()->with('success', $member->is_active ? __('team.member_activated') : __('team.member_deactivated'));
    }

    /**
     * AJAX: search team members for @mention autocomplete.
     */
    public function search(Request $request)
    {
        $q = $request->input('q', '');
        $members = TeamMember::active()
            ->where(function ($query) use ($q) {
                $query->where('name', 'like', '%' . $q . '%')
                      ->orWhere('email', 'like', '%' . $q . '%');
            })
            ->where('id', '!=', auth()->id())
            ->orderBy('name')
            ->limit(5)
            ->get(['id', 'name', 'email', 'role']);

        return response()->json($members);
    }

    private function syncExpertise(TeamMember $member, array $data)
    {
        $member->moduleExpertise()->delete();

        $modules = $data['expertise'] ?? [];
        $primary = $data['primary_module'] ?? null;

        foreach ($modules as $mod) {
            $member->moduleExpertise()->create([
                'module_name' => $mod,
                'is_primary'  => $mod === $primary,
            ]);
        }
    }
}
