import { useRoles } from '../../features/admin/hooks/useRoles';
import { useNavigate } from 'react-router-dom';
import './Dashboard.css';

export default function Dashboard() {
    const { roles, loading } = useRoles();
    const navigate = useNavigate();

    const totalRoles = roles.length;
    const systemRoles = roles.filter(r => r.isSystem).length;

    const handleViewRoles = () => {
        navigate('/admin/roles');
    };

    return (
        <section className="main__body">
            <div className="body__title">
                <p className="body__title-text">DASHBOARD - PHÒNG CNTT</p>
            </div>

            <div className="body__inner">
                {loading ? (
                    <div className="dashboard-loading">Đang tải...</div>
                ) : (
                    <>
                        {/* Stats Cards */}
                        <div className="stats-grid">
                            <div className="stat-card primary" onClick={handleViewRoles}>
                                <div className="stat-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                                        <circle cx="9" cy="7" r="4"/>
                                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                                        <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                                    </svg>
                                </div>
                                <div className="stat-content">
                                    <span className="stat-number">{totalRoles}</span>
                                    <span className="stat-label">Roles</span>
                                </div>
                                <div className="stat-action">Quản lý →</div>
                            </div>

                            <div className="stat-card success">
                                <div className="stat-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                    </svg>
                                </div>
                                <div className="stat-content">
                                    <span className="stat-number">{systemRoles}</span>
                                    <span className="stat-label">System Roles</span>
                                </div>
                                <div className="stat-sublabel">Không thể xóa</div>
                            </div>

                            <div className="stat-card info">
                                <div className="stat-icon">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2">
                                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"/>
                                        <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                    </svg>
                                </div>
                                <div className="stat-content">
                                    <span className="stat-number">RBAC</span>
                                    <span className="stat-label">Phân quyền động</span>
                                </div>
                                <div className="stat-sublabel">Database-driven</div>
                            </div>
                        </div>

                        {/* Roles Overview */}
                        <div className="dashboard-section">
                            <h3 className="section-title">Danh sách Roles</h3>
                            <div className="roles-table">
                                <div className="roles-table-header">
                                    <span>Role Code</span>
                                    <span>Tên</span>
                                    <span>Mô tả</span>
                                    <span>Loại</span>
                                </div>
                                {roles.map(role => (
                                    <div 
                                        key={role.id} 
                                        className="roles-table-row"
                                        onClick={() => navigate(`/admin/roles/${role.id}/permissions`)}
                                    >
                                        <span className="role-code">{role.code}</span>
                                        <span className="role-name">{role.name}</span>
                                        <span className="role-desc">{role.description || '-'}</span>
                                        <span className={`role-type ${role.isSystem ? 'system' : 'custom'}`}>
                                            {role.isSystem ? 'System' : 'Custom'}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    </>
                )}
            </div>
        </section>
    );
}
