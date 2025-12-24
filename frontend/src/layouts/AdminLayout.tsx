import { AuthGuard } from "../components/AuthGuard";
import BaseLayout from "./BaseLayout";
import type { LayoutConfig } from "./types";

const adminConfig: LayoutConfig = {
  role: "admin_system",
  headerTitle: "PHÒNG CÔNG NGHỆ THÔNG TIN",
  menuItems: [
    {
      to: "dashboard",
      label: "Dashboard",
      icon: (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="20" height="20">
          <path
            fill="currentColor"
            d="M64 64c0-17.7-14.3-32-32-32S0 46.3 0 64L0 400c0 44.2 35.8 80 80 80l400 0c17.7 0 32-14.3 32-32s-14.3-32-32-32L80 416c-8.8 0-16-7.2-16-16L64 64zm406.6 86.6c12.5-12.5 12.5-32.8 0-45.3s-32.8-12.5-45.3 0L320 210.7l-57.4-57.4c-12.5-12.5-32.8-12.5-45.3 0l-112 112c-12.5 12.5-12.5 32.8 0 45.3s32.8 12.5 45.3 0L240 221.3l57.4 57.4c12.5 12.5 32.8 12.5 45.3 0l128-128z"
          />
        </svg>
      ),
    },
    {
      to: "roles",
      label: "Quản lý phân quyền",
      icon: (
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512" width="20" height="20">
          <path
            fill="currentColor"
            d="M256 64L128 160L0 160L0 320l128 0 128 96 0-352zm48 56l0 208c44.2-32.3 72-84.4 72-144s-27.8-111.7-72-144zm80 104c0 79.5-37.8 150-96 195.3l0 52.7c86.5-45.8 144-135.7 144-248s-57.5-202.2-144-248l0 52.7c58.2 45.3 96 115.8 96 195.3z"
          />
        </svg>
      ),
    },
  ],
};

export default function AdminLayout() {
  return (
    <AuthGuard requiredRole="admin_system">
      <BaseLayout config={adminConfig} />
    </AuthGuard>
  );
}
