import { BrowserRouter, Routes, Route } from 'react-router-dom';
import Login from './pages/Login.jsx';
import VanRepDashboard from './pages/VanRepDashboard.jsx';
import DistributorDashboard from './pages/DistributorDashboard.jsx';
import ManagerDashboard from './pages/ManagerDashboard.jsx';

// ...existing code...
// ...existing code...

export default function App() {
	return (
		<BrowserRouter>
			<Routes>
				<Route path="/" element={<Login />} />
				<Route path="/login" element={<Login />} />
				<Route path="/van-rep" element={<VanRepDashboard />} />
			<Route path="/distributor" element={<DistributorDashboard />} />
			<Route path="/manager" element={<ManagerDashboard />} />
			</Routes>
		</BrowserRouter>
	);
}
