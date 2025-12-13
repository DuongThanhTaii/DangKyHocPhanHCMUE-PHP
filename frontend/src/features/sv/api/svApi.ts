import type { ServiceResult } from "../../common/ServiceResult";
import type {
    MonHocGhiDanhForSinhVien,
    RequestGhiDanhMonHoc,
    RequestHuyGhiDanhMonHoc,
    MonHocDaGhiDanh,
    DanhSachLopHocPhanDTO,
    DangKyHocPhanRequest,
    HuyDangKyHocPhanRequest,
    ChuyenLopHocPhanRequest,
    LopHocPhanItemDTO,
    SVTKBWeeklyItemDTO,
    MonHocInfoDTO,
    ThanhToanHocPhiRequest,
    ChiTietHocPhiDTO,
    CreatePaymentRequest,
    CreatePaymentResponse,
    PaymentStatusResponse,
    LopDaDangKyWithTaiLieuDTO,
    SVTaiLieuDTO,
} from "../types";
import { fetchJSON } from "../../../utils/fetchJSON";
import type { LichSuDangKyDTO } from "../types";
import type { MonHocTraCuuDTO } from "../types";
import api from "../../../utils/api";
import { cachedFetch, clearCacheByPattern, SHORT_CACHE_TTL } from "../../../utils/apiCache";

// Helper to invalidate SV cache after mutations
const invalidateSvCache = () => {
    clearCacheByPattern("sv/");
};

export const svApi = {
    // GHI DANH - CACHED

    getMonHocGhiDanh: async (): Promise<ServiceResult<MonHocGhiDanhForSinhVien[]>> => {
        return cachedFetch("sv/mon-hoc-ghi-danh", () =>
            fetchJSON("sv/mon-hoc-ghi-danh", { method: "GET" })
        );
    },

    ghiDanhMonHoc: async (data: RequestGhiDanhMonHoc): Promise<ServiceResult<null>> => {
        invalidateSvCache(); // Clear cache after mutation
        return await fetchJSON("sv/ghi-danh", {
            method: "POST",
            body: data,
        });
    },

    huyGhiDanhMonHoc: async (data: RequestHuyGhiDanhMonHoc): Promise<ServiceResult<null>> => {
        invalidateSvCache();
        return await fetchJSON("sv/huy-ghi-danh", {
            method: "POST",
            body: data,
        });
    },

    getDanhSachDaGhiDanh: async (): Promise<ServiceResult<MonHocDaGhiDanh[]>> => {
        return cachedFetch("sv/ghi-danh/my", () =>
            fetchJSON("sv/ghi-danh/my", { method: "GET" })
        );
    },

    checkTrangThaiGhiDanh: async (): Promise<ServiceResult<null>> => {
        return cachedFetch("sv/check-ghi-danh", () =>
            fetchJSON("sv/check-ghi-danh", { method: "GET" }),
            SHORT_CACHE_TTL // 30 seconds for status checks
        );
    },

    // ĐĂNG KÝ HỌC PHẦN - CACHED

    checkPhaseDangKy: async (hocKyId: string): Promise<ServiceResult<null>> => {
        return cachedFetch(`sv/check-phase-dang-ky/${hocKyId}`, () =>
            fetchJSON(`sv/check-phase-dang-ky?hoc_ky_id=${hocKyId}`),
            SHORT_CACHE_TTL
        );
    },

    getDanhSachLopHocPhan: async (hocKyId: string): Promise<ServiceResult<DanhSachLopHocPhanDTO>> => {
        return cachedFetch(`sv/lop-hoc-phan/${hocKyId}`, () =>
            fetchJSON(`sv/lop-hoc-phan?hoc_ky_id=${hocKyId}`)
        );
    },

    getLopDaDangKy: async (hocKyId: string): Promise<ServiceResult<MonHocInfoDTO[]>> => {
        return cachedFetch(`sv/lop-da-dang-ky/${hocKyId}`, () =>
            fetchJSON(`sv/lop-da-dang-ky?hoc_ky_id=${hocKyId}`)
        );
    },

    dangKyLopHocPhan: async (data: DangKyHocPhanRequest): Promise<ServiceResult<null>> => {
        invalidateSvCache();
        return await fetchJSON("sv/dang-ky-hoc-phan", {
            method: "POST",
            body: data,
        });
    },

    /**
     * Hủy đăng ký học phần (1 lớp)
     */
    huyDangKyLopHocPhan: async (data: HuyDangKyHocPhanRequest): Promise<ServiceResult<null>> => {
        invalidateSvCache();
        return await fetchJSON("sv/huy-dang-ky-hoc-phan", {
            method: "POST",
            body: data,
        });
    },

    /**
     * Chuyển lớp học phần
     */
    chuyenLopHocPhan: async (data: ChuyenLopHocPhanRequest): Promise<ServiceResult<null>> => {
        invalidateSvCache();
        return await fetchJSON("sv/chuyen-lop-hoc-phan", {
            method: "POST",
            body: data,
        });
    },

    /**
     * Load danh sách lớp chưa đăng ký theo môn (để chuyển lớp) - CACHED
     */
    getLopChuaDangKyByMonHoc: async (
        monHocId: string,
        hocKyId: string
    ): Promise<ServiceResult<LopHocPhanItemDTO[]>> => {
        return cachedFetch(`sv/lop-hoc-phan/mon-hoc/${monHocId}/${hocKyId}`, () =>
            fetchJSON(`sv/lop-hoc-phan/mon-hoc?mon_hoc_id=${monHocId}&hoc_ky_id=${hocKyId}`)
        );
    },

    /**
     * Lấy lịch sử đăng ký theo học kỳ - CACHED
     */
    getLichSuDangKy: async (hocKyId: string): Promise<ServiceResult<LichSuDangKyDTO>> => {
        return cachedFetch(`sv/lich-su-dang-ky/${hocKyId}`, () =>
            fetchJSON(`sv/lich-su-dang-ky?hoc_ky_id=${hocKyId}`, { method: "GET" })
        );
    },

    /**
     * Lấy TKB theo tuần (sinh viên) - CACHED
     */
    getTKBWeekly: async (
        hocKyId: string,
        dateStart: string, // YYYY-MM-DD
        dateEnd: string    // YYYY-MM-DD
    ): Promise<ServiceResult<SVTKBWeeklyItemDTO[]>> => {
        return cachedFetch(`sv/tkb-weekly/${hocKyId}/${dateStart}/${dateEnd}`, () =>
            fetchJSON(`sv/tkb-weekly?hoc_ky_id=${hocKyId}&date_start=${dateStart}&date_end=${dateEnd}`)
        );
    },

    /**
     * Tra cứu học phần theo học kỳ - CACHED
     */
    traCuuHocPhan: async (
        hocKyId: string
    ): Promise<ServiceResult<MonHocTraCuuDTO[]>> => {
        return cachedFetch(`sv/tra-cuu-hoc-phan/${hocKyId}`, () =>
            fetchJSON(`sv/tra-cuu-hoc-phan?hoc_ky_id=${hocKyId}`)
        );
    },

    /**
     * Lấy chi tiết học phí theo học kỳ - CACHED
     */
    getChiTietHocPhi: async (hocKyId: string): Promise<ServiceResult<ChiTietHocPhiDTO>> => {
        return cachedFetch(`sv/hoc-phi/${hocKyId}`, () =>
            fetchJSON(`sv/hoc-phi?hoc_ky_id=${hocKyId}`)
        );
    },

    /**
     * Thanh toán học phí (mock)
     */
    thanhToanHocPhi: async (data: ThanhToanHocPhiRequest): Promise<ServiceResult<any>> => {
        return await fetchJSON("hoc-phi/thanh-toan", {
            method: "POST",
            body: data,
        });
    },

    /**
     * Tạo payment MoMo (ONLY hocKyId)
     */
    createPayment: async (data: CreatePaymentRequest): Promise<ServiceResult<CreatePaymentResponse>> => {
        return await fetchJSON("payment/create", {
            method: "POST",
            body: data,
        });
    },

    /**
     * Get payment status with query parameter
     */
    getPaymentStatus: async (orderId: string): Promise<ServiceResult<PaymentStatusResponse>> => {
        if (!orderId || !orderId.trim()) {
            return {
                isSuccess: false,
                message: "orderId không hợp lệ",
                error: "INVALID_ORDER_ID",
            };
        }

        const cleanOrderId = orderId.trim();

        try {
            const result = await fetchJSON(`payment/status?orderId=${encodeURIComponent(cleanOrderId)}`, {
                method: "GET",
            });

            return result;
        } catch (error: any) {
            console.error("getPaymentStatus error:", error);

            return {
                isSuccess: false,
                message: error.message || "Không thể lấy trạng thái thanh toán",
                error: error.code || "FETCH_ERROR",
            };
        }
    },

    // TÀI LIỆU HỌC TẬP

    /**
     * Lấy danh sách lớp đã đăng ký kèm tài liệu
     */
    getLopDaDangKyWithTaiLieu: async (hocKyId: string): Promise<ServiceResult<LopDaDangKyWithTaiLieuDTO[]>> => {
        return await fetchJSON(`sv/lop-da-dang-ky/tai-lieu?hoc_ky_id=${hocKyId}`);
    },

    /**
     * Lấy tài liệu của một lớp học phần
     */
    getTaiLieuByLopHocPhan: async (lopHocPhanId: string): Promise<ServiceResult<SVTaiLieuDTO[]>> => {
        return await fetchJSON(`sv/lop-hoc-phan/${lopHocPhanId}/tai-lieu`);
    },

    /**
     * Download tài liệu (backend streams from S3)
     */
    downloadTaiLieu: async (lopHocPhanId: string, docId: string): Promise<Blob | null> => {
        try {
            const response = await api.get(
                `sv/lop-hoc-phan/${lopHocPhanId}/tai-lieu/${docId}/download`,
                {
                    responseType: "blob",
                }
            );

            if (response.status === 200 && response.data) {
                return response.data as Blob;
            }

            return null;
        } catch (error) {
            console.error("Download tài liệu failed:", error);
            return null;
        }
    },
};
