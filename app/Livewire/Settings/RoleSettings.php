<?php

namespace App\Livewire\Settings;

use App\Models\Module;
use App\Models\Role;
use App\Models\Restaurant;
use App\Models\User;
use App\Enums\ActivityEvent;
use App\Support\ActivityLogger;
use Livewire\Component;
use Illuminate\Support\Str;
use Jantinnerezo\LivewireAlert\LivewireAlert;

class RoleSettings extends Component
{
    use LivewireAlert;

    public $permissions;
    public $roles;

    // Modal properties
    public $showAddRoleModal = false;
    
    // Role form properties
    public $newRoleDisplayName = '';
    public $copyFromRole = '';
    public $editingRoleId = null;
    public $editingRoleDisplayName = '';
    public $inlineEditingRoleId = null;
    public $inlineEditingRoleName = '';
    
    // Delete role properties
    public $deletingRoleId = null;
    public $deletingRoleName = '';
    public $usersWithDeletingRole = [];
    public $reassignRoleId = '';
    public $showReassignModal = false;

    
    public function mount()
    {
        $this->permissions = $this->enabledModules(['permissions.roles']);

        $this->roles = Role::whereNotIn('display_name', ['Admin', 'Super Admin'])
            ->where('restaurant_id', restaurant()->id)
            ->get();
    }

    private function enabledModules(array $relations = [])
    {
        return Module::with($relations)->excludeDeprecated()->get()
            ->filter(function ($m) {
                if ($m->name === 'Sms') {
                    return module_enabled('Sms') && in_array('Sms', restaurant_modules());
                }

                if ($m->name === 'Hotel') {
                    return module_enabled('Hotel') && in_array('Hotel', restaurant_modules());
                }

                return true;
            });
    }

    public function setPermission($roleID, $permissionID)
    {
        $role = Role::find($roleID);
        $role->givePermissionTo($permissionID);

        $permission = \Spatie\Permission\Models\Permission::find($permissionID);

        ActivityLogger::recordEvent(
            activityEvent: ActivityEvent::RolePermissionGranted,
            description: "Permission granted to role {$role->display_name}: " . ($permission?->name ?? $permissionID),
            properties: [
                'role_id' => $role->id,
                'role_name' => $role->display_name,
                'permission_id' => $permissionID,
                'permission_name' => $permission?->name,
            ],
            restaurantId: restaurant()?->id ? (int) restaurant()->id : null,
        );
    }

    public function removePermission($roleID, $permissionID)
    {
        $role = Role::find($roleID);
        $role->revokePermissionTo($permissionID);

        $permission = \Spatie\Permission\Models\Permission::find($permissionID);

        ActivityLogger::recordEvent(
            activityEvent: ActivityEvent::RolePermissionRevoked,
            description: "Permission revoked from role {$role->display_name}: " . ($permission?->name ?? $permissionID),
            properties: [
                'role_id' => $role->id,
                'role_name' => $role->display_name,
                'permission_id' => $permissionID,
                'permission_name' => $permission?->name,
            ],
            restaurantId: restaurant()?->id ? (int) restaurant()->id : null,
        );
    }

    public function showAddRole()
    {
        $this->showAddRoleModal = true;
        $this->resetRoleForm();
    }

    public function closeAddRoleModal()
    {
        $this->showAddRoleModal = false;
        $this->resetRoleForm();
        $this->resetDeleteForm();
    }

    public function updatedShowAddRoleModal($value)
    {
        if (!$value) {
            $this->resetRoleForm();
            $this->resetDeleteForm();
        }
    }

    public function resetRoleForm()
    {
        $this->newRoleDisplayName = '';
        $this->copyFromRole = '';
        $this->editingRoleId = null;
        $this->editingRoleDisplayName = '';
        $this->inlineEditingRoleId = null;
        $this->inlineEditingRoleName = '';
        $this->resetErrorBag();
    }

    public function resetDeleteForm()
    {
        $this->deletingRoleId = null;
        $this->deletingRoleName = '';
        $this->usersWithDeletingRole = [];
        $this->reassignRoleId = '';
        $this->showReassignModal = false;
    }

    public function startInlineEdit($roleId, $roleName)
    {
        // Check if role is protected
        $protectedRoles = ['Admin', 'Super Admin', 'Branch Head', 'Waiter', 'Chef'];
        $role = Role::find($roleId);
        
        if (in_array($role->display_name, $protectedRoles)) {
            $this->alert('error', __('messages.roleCannotBeUpdated'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
            return;
        }
        
        $this->inlineEditingRoleId = $roleId;
        $this->inlineEditingRoleName = $roleName;
    }

    public function saveInlineEdit()
    {
        $this->validate([
            'inlineEditingRoleName' => 'required|string|max:255',
        ]);

        try {
            $role = Role::find($this->inlineEditingRoleId);
            
            if (!$role) {
                $this->alert('error', __('messages.roleNotFound'), [
                    'toast' => true,
                    'position' => 'top-end',
                    'showCancelButton' => false,
                    'cancelButtonText' => __('app.close')
                ]);
                return;
            }

            // Check if new display name already exists for current restaurant
            $existingRole = Role::where('display_name', $this->inlineEditingRoleName)
                               ->where('id', '<>', $this->inlineEditingRoleId)
                               ->where('display_name', '<>', 'Admin')
                               ->where('display_name', '<>', 'Super Admin')
                               ->where('restaurant_id', restaurant()->id)
                               ->first();

            if ($existingRole) {
                $this->addError('inlineEditingRoleName', 'Role display name already exists for this restaurant.');
                return;
            }

            // Update both name and display_name
            $role->update([
                'name' => $this->inlineEditingRoleName . '_' . restaurant()->id,
                'display_name' => $this->inlineEditingRoleName,
            ]);

            ActivityLogger::recordEvent(
                activityEvent: ActivityEvent::RoleUpdated,
                description: "Role renamed to {$this->inlineEditingRoleName}",
                properties: [
                    'role_id' => $role->id,
                    'display_name' => $this->inlineEditingRoleName,
                ],
                restaurantId: restaurant()?->id ? (int) restaurant()->id : null,
            );

            // Refresh the roles list
            $this->roles = Role::where('display_name', '<>', 'Admin')
                              ->where('display_name', '<>', 'Super Admin')
                              ->where('restaurant_id', restaurant()->id)
                              ->get();

            // Show success message
            $this->alert('success', __('messages.roleUpdatedSuccessfully'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);

            // Reset inline editing
            $this->inlineEditingRoleId = null;
            $this->inlineEditingRoleName = '';

        } catch (\Exception $e) {
            $this->alert('error', __('messages.errorUpdatingRole') . $e->getMessage(), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
        }
    }

    public function cancelInlineEdit()
    {
        $this->inlineEditingRoleId = null;
        $this->inlineEditingRoleName = '';
        $this->resetErrorBag();
    }

    public function createRole()
    {
        $this->validate([
            'newRoleDisplayName' => 'required|string|max:255',
        ]);

        // Check if role display name already exists for current restaurant
        $existingRole = Role::where('display_name', $this->newRoleDisplayName)
                           ->where('display_name', '<>', 'Admin')
                           ->where('display_name', '<>', 'Super Admin')
                           ->where('restaurant_id', restaurant()->id)
                           ->first();

        if ($existingRole) {
            $this->addError('newRoleDisplayName', 'Role display name already exists for this restaurant.');
            return;
        }

        try {
            // Get current restaurant
            $currentRestaurant = restaurant();

            // Get source role permissions if copying
            $sourcePermissions = null;
            if ($this->copyFromRole) {
                $copyRole = Role::find($this->copyFromRole);
                if ($copyRole) {
                    $sourcePermissions = $copyRole->permissions;
                }
            }

            // Create the role only for current restaurant
            $role = Role::create([
                'name' => $this->newRoleDisplayName . '_' . $currentRestaurant->id,
                'display_name' => $this->newRoleDisplayName,
                'guard_name' => 'web',
                'restaurant_id' => $currentRestaurant->id,
            ]);

            // Copy permissions to this restaurant's role if source permissions exist
            if ($sourcePermissions) {
                $role->syncPermissions($sourcePermissions);
            }

            ActivityLogger::recordEvent(
                activityEvent: ActivityEvent::RoleCreated,
                description: "Role created: {$this->newRoleDisplayName}",
                properties: [
                    'role_id' => $role->id,
                    'display_name' => $this->newRoleDisplayName,
                    'copied_from_role_id' => $this->copyFromRole ?: null,
                ],
                restaurantId: (int) $currentRestaurant->id,
            );

            // Refresh the roles list for current restaurant
            $this->roles = Role::where('display_name', '<>', 'Admin')
                              ->where('display_name', '<>', 'Super Admin')
                              ->where('restaurant_id', restaurant()->id)
                              ->get();

            // Show success message
            $this->alert('success', __('messages.roleCreatedSuccessfully'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);

            // Close modal and reset form
            $this->showAddRoleModal = false;
            $this->resetRoleForm();

        } catch (\Exception $e) {
            $this->alert('error', __('messages.errorCreatingRole') . $e->getMessage(), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
        }
    }

    public function deleteRole($roleId)
    {
        // Check if role is protected
        $protectedRoles = ['Admin', 'Super Admin', 'Branch Head', 'Waiter', 'Chef'];
        $role = Role::find($roleId);
        
        if (in_array($role->display_name, $protectedRoles)) {
            $this->alert('error', __('messages.roleCannotBeDeleted'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
            return;
        }
        
        $this->deletingRoleId = $roleId;
        $this->deletingRoleName = $role->display_name;
        $this->usersWithDeletingRole = User::whereHas('roles', function($query) use ($roleId) {
            $query->where('roles.id', $roleId);
        })->get();
        $this->showReassignModal = true;
    }

    public function confirmDeleteRole()
    {
        $this->validate([
            'reassignRoleId' => 'required|exists:roles,id',
        ]);

        try {
            $deletingRole = Role::find($this->deletingRoleId);
            $reassignRole = Role::find($this->reassignRoleId);

            if (!$deletingRole || !$reassignRole) {
                $this->alert('error', __('messages.roleNotFound'), [
                    'toast' => true,
                    'position' => 'top-end',
                    'showCancelButton' => false,
                    'cancelButtonText' => __('app.close')
                ]);
                return;
            }

            // Reassign users to the new role
            foreach ($this->usersWithDeletingRole as $user) {
                $user->removeRole($deletingRole);
                $user->assignRole($reassignRole);
            }

            // Delete the role
            $deletingRole->delete();

            ActivityLogger::recordEvent(
                activityEvent: ActivityEvent::RoleDeleted,
                description: "Role deleted: {$deletingRole->display_name}",
                properties: [
                    'role_id' => $deletingRole->id,
                    'display_name' => $deletingRole->display_name,
                    'reassigned_to_role_id' => $reassignRole->id,
                    'users_reassigned' => count($this->usersWithDeletingRole),
                ],
                restaurantId: restaurant()?->id ? (int) restaurant()->id : null,
            );

            // Refresh the roles list
            $this->roles = Role::where('display_name', '<>', 'Admin')
                              ->where('display_name', '<>', 'Super Admin')
                              ->where('restaurant_id', restaurant()->id)
                              ->get();

            // Show success message
            $this->alert('success', __('messages.roleDeletedAndUsersReassigned'), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);

            // Close reassign modal
            $this->showReassignModal = false;
            $this->resetDeleteForm();

        } catch (\Exception $e) {
            $this->alert('error', __('messages.errorDeletingRole') . $e->getMessage(), [
                'toast' => true,
                'position' => 'top-end',
                'showCancelButton' => false,
                'cancelButtonText' => __('app.close')
            ]);
        }
    }

    public function render()
    {
        return view('livewire.settings.role-settings');
    }

}
