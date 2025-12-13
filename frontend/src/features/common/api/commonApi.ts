import { fetchJSON } from "../../../utils/fetchJSON";
import type { ServiceResult } from "../ServiceResult";
import type { NganhDTO } from "../types";
import type { HocKyNienKhoaDTO, HocKyHienHanhDTO } from "../types";

/**
 * Simple in-memory cache with TTL and request deduplication
 * This prevents 200+ duplicate requests when multiple components mount simultaneously
 */
const cache = new Map<string, { data: any; expiry: number }>();
const pendingRequests = new Map<string, Promise<any>>();
const CACHE_TTL = 5 * 60 * 1000; // 5 minutes

async function cachedFetch<T>(key: string, fetcher: () => Promise<T>): Promise<T> {
    // 1. Check cache first
    const cached = cache.get(key);
    if (cached && Date.now() < cached.expiry) {
        return cached.data as T;
    }

    // 2. Check if there's already a pending request for this key (deduplication)
    if (pendingRequests.has(key)) {
        return pendingRequests.get(key);
    }

    // 3. Make new request and cache the promise
    const promise = fetcher().then((result) => {
        // Cache successful results
        cache.set(key, { data: result, expiry: Date.now() + CACHE_TTL });
        pendingRequests.delete(key);
        return result;
    }).catch((error) => {
        pendingRequests.delete(key);
        throw error;
    });

    pendingRequests.set(key, promise);
    return promise;
}

// Clear cache utility (useful after login/logout)
export const clearApiCache = () => {
    cache.clear();
    pendingRequests.clear();
};

/**
 * Common API - Public endpoints (Auth required, all roles)
 */
export const commonApi = {
    /**
     * Lấy học kỳ hiện hành (public, auth required) - CACHED
     */
    getHocKyHienHanh: async (): Promise<ServiceResult<HocKyHienHanhDTO>> => {
        return cachedFetch("hoc-ky-hien-hanh", () =>
            fetchJSON("hoc-ky-hien-hanh", { method: "GET" })
        );
    },

    /**
     * Lấy danh sách học kỳ kèm niên khóa (public, auth required) - CACHED
     */
    getHocKyNienKhoa: async (): Promise<ServiceResult<HocKyNienKhoaDTO[]>> => {
        return cachedFetch("hoc-ky-nien-khoa", () =>
            fetchJSON("hoc-ky-nien-khoa", { method: "GET" })
        );
    },

    /**
     * Lấy danh sách ngành (có thể filter theo khoa_id) - CACHED per khoaId
     */
    getDanhSachNganh: async (khoaId?: string): Promise<ServiceResult<NganhDTO[]>> => {
        const key = khoaId ? `nganh-${khoaId}` : "nganh-all";
        const url = khoaId ? `dm/nganh?khoa_id=${khoaId}` : "dm/nganh";

        return cachedFetch(key, () =>
            fetchJSON(url, { method: "GET" })
        );
    },

    /**
     * Cập nhật ngày bắt đầu và kết thúc học kỳ - NOT CACHED (mutation)
     */
    updateHocKyDate: async (data: {
        hocKyId: string;
        ngayBatDau: string;
        ngayKetThuc: string;
    }): Promise<ServiceResult<null>> => {
        // Invalidate related cache after mutation
        cache.delete("hoc-ky-hien-hanh");
        cache.delete("hoc-ky-nien-khoa");

        return await fetchJSON("hoc-ky/dates", {
            method: "PATCH",
            body: JSON.stringify(data),
        });
    },
};
