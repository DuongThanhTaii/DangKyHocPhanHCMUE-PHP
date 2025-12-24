import { useNavigate } from 'react-router-dom';
import { useRoles } from '../../features/admin/hooks/useRoles';
import './RoleList.css';

export default function RoleList() {
    const { roles, loading, error } = useRoles();
    const navigate = useNavigate();

    const handleRoleClick = (roleId: string) => {
        navigate(`/admin/roles/${roleId}/permissions`);
    };

    return (
        <section className="main__body">
            <div className="body__title">
                <p className="body__title-text">QUẢN LÝ PHÂN QUYỀN</p>
            </div>

            <div className="body__inner">
                {loading && (
                    <div className="loading-container">
                        <div className="loading-spinner"></div>
                        <p>Đang tải danh sách roles...</p>
                    </div>
                )}

                {error && (
                    <div className="error-container">
                        <p>{error}</p>
                    </div>
                )}

                {!loading && !error && (
                    <div className="role-grid">
                        {roles.map(role => (
                            <div 
                                key={role.id} 
                                className={`role-card ${role.isSystem ? 'system' : ''}`}
                                onClick={() => handleRoleClick(role.id)}
                            >
                                <div className="role-card-header">
                                    <span className="role-code">{role.code}</span>
                                    {role.isSystem && <span className="system-badge">System</span>}
                                </div>
                                <h3 className="role-name">{role.name}</h3>
                                {role.description && (
                                    <p className="role-description">{role.description}</p>
                                )}
                                <div className="role-action">
                                    Xem permissions →
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </section>
    );
}
