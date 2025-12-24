import { useParams, useNavigate } from 'react-router-dom';
import { useRolePermissions } from '../../features/admin/hooks/useRolePermissions';
import './RolePermissions.css';

export default function RolePermissions() {
    const { roleId } = useParams<{ roleId: string }>();
    const navigate = useNavigate();
    const { data, loading, error, togglePermission } = useRolePermissions(roleId || null);

    const handleToggle = async (permissionId: string, currentState: boolean) => {
        await togglePermission(permissionId, !currentState);
    };

    const handleBack = () => {
        navigate('/admin/roles');
    };

    return (
        <section className="main__body">
            <div className="body__title">
                <p className="body__title-text">
                    {data?.role ? `PERMISSIONS: ${data.role.name}` : 'PERMISSIONS'}
                </p>
            </div>

            <div className="body__inner">
                <div className="permissions-header">
                    <button className="back-btn" onClick={handleBack}>
                        ← Quay lại danh sách Roles
                    </button>
                    
                    {data && (
                        <div className="permission-stats">
                            <span className="stat enabled">{data.enabledCount} enabled</span>
                            <span className="stat total">/ {data.totalPermissions} total</span>
                        </div>
                    )}
                </div>

                {loading && (
                    <div className="loading-container">
                        <div className="loading-spinner"></div>
                        <p>Đang tải permissions...</p>
                    </div>
                )}

                {error && (
                    <div className="error-container">
                        <p>{error}</p>
                    </div>
                )}

                {data?.role?.code === 'admin_system' && (
                    <div className="admin-warning">
                        Admin System có full quyền và không thể chỉnh sửa
                    </div>
                )}

                {!loading && !error && data && (
                    <div className="permission-groups">
                        {data.permissionGroups.map(group => (
                            <div key={group.module} className="permission-group">
                                <h3 className="group-title">{group.module}</h3>
                                <div className="permission-table">
                                    <div className="permission-table-header">
                                        <span className="col-method">Method</span>
                                        <span className="col-path">API Path</span>
                                        <span className="col-status">Status</span>
                                    </div>
                                    {group.permissions.map(perm => (
                                        <div key={perm.id} className="permission-row">
                                            <span className={`col-method method-${perm.method.toLowerCase()}`}>
                                                {perm.method}
                                            </span>
                                            <span className="col-path">
                                                {perm.routePath}
                                                {perm.description && (
                                                    <small className="path-desc">{perm.description}</small>
                                                )}
                                            </span>
                                            <span className="col-status">
                                                <button
                                                    className={`toggle-btn ${perm.isEnabled ? 'on' : 'off'}`}
                                                    onClick={() => handleToggle(perm.id, perm.isEnabled)}
                                                    disabled={data.role?.code === 'admin_system'}
                                                >
                                                    {perm.isEnabled ? 'ON' : 'OFF'}
                                                </button>
                                            </span>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </section>
    );
}
