import { Outlet } from 'react-router-dom';
import '../../styles/reset.css';
import '../../styles/menu.css';

export default function AdminLayout() {
    return (
        <main className="main">
            <Outlet />
        </main>
    );
}
