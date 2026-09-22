<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserRequest;
use App\Models\User;
use App\Services\Admin\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function __construct(private readonly UserService $users) {}

    /**
     * Display the users management page.
     */
    public function showTableList()
    {
        $roles = Role::orderBy('name')->get(['id', 'name']);

        return view('users.componentItem', compact('roles'));
    }

    /**
     * List all users with their roles (DataTables server-side JSON).
     */
    public function index(Request $request)
    {
        try {
            // DataTables parameters
            $start = max(0, (int) $request->input('start', 0));
            $length = min(100, max(1, (int) $request->input('length', 25)));
            $searchValue = $request->input('search.value', '');
            $draw = $request->input('draw', 1);
            $orderColumn = (int) $request->input('order.0.column', 0);
            $orderDirection = $request->input('order.0.dir', 'asc') === 'desc' ? 'desc' : 'asc';

            $query = User::with('roles:id,name');

            // Total records count (without filtering)
            $totalRecords = (clone $query)->count();

            // Global search
            if (! empty($searchValue)) {
                $query->where(function ($q) use ($searchValue) {
                    $q->where('name', 'like', "%{$searchValue}%")
                        ->orWhere('email', 'like', "%{$searchValue}%");
                });
            }

            // Filtered records count
            $filteredRecords = (clone $query)->count();

            // Sorting (whitelisted columns only)
            $columns = ['id', 'name', 'email'];
            $orderColumn = $columns[$orderColumn] ?? 'id';
            $query->orderBy($orderColumn, $orderDirection);

            $users = $query->skip($start)->take($length)->get();

            // Format data for DataTables
            $formattedData = $users->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'roles' => $user->roles->pluck('name')->all(),
                    'roles_label' => $user->roles->pluck('name')->implode(', '),
                    'is_super_admin' => $user->hasRole(config('roles.super_admin_role')),
                    'is_self' => $user->id === auth()->id(),
                ];
            });

            return response()->json([
                'draw' => (int) $draw,
                'recordsTotal' => $totalRecords,
                'recordsFiltered' => $filteredRecords,
                'data' => $formattedData,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'draw' => (int) $request->input('draw', 1),
                'recordsTotal' => 0,
                'recordsFiltered' => 0,
                'data' => [],
            ]);
        }
    }

    public function create(): JsonResponse
    {
        return response()->json(['message' => 'Provide user data to create.'], 200);
    }

    public function store(StoreUserRequest $request): JsonResponse
    {
        $user = $this->users->create($request->validated());

        return response()->json(['success' => true, 'message' => 'User created successfully.', 'data' => $user], 201);
    }

    public function show(User $user): JsonResponse
    {
        return response()->json($user->load('roles:id,name'));
    }

    public function edit(User $user): JsonResponse
    {
        return response()->json($user->load('roles:id,name'));
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user = $this->users->update($user, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'User updated successfully.',
            'data' => $user,
        ], 200);
    }

    public function destroy(User $user): JsonResponse
    {
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot delete your own account.',
            ], 403);
        }

        if (! $this->users->canDelete($user)) {
            return response()->json([
                'success' => false,
                'message' => 'The Super Admin account cannot be deleted.',
            ], 403);
        }

        $user->delete();

        return response()->json(['success' => true, 'message' => 'User deleted successfully.'], 200);
    }
}
