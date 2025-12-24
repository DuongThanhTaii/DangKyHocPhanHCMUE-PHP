import { useState, useEffect, useCallback } from 'react';
import { adminApi } from '../api/adminApi';
import type { Role } from '../types';

export const useRoles = () => {
    const [roles, setRoles] = useState<Role[]>([]);
    const [loading, setLoading] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const fetchRoles = useCallback(async () => {
        setLoading(true);
        setError(null);
        try {
            const result = await adminApi.getRoles();
            if (result.isSuccess && result.data) {
                setRoles(result.data);
            } else {
                setError(result.message || 'Lỗi tải danh sách roles');
            }
        } catch (err) {
            setError('Lỗi kết nối server');
        } finally {
            setLoading(false);
        }
    }, []);

    useEffect(() => {
        fetchRoles();
    }, [fetchRoles]);

    return { roles, loading, error, refetch: fetchRoles };
};
