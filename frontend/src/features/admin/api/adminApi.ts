import { fetchJSON } from '../../../utils/fetchJSON';
import type { Role, RolePermissionsResponse } from '../types';

interface ServiceResult<T> {
    isSuccess: boolean;
    data: T | null;
    message?: string;
}

const API_BASE = '/admin';

export const adminApi = {
    /**
     * Get all roles
     */
    getRoles: async (): Promise<ServiceResult<Role[]>> => {
        return fetchJSON(`${API_BASE}/roles`);
    },

    /**
     * Get permissions for a role
     */
    getRolePermissions: async (roleId: string): Promise<ServiceResult<RolePermissionsResponse>> => {
        return fetchJSON(`${API_BASE}/roles/${roleId}/permissions`);
    },

    /**
     * Toggle permission for a role
     */
    togglePermission: async (
        roleId: string,
        permissionId: string,
        isEnabled: boolean
    ): Promise<ServiceResult<{ roleId: string; permissionId: string; isEnabled: boolean }>> => {
        return fetchJSON(`${API_BASE}/roles/${roleId}/permissions/${permissionId}`, {
            method: 'PUT',
            body: JSON.stringify({ isEnabled }),
        });
    },
};
