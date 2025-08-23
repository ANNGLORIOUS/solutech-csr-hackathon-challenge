import React, { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useAuth } from '../context/AuthContext.jsx';

const USERS = [
  {
    email: 'alex@vansales.com',
    password: 'password123',
    role: 'van-rep',
  },
  {
    email: 'rayyidh@bestjuice.com',
    password: 'password123',
    role: 'distributor',
  },
  {
    email: 'manager@manufacturer.com',
    password: 'password123',
    role: 'manager',
  },
];

export default function Login() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);
  const navigate = useNavigate();
  const { login } = useAuth();

  const handleSubmit = (e) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    const user = USERS.find(
      (u) => u.email === email.trim() && u.password === password
    );
    setTimeout(() => {
      setLoading(false);
      if (user) {
        login(user); // set user in context
        navigate(`/${user.role}`);
      } else {
        setError('Invalid credentials. Please try again.');
      }
    }, 800);
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-100">
      <div className="bg-white p-8 rounded shadow-md w-full max-w-md">
        <h2 className="text-2xl font-bold mb-6 text-center">Login</h2>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="block mb-1 font-medium">Email</label>
            <input
              type="email"
              className="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300"
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              required
              autoFocus
            />
          </div>
          <div>
            <label className="block mb-1 font-medium">Password</label>
            <input
              type="password"
              className="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
            />
          </div>
          {error && (
            <div className="text-red-500 text-sm text-center">{error}</div>
          )}
          <button
            type="submit"
            className="w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 transition"
            disabled={loading}
          >
            {loading ? 'Logging in...' : 'Login'}
          </button>
        </form>
        <div className="mt-6 text-xs text-gray-500">
          <div>Van Rep: alex@vansales.com / password123</div>
          <div>Distributor: rayyidh@bestjuice.com / password123</div>
          <div>Manager: manager@manufacturer.com / password123</div>
        </div>
      </div>
    </div>
  );
}
