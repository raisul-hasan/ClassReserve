import React, { createContext, useContext, useEffect, useState } from 'react';
import { getSession, loginWithApi, logoutWithApi, signupWithApi } from '../services/classReserveService';

export type UserRole = 'student' | 'club' | 'faculty' | 'admin';

export interface User {
  id?: number | string;
  name: string;
  email: string;
  role: UserRole;
}

interface AuthContextType {
  user: User | null;
  isLoading: boolean;
  login: (email: string, password: string, role: UserRole) => Promise<User>;
  signup: (name: string, email: string, password: string, role: UserRole) => Promise<void>;
  logout: () => Promise<void>;
  updateUser: (user: User) => void;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(() => {
    const saved = localStorage.getItem('user');
    return saved ? JSON.parse(saved) : null;
  });
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    getSession()
      .then((data) => {
        if (data.user) {
          setUser(data.user);
          localStorage.setItem('user', JSON.stringify(data.user));
        } else {
          setUser(null);
          localStorage.removeItem('user');
        }
      })
      .catch(() => {
        // Keep local mock user if the PHP API is not running.
      })
      .finally(() => setIsLoading(false));
  }, []);

  const login = async (email: string, password: string, role: UserRole) => {
    try {
      const apiUser = await loginWithApi(email, password);
      const nextUser: User = {
        id: apiUser.id,
        name: apiUser.name,
        email: apiUser.email || email,
        role: apiUser.role,
      };
      setUser(nextUser);
      localStorage.setItem('user', JSON.stringify(nextUser));
      return nextUser;
    } catch (error) {
      const saved = localStorage.getItem('user');
      if (!saved) {
        throw error;
      }

      const fallbackUser: User = JSON.parse(saved);
      if (fallbackUser.email !== email || fallbackUser.role !== role) {
        throw error;
      }

      setUser(fallbackUser);
      return fallbackUser;
    }
  };

  const signup = async (name: string, email: string, password: string, role: UserRole) => {
    if (role === 'admin') {
      throw new Error('Admin accounts are managed by the system.');
    }

    await signupWithApi(name, email, password, role);
    const newUser: User = { id: `user-${Date.now()}`, name, email, role };
    localStorage.setItem('user', JSON.stringify(newUser));
  };

  const logout = async () => {
    setUser(null);
    localStorage.removeItem('user');
    await logoutWithApi().catch(() => undefined);
  };

  const updateUser = (nextUser: User) => {
    setUser(nextUser);
    localStorage.setItem('user', JSON.stringify(nextUser));
  };

  return (
    <AuthContext.Provider value={{ user, isLoading, login, signup, logout, updateUser }}>
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
