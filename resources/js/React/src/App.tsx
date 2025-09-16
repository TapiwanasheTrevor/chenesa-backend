import React from 'react';
import {BrowserRouter as Router, Routes, Route, Link, useLocation, Navigate, Outlet} from 'react-router-dom';
import {
    DropletIcon,
    Users as UsersIcon,
    TruckIcon,
    BellIcon,
    Settings as SettingsIcon,
    LayoutDashboard,
    FileText,
    LogOut,
} from 'lucide-react';
import {useAuth} from './contexts/AuthContext';
import {ProtectedRoute} from './components/ProtectedRoute';
import Dashboard from './pages/Dashboard';
import Users from './pages/Users';
import Deliveries from './pages/Deliveries';
import Notifications from './pages/Notifications';
import Reports from './pages/Reports';
import Settings from './pages/Settings';
import Login from './pages/Login';
import toast from 'react-hot-toast';

function Sidebar() {
    const location = useLocation();
    const {signOut} = useAuth();

    const menuItems = [
        {icon: LayoutDashboard, label: 'Dashboard', path: '/'},
        {icon: UsersIcon, label: 'Users', path: '/users'},
        {icon: TruckIcon, label: 'Deliveries', path: '/deliveries'},
        {icon: BellIcon, label: 'Notifications', path: '/notifications'},
        {icon: FileText, label: 'Reports', path: '/reports'},
        {icon: SettingsIcon, label: 'Settings', path: '/settings'},
    ];

    const handleSignOut = async () => {
        try {
            await signOut();
            toast.success('Signed out successfully');
        } catch (error) {
            toast.error('Failed to sign out');
            console.error('Sign out error:', error);
        }
    };

    return (
        <aside className="w-64 bg-white shadow-md h-screen flex flex-col">
            <div className="p-4">
                <h1 className="text-xl font-bold flex items-center gap-2">
                    <DropletIcon className="text-blue-500"/>
                    Chenesa
                </h1>
            </div>
            <nav className="mt-8 flex-1">
                {menuItems.map((item) => (
                    <Link
                        key={item.label}
                        to={item.path}
                        className={`flex items-center gap-3 px-4 py-3 text-gray-700 hover:bg-gray-50 transition-colors ${
                            location.pathname === item.path ? 'bg-blue-50 text-blue-600 border-r-4 border-blue-600' : ''
                        }`}
                    >
                        <item.icon size={20}/>
                        {item.label}
                    </Link>
                ))}
            </nav>
            <div className="p-4 border-t">
                <button
                    onClick={handleSignOut}
                    className="flex items-center gap-2 text-gray-700 hover:text-gray-900 w-full"
                >
                    <LogOut size={20}/>
                    Logout
                </button>
            </div>
        </aside>
    );
}

function Layout() {
    return (
        <div className="min-h-screen bg-gray-100 flex">
            <Sidebar/>
            <main className="flex-1 p-8 overflow-auto">
                <div className="max-w-7xl mx-auto">
                    <Outlet/> {/* This is the key change */}
                </div>
            </main>
        </div>
    );
}

function App() {
    return (
        <Router>
            <Routes>
                <Route path="/login" element={<Login/>}/>

                {/* Parent route with Layout */}
                <Route
                    path="/"
                    element={
                        <ProtectedRoute>
                            <Layout/>
                        </ProtectedRoute>
                    }
                >
                    {/* Child routes will render in the <Outlet /> */}
                    <Route index element={<Dashboard/>}/>
                    <Route path="users" element={<Users/>}/>
                    <Route path="deliveries" element={<Deliveries/>}/>
                    <Route path="notifications" element={<Notifications/>}/>
                    <Route path="reports" element={<Reports/>}/>
                    <Route path="settings" element={<Settings/>}/>
                </Route>

                <Route path="*" element={<Navigate to="/" replace/>}/>
            </Routes>
        </Router>
    );
}

export default App;
