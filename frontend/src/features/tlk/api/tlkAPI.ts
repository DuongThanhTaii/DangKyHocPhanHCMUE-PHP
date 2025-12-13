import { fetchJSON } from "../../../utils/fetchJSON";
import type { ServiceResult } from "../../common/ServiceResult";
import type {
    DeXuatHocPhanRequest,
    DeXuatHocPhanForTroLyKhoaDTO,
    HocPhanForCreateLopDTO,
    PhongHocDTO,
    XepTKBRequest,
    ThoiKhoaBieuMonHocDTO,
    MonHocDTO,
    GiangVienDTO,
    HocKyHienHanhResponse,
} from "../types";
import { cachedFetch, clearCacheByPattern, SHORT_CACHE_TTL } from "../../../utils/apiCache";

// Helper to invalidate TLK cache after mutations
const invalidateTlkCache = () => {
    clearCacheByPattern("tlk/");
};

export const tlkAPI = {
    // ============ Học kỳ hiện hành ============
    /**
     * Lấy thông tin học kỳ hiện hành - CACHED
     */
    getHocKyHienHanh: async (): Promise<ServiceResult<HocKyHienHanhResponse>> => {
        return cachedFetch("tlk/hien-hanh", () =>
            fetchJSON("hien-hanh", { method: "GET" })
        );
    },

    // ============ Môn học theo khoa của TLK ============
    /**
     * Lấy danh sách môn học theo khoa của TLK - CACHED
     */
    getMonHoc: async (): Promise<ServiceResult<MonHocDTO[]>> => {
        return cachedFetch("tlk/mon-hoc", () =>
            fetchJSON("tlk/mon-hoc", { method: "GET" })
        );
    },

    // ============ Giảng viên theo khoa của TLK ============
    /**
     * Lấy danh sách giảng viên theo khoa của TLK - CACHED
     * @param monHocId - Optional: filter by mon_hoc_id
     */
    getGiangVien: async (monHocId?: string): Promise<ServiceResult<GiangVienDTO[]>> => {
        const url = monHocId ? `tlk/giang-vien?mon_hoc_id=${monHocId}` : "tlk/giang-vien";
        const key = monHocId ? `tlk/giang-vien/${monHocId}` : "tlk/giang-vien";
        return cachedFetch(key, () =>
            fetchJSON(url, { method: "GET" })
        );
    },

    // ============ Đề xuất học phần ============
    /**
     * Tạo đề xuất học phần
     */
    createDeXuatHocPhan: async (data: DeXuatHocPhanRequest): Promise<ServiceResult<null>> => {
        invalidateTlkCache();
        return await fetchJSON("tlk/de-xuat-hoc-phan", {
            method: "POST",
            body: data,
        });
    },

    getDeXuatHocPhan: async (): Promise<ServiceResult<DeXuatHocPhanForTroLyKhoaDTO[]>> => {
        return cachedFetch("tlk/de-xuat-hoc-phan", () =>
            fetchJSON("tlk/de-xuat-hoc-phan", { method: "GET" }),
            SHORT_CACHE_TTL
        );
    },

    /**
     * COPY từ PDT - Lấy phòng học available - CACHED
     */
    getAvailablePhongHoc: async (): Promise<ServiceResult<PhongHocDTO[]>> => {
        return cachedFetch("tlk/phong-hoc/available", () =>
            fetchJSON("tlk/phong-hoc/available", { method: "GET" })
        );
    },

    /**
     * MOVE từ PDT - Lấy học phần để tạo lớp
     */
    getHocPhansForCreateLop: async (hocKyId: string): Promise<ServiceResult<HocPhanForCreateLopDTO[]>> => {
        return await fetchJSON(`tlk/lop-hoc-phan/get-hoc-phan/${hocKyId}`, {
            method: "GET",
        });
    },

    /**
     * Lấy tất cả phòng học của TLK (có thể filter theo khoa)
     */
    getPhongHocByTLK: async (): Promise<ServiceResult<PhongHocDTO[]>> => {
        return await fetchJSON("tlk/phong-hoc", {
            method: "GET",
        });
    },

    /**
     * Lấy TKB đã có của nhiều môn học
     */
    getTKBByMaHocPhans: async (
        maHocPhans: string[],
        hocKyId: string
    ): Promise<ServiceResult<ThoiKhoaBieuMonHocDTO[]>> => {
        return await fetchJSON("tlk/thoi-khoa-bieu/batch", {
            method: "POST",
            body: { maHocPhans, hocKyId },
        });
    },

    /**
     * Xếp thời khóa biểu
     */
    xepThoiKhoaBieu: async (data: XepTKBRequest): Promise<ServiceResult<any>> => {
        return await fetchJSON("tlk/thoi-khoa-bieu", {
            method: "POST",
            body: data,
        });
    },
};