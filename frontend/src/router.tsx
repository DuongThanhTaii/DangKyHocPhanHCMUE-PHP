import { lazy, Suspense } from "react";
import { createBrowserRouter, Navigate } from "react-router-dom";
import ProtectedRoute from "./components/ProtectedRoute";
import PageLoader from "./components/loader/PageLoader";

// ============================================================
// STATIC IMPORTS - Các pages cần load ngay (không lazy)
// LoginPage & ResetPassword load ngay vì là trang đầu tiên user thấy
// Layouts cũng cần load tĩnh vì wrap child routes
// ============================================================
import LoginPage from "./pages/LoginPage";
import ResetPassword from "./pages/ResetPassword";

import PDTLayout from "./layouts/PDTLayout";
import GVLayout from "./layouts/GVLayout";
import TroLyKhoaLayout from "./layouts/TroLyKhoaLayout";
import TruongKhoaLayout from "./layouts/TruongKhoaLayout";
import SVLayout from "./layouts/SVLayout";

// ============================================================
// LAZY IMPORTS - Các pages được load khi cần (code splitting)
// Giúp giảm initial bundle size đáng kể
// ============================================================

// PDT Pages
const ChuyenHocKyHienHanh = lazy(() => import("./pages/pdt/ChuyenHocKyHienHanh"));
const ChuyenTrangThai = lazy(() => import("./pages/pdt/ChuyenTrangThai"));
const PDTDuyetHocPhan = lazy(() => import("./pages/pdt/DuyetHocPhan-PDT"));
const QuanLyNoiBo = lazy(() => import("./pages/pdt/QuanLyNoiBo"));
const BaoCaoThongKe = lazy(() => import("./pages/pdt/ThongKeDashboard"));
const ControlPanel = lazy(() => import("./pages/pdt/ControlPanel"));
const PhanBoPhongHoc = lazy(() => import("./pages/pdt/PhanBoPhongHoc"));

// GV Pages
const GVLopHocPhanList = lazy(() => import("./pages/gv/GVLopHocPhanList"));
const GVLopHocPhanDetail = lazy(() => import("./pages/gv/GVLopHocPhanDetail"));
const GVThoiKhoaBieu = lazy(() => import("./pages/gv/GVThoiKhoaBieu"));

// TLK Pages
const TaoLopHocPhan = lazy(() => import("./pages/tlk/TaoLopHocPhan"));
const LenDanhSachHocPhan = lazy(() => import("./pages/tlk/LenDanhSachHocPhan"));
const TlkDuyetHocPhan = lazy(() => import("./pages/tlk/DuyetHocPhan-TLK"));

// TK Pages
const TkDuyetHocPhan = lazy(() => import("./pages/tk/DuyetHocPhan-TK"));

// SV Pages
const GhiDanhHocPhan = lazy(() => import("./pages/sv/GhiDanhHocPhan"));
const TraCuuMonHoc = lazy(() => import("./pages/sv/TraCuuMonHoc"));
const LichSuDangKy = lazy(() => import("./pages/sv/LichSuDangKyHocPhan"));
const XemThoiKhoaBieu = lazy(() => import("./pages/sv/XemThoiKhoaBieu"));
const DangKyHocPhan = lazy(() => import("./pages/sv/DangKyHocPhan"));
const ThanhToanHocPhi = lazy(() => import("./pages/sv/ThanhToanHocPhi"));
const PaymentResult = lazy(() => import("./pages/sv/PaymentResult"));
const DemoPaymentPage = lazy(() => import("./pages/sv/DemoPaymentPage"));
const SVLopHocPhanList = lazy(() => import("./pages/sv/SVLopHocPhanList"));
const SVLopHocPhanDetail = lazy(() => import("./pages/sv/SVLopHocPhanDetail"));
const TaiLieuHocTap = lazy(() => import("./pages/sv/TaiLieuHocTap"));

// ============================================================
// HELPER COMPONENT - Wrap lazy components với Suspense + Loader
// ============================================================
const LazyPage = ({ children }: { children: React.ReactNode }) => (
  <Suspense fallback={<PageLoader text="Đang tải trang" />}>
    {children}
  </Suspense>
);

// ============================================================
// ROUTER CONFIGURATION
// ============================================================
export const router = createBrowserRouter([
  // Public routes - load tĩnh
  { path: "/", element: <LoginPage /> },
  { path: "/reset-password", element: <ResetPassword /> },

  // Payment result - lazy vì không thường xuyên access
  { path: "/payment/result", element: <LazyPage><PaymentResult /></LazyPage> },

  // PDT Routes
  {
    path: "/pdt",
    element: (
      <ProtectedRoute allow={["phong_dao_tao"]}>
        <PDTLayout />
      </ProtectedRoute>
    ),
    children: [
      { index: true, element: <Navigate to="chuyen-trang-thai" replace /> },
      { path: "chuyen-trang-thai", element: <LazyPage><ChuyenTrangThai /></LazyPage> },
      { path: "duyet-hoc-phan", element: <LazyPage><PDTDuyetHocPhan /></LazyPage> },
      { path: "quan-ly", element: <LazyPage><QuanLyNoiBo /></LazyPage> },
      { path: "thong-ke-dashboard", element: <LazyPage><BaoCaoThongKe /></LazyPage> },
      { path: "chuyen-hoc-ky", element: <LazyPage><ChuyenHocKyHienHanh /></LazyPage> },
      { path: "phan-bo-phong-hoc", element: <LazyPage><PhanBoPhongHoc /></LazyPage> },
      { path: "control-panel", element: <LazyPage><ControlPanel /></LazyPage> },
    ],
  },

  // GV Routes
  {
    path: "/gv",
    element: (
      <ProtectedRoute allow={["giang_vien"]}>
        <GVLayout />
      </ProtectedRoute>
    ),
    children: [
      { index: true, element: <Navigate to="lop-hoc-phan" replace /> },
      { path: "lop-hoc-phan", element: <LazyPage><GVLopHocPhanList /></LazyPage> },
      { path: "lop-hoc-phan/:id", element: <LazyPage><GVLopHocPhanDetail /></LazyPage> },
      { path: "thoi-khoa-bieu", element: <LazyPage><GVThoiKhoaBieu /></LazyPage> },
    ],
  },

  // TLK Routes
  {
    path: "/tlk",
    element: (
      <ProtectedRoute allow={["tro_ly_khoa"]}>
        <TroLyKhoaLayout />
      </ProtectedRoute>
    ),
    children: [
      { index: true, element: <Navigate to="len-danh-sach-hoc-phan" replace /> },
      { path: "len-danh-sach-hoc-phan", element: <LazyPage><LenDanhSachHocPhan /></LazyPage> },
      { path: "duyet-hoc-phan", element: <LazyPage><TlkDuyetHocPhan /></LazyPage> },
      { path: "tao-lop-hoc-phan", element: <LazyPage><TaoLopHocPhan /></LazyPage> },
    ],
  },

  // TK Routes
  {
    path: "/tk",
    element: (
      <ProtectedRoute allow={["truong_khoa"]}>
        <TruongKhoaLayout />
      </ProtectedRoute>
    ),
    children: [
      { index: true, element: <Navigate to="duyet-hoc-phan" replace /> },
      { path: "duyet-hoc-phan", element: <LazyPage><TkDuyetHocPhan /></LazyPage> },
    ],
  },

  // SV Routes
  {
    path: "/sv",
    element: <SVLayout />,
    children: [
      { index: true, element: <Navigate to="ghi-danh-hoc-phan" replace /> },
      { path: "ghi-danh-hoc-phan", element: <LazyPage><GhiDanhHocPhan /></LazyPage> },
      { path: "tra-cuu-mon-hoc", element: <LazyPage><TraCuuMonHoc /></LazyPage> },
      { path: "lich-su-dang-ky-hoc-phan", element: <LazyPage><LichSuDangKy /></LazyPage> },
      { path: "xem-thoi-khoa-bieu", element: <LazyPage><XemThoiKhoaBieu /></LazyPage> },
      { path: "dang-ky-hoc-phan", element: <LazyPage><DangKyHocPhan /></LazyPage> },
      { path: "thanh-toan-hoc-phi", element: <LazyPage><ThanhToanHocPhi /></LazyPage> },
      { path: "payment/demo", element: <LazyPage><DemoPaymentPage /></LazyPage> },
      { path: "tai-lieu", element: <LazyPage><TaiLieuHocTap /></LazyPage> },
      { path: "lop-hoc-phan", element: <LazyPage><SVLopHocPhanList /></LazyPage> },
      { path: "lop-hoc-phan/:id", element: <LazyPage><SVLopHocPhanDetail /></LazyPage> },
    ],
  },

  // Fallback
  { path: "*", element: <Navigate to="/" replace /> },
]);
