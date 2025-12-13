import { useMemo, useState } from "react";
import "../../styles/reset.css";
import "../../styles/menu.css";
import { useTraCuuHocPhan } from "../../features/sv/hooks";
import type { MonHocTraCuuDTO } from "../../features/sv/types";
import HocKySelector from "../../components/HocKySelector";

// Helper to get display name for loaiMon
const getLoaiMonLabel = (loaiMon: string): string => {
  switch (loaiMon) {
    case "chuyen_nganh":
      return "Chuyên ngành";
    case "dai_cuong":
      return "Đại cương";
    case "tu_chon":
      return "Tự chọn";
    default:
      return "Khác";
  }
};

// Type for grouped data
interface GroupedByLoaiMon {
  loaiMon: string;
  loaiMonLabel: string;
  monHocs: MonHocTraCuuDTO[];
}

export default function TraCuuMonHoc() {
  const [selectedHocKyId, setSelectedHocKyId] = useState<string>("");
  const [searchQuery, setSearchQuery] = useState<string>("");
  const [loaiMonFilter, setLoaiMonFilter] = useState<string>("all");

  // ✅ Fetch data
  const { data: monHocs, loading: loadingData } =
    useTraCuuHocPhan(selectedHocKyId);

  // ✅ Filter data
  const filteredData = useMemo(() => {
    let result = monHocs;

    if (loaiMonFilter !== "all") {
      result = result.filter((mon) => mon.loaiMon === loaiMonFilter);
    }

    if (searchQuery.trim()) {
      const q = searchQuery.trim().toLowerCase();
      result = result.filter(
        (mon) =>
          mon.maMon.toLowerCase().includes(q) ||
          mon.tenMon.toLowerCase().includes(q)
      );
    }

    return result;
  }, [monHocs, loaiMonFilter, searchQuery]);

  // ✅ Group by loaiMon
  const groupedData = useMemo((): GroupedByLoaiMon[] => {
    const groups: Record<string, MonHocTraCuuDTO[]> = {};

    filteredData.forEach((mon) => {
      const key = mon.loaiMon || "khac";
      if (!groups[key]) {
        groups[key] = [];
      }
      groups[key].push(mon);
    });

    // Order: dai_cuong, chuyen_nganh, tu_chon, khac
    const order = ["dai_cuong", "chuyen_nganh", "tu_chon", "khac"];
    return order
      .filter((key) => groups[key] && groups[key].length > 0)
      .map((key) => ({
        loaiMon: key,
        loaiMonLabel: getLoaiMonLabel(key),
        monHocs: groups[key],
      }));
  }, [filteredData]);

  return (
    <section className="main__body">
      <div className="body__title">
        <p className="body__title-text">TRA CỨU HỌC PHẦN</p>
      </div>

      <div className="body__inner">
        {/* ✅ Filters */}
        <div className="selecy__duyethp__container">
          <HocKySelector onHocKyChange={setSelectedHocKyId} />

          {/* Loại môn */}
          <div className="mr_20">
            <select
              className="form__select w__200"
              value={loaiMonFilter}
              onChange={(e) => setLoaiMonFilter(e.target.value)}
              disabled={!selectedHocKyId}
            >
              <option value="all">Tất cả loại môn</option>
              <option value="chuyen_nganh">Chuyên ngành</option>
              <option value="dai_cuong">Đại cương</option>
              <option value="tu_chon">Tự chọn</option>
            </select>
          </div>

          {/* Search */}
          <div className="form__group__tracuu">
            <input
              type="text"
              className="form__input"
              placeholder=""
              value={searchQuery}
              onChange={(e) => setSearchQuery(e.target.value)}
              disabled={!selectedHocKyId}
            />
            <label className="form__floating-label">Tìm theo mã/tên môn</label>
          </div>
        </div>

        {/* ✅ Data Table - Grouped by loaiMon */}
        {loadingData ? (
          <p style={{ textAlign: "center", padding: 40 }}>
            Đang tải danh sách học phần...
          </p>
        ) : (
          <>
            {groupedData.map((group: GroupedByLoaiMon) => (
              <fieldset key={group.loaiMon} className="fieldeset__dkhp mt_20">
                <legend>
                  <span style={{ color: "#3b82f6", fontWeight: "bold" }}>
                    {group.loaiMonLabel}
                  </span>{" "}
                  ({group.monHocs.length} môn)
                </legend>

                <table className="table" style={{ color: "#172b4d" }}>
                  <thead>
                    <tr>
                      <th>STT</th>
                      <th>Mã môn</th>
                      <th>Tên môn</th>
                      <th>TC</th>
                      <th>Mã lớp</th>
                      <th>Giảng viên</th>
                      <th>Sĩ số</th>
                      <th>Thời khóa biểu</th>
                    </tr>
                  </thead>
                  <tbody>
                    {group.monHocs.map((mon: MonHocTraCuuDTO) => {
                      // If no classes, show one row with subject info
                      if (mon.danhSachLop.length === 0) {
                        return (
                          <tr key={mon.stt}>
                            <td>{mon.stt}</td>
                            <td>{mon.maMon}</td>
                            <td>{mon.tenMon}</td>
                            <td>{mon.soTinChi}</td>
                            <td colSpan={4} style={{ color: "#9ca3af", fontStyle: "italic" }}>
                              Chưa có lớp học phần
                            </td>
                          </tr>
                        );
                      }

                      // Render each class as a row, with subject info on first row
                      return mon.danhSachLop.map((lop: any, idx: number) => (
                        <tr key={`${mon.stt}-${lop.id}`}>
                          {idx === 0 ? (
                            <>
                              <td rowSpan={mon.danhSachLop.length}>{mon.stt}</td>
                              <td rowSpan={mon.danhSachLop.length}>{mon.maMon}</td>
                              <td rowSpan={mon.danhSachLop.length}>{mon.tenMon}</td>
                              <td rowSpan={mon.danhSachLop.length}>{mon.soTinChi}</td>
                            </>
                          ) : null}
                          <td>{lop.maLop}</td>
                          <td>{lop.giangVien}</td>
                          <td>
                            {lop.soLuongHienTai}/{lop.soLuongToiDa}
                          </td>
                          <td style={{ whiteSpace: "pre-line" }}>
                            {lop.thoiKhoaBieu}
                          </td>
                        </tr>
                      ));
                    })}
                  </tbody>
                </table>
              </fieldset>
            ))}

            {groupedData.length === 0 && selectedHocKyId && (
              <p style={{ textAlign: "center", padding: 40, color: "#6b7280" }}>
                {searchQuery || loaiMonFilter !== "all"
                  ? "Không tìm thấy môn học phù hợp với bộ lọc"
                  : "Chưa có học phần nào trong học kỳ này"}
              </p>
            )}

            {!selectedHocKyId && (
              <p style={{ textAlign: "center", padding: 40, color: "#6b7280" }}>
                Vui lòng chọn học kỳ để tra cứu
              </p>
            )}
          </>
        )}
      </div>
    </section>
  );
}
