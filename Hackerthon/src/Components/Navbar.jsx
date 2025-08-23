import React from 'react';
import { Link, useLocation } from 'react-router-dom';

const navLinks = {
  'van-rep': [
    { to: '/login', label: 'Logout' },
  ],
  distributor: [
    { to: '/login', label: 'Logout' },
  ],
  manager: [
    { to: '/login', label: 'Logout' },
  ],
};

export default function Navbar({ role }) {
  const location = useLocation();
  const links = navLinks[role] || [{ to: '/login', label: 'Login' }];
  return (
    <nav className="bg-white shadow flex items-center justify-between px-6 py-3 mb-6">
      <div className="font-bold text-xl text-blue-600">StockFlow</div>
      <div className="flex gap-4">
        {links.map((link) => (
          <Link
            key={link.to}
            to={link.to}
            className={`px-3 py-1 rounded hover:bg-blue-100 transition font-medium ${location.pathname === link.to ? 'bg-blue-600 text-white' : 'text-blue-600'}`}
          >
            {link.label}
          </Link>
        ))}
      </div>
    </nav>
  );
}