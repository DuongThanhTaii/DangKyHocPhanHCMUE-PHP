// Admin RBAC Types

export interface Role {
    id: string;
    code: string;
    name: string;
    description: string | null;
    isSystem: boolean;
}

export interface Permission {
    id: string;
    routeName: string | null;
    routePath: string;
    method: string;
    description: string | null;
    isPublic: boolean;
    isEnabled: boolean;
}

export interface PermissionGroup {
    module: string;
    permissions: Permission[];
}

export interface RolePermissionsResponse {
    role: Pick<Role, 'id' | 'code' | 'name' | 'description'>;
    permissionGroups: PermissionGroup[];
    totalPermissions: number;
    enabledCount: number;
}
