import { useState, useEffect, useCallback } from 'react';
import { adminApi } from '../api/adminApi';
import type { RolePermissionsResponse } from '../types';

export const useRolePermissions = (roleId: string | null) => {
    const [data, setData] = useState<RolePermissionsResponse | null>(null);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const fetchPermissions = useCallback(async () => {
        if (!roleId) return;

        setLoading(true);
        setError(null);
        try {
            const result = await adminApi.getRolePermissions(roleId);
            if (result.isSuccess && result.data) {
                setData(result.data);
            } else {
                setError(result.message || 'Lỗi tải permissions');
            }
        } catch (err) {
            setError('Lỗi kết nối server');
        } finally {
            setLoading(false);
        }
    }, [roleId]);

    useEffect(() => {
        fetchPermissions();
    }, [fetchPermissions]);

    const togglePermission = async (permissionId: string, isEnabled: boolean) => {
        if (!roleId) return false;

        try {
            const result = await adminApi.togglePermission(roleId, permissionId, isEnabled);
            if (result.isSuccess) {
                // Update local state
                setData(prev => {
                    if (!prev) return prev;
                    return {
                        ...prev,
                        enabledCount: isEnabled ? prev.enabledCount + 1 : prev.enabledCount - 1,
                        permissionGroups: prev.permissionGroups.map(group => ({
                            ...group,
                            permissions: group.permissions.map(perm =>
                                perm.id === permissionId
                                    ? { ...perm, isEnabled }
                                    : perm
                            )
                        }))
                    };
                });
                return true;
            }
            return false;
        } catch {
            return false;
        }
    };

    return { data, loading, error, refetch: fetchPermissions, togglePermission };
};
