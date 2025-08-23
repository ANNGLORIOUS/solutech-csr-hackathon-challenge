import React, { createContext, useContext, useState } from 'react';

const AuthContext = createContext();

export function AuthProvider({ children }) {
  const [user, setUser] = useState(null); // { email, role }

  const login = (userData) => {
    setUser(userData);
    // Optionally store in localStorage/sessionStorage
  };

  const logout = () => {
    setUser(null);
    // Optionally clear from localStorage/sessionStorage
  };

  return (
    <AuthContext.Provider value={{ user, login, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  return useContext(AuthContext);
}
