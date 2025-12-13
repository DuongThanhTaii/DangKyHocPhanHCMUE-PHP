import { fetchJSON } from "../../../utils/fetchJSON";
import type { ServiceResult } from "../../common/ServiceResult";
import type { TKBWeeklyItemDTO, TietHoc } from "../types";
import { cachedFetch } from "../../../utils/apiCache";

export const gvAPI = {
    /**
     * Lấy TKB theo khoảng ngày (tuần) - CACHED
     */
    getTKBWeekly: async (
        hocKyId: string,
        dateStart: string, // YYYY-MM-DD
        dateEnd: string    // YYYY-MM-DD
    ): Promise<ServiceResult<TKBWeeklyItemDTO[]>> => {
        return cachedFetch(`gv/tkb-weekly/${hocKyId}/${dateStart}/${dateEnd}`, () =>
            fetchJSON(`gv/tkb-weekly?hoc_ky_id=${hocKyId}&date_start=${dateStart}&date_end=${dateEnd}`)
        );
    },

    /**
     * Lấy config tiết học - CACHED (long TTL since it rarely changes)
     */
    getTietHocConfig: async (): Promise<ServiceResult<TietHoc[]>> => {
        return cachedFetch("config/tiet-hoc", () =>
            fetchJSON("config/tiet-hoc")
        );
    },
};