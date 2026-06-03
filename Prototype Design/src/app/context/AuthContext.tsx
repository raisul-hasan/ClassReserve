import React, { createContext, useContext, useState } from 'react';

export type UserRole = 'student' | 'club' | 'faculty' | 'admin';

export interface User {
  name: string;
  email: string;
  role: UserRole;
}

interface AuthContextType {
  user: User | null;
  login: (email: string, password: string, role: UserRole) => void;
  signup: (name: string, email: string, password: string, role: UserRole) => void;
  logout: () => void;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(() => {
    const saved = localStorage.getItem('user');
    return saved ? JSON.parse(saved) : null;
  });

  const login = (email: string, _password: string, role: UserRole) => {
    const mockUser: User = {
      name:
        role === 'admin'
          ? 'System Admin'
          : role === 'faculty'
            ? 'Dr. Sarah Johnson'
            : role === 'club'
              ? 'Computing Club'
              : 'Michael Chen',
      email,
      role,
    };

    setUser(mockUser);
    localStorage.setItem('user', JSON.stringify(mockUser));
  };

  const signup = (name: string, email: string, _password: string, role: UserRole) => {
    const newUser: User = { name, email, role };
    setUser(newUser);
    localStorage.setItem('user', JSON.stringify(newUser));
  };

  const logout = () => {
    setUser(null);
    localStorage.removeItem('user');
  };

  return (
    <AuthContext.Provider value={{ user, login, signup, logout }}>
      {children}
    </AuthContext.Provider>
  );
}

export function useAuth() {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within AuthProvider');
  }
  return context;
}
