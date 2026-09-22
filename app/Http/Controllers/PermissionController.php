<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\StorePermissionRequest;
use App\Http\Requests\Admin\UpdatePermissionRequest;
use App\Services\Admin\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function __construct(private readonly PermissionService $permissions) {}

    /**
     * Display the permissions management page.
     */
    public function showTableList()
    {
        return view('permissions.componentItem');
    }

    /**
     * List all permissions with the roles that use them
     * (DataTables server-side JSON).
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

            $query = Permission::with('roles:id,name');

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

            $permissions = $query->skip($start)->take($length)->get();

            // Format data for DataTables
            $formattedData = $permissions->map(function ($permission) {
                return [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'group' => ucfirst(str_contains($permission->name, '.') ? explode('.', $permission->name)[0] : 'Other'),
                    'roles' => $permission->roles->pluck('name')->all(),
                    'roles_count' => $permission->roles->count(),
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
        return response()->json(['message' => 'Provide permission data to create.'], 200);
    }

    public function store(StorePermissionRequest $request): JsonResponse
    {
        $permission = $this->permissions->create($request->validated());

        return response()->json(['success' => true, 'message' => 'Permission created successfully.', 'data' => $permission], 201);
    }

    public function show(Permission $permission): JsonResponse
    {
        return response()->json($permission->load('roles:id,name'));
    }

    public function edit(Permission $permission): JsonResponse
    {
        return response()->json($permission->load('roles:id,name'));
    }

    public function update(UpdatePermissionRequest $request, Permission $permission): JsonResponse
    {
        $permission = $this->permissions->update($permission, $request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Permission updated successfully.',
            'data' => $permission,
        ], 200);
    }

    public function destroy(Permission $permission): JsonResponse
    {
        if (! $this->permissions->canDelete($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'This permission is assigned to one or more roles. Remove it from those roles first.',
            ], 422);
        }

        $permission->delete();

        return response()->json(['success' => true, 'message' => 'Permission deleted successfully.'], 200);
    }
}
