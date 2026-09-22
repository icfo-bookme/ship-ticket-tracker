<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRoleRequest;
use App\Services\Admin\RoleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(private readonly RoleService $roles) {}

    /**
     * Display the roles management page.
     */
    public function showTableList()
    {
        $permissionGroups = $this->roles->permissionGroups();

        return view('roles.componentItem', compact('permissionGroups'));
    }

    /**
     * List all roles with their permissions (DataTables server-side JSON).
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

            $query = Role::with('permissions:id,name');

            // Total records count (without filtering)
            $totalRecords = (clone $query)->count();

            // Global search
            if (! empty($searchValue)) {
                $query->where('name', 'like', "%{$searchValue}%");
            }

            // Filtered records count
            $filteredRecords = (clone $query)->count();

            // Sorting (whitelisted columns only)
            $columns = ['id', 'name'];
            $orderColumn = $columns[$orderColumn] ?? 'id';
            $query->orderBy($orderColumn, $orderDirection);

            $roles = $query->skip($start)->take($length)->get();

            // Format data for DataTables
            $formattedData = $roles->map(function ($role) {
                return [
                    'id' => $role->id,
                    'name' => $role->name,
                    'permissions' => $role->permissions->pluck('name')->all(),
                    'permissions_count' => $role->permissions->count(),
                    'is_super_admin' => $role->name === config('roles.super_admin_role'),
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
        return response()->json(['message' => 'Provide role data to create.'], 200);
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $role = $this->roles->create($request->validated());

        return response()->json(['success' => true, 'message' => 'Role created successfully.', 'data' => $role], 201);
    }

    public function show(Role $role): JsonResponse
    {
        return response()->json($role->load('permissions:id,name'));
    }

    public function edit(Role $role): JsonResponse
    {
        return response()->json($role->load('permissions:id,name'));
    }

    public function update(UpdateRoleRequest $request, Role $role): JsonResponse
    {
        $role = $this->roles->update($role, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Role updated successfully.',
            'data' => $role,
        ], 200);
    }

    public function destroy(Role $role): JsonResponse
    {
        if (! $this->roles->delete($role)) {
            return response()->json([
                'success' => false,
                'message' => 'The Super Admin role cannot be deleted.',
            ], 403);
        }

        return response()->json(['success' => true, 'message' => 'Role deleted successfully.'], 200);
    }
}
