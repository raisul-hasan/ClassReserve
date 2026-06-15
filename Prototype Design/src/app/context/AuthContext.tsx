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
  signup: (name: string, email: string, password: string, role: UserRole) => Promise<User>;
  logout: () => Promise<void>;
  updateUser: (user: User) => void;
}

const AuthContext = createContext<AuthContextType | undefined>(undefined);
const CURRENT_USER_KEY = 'user';

function readSavedUser() {
  const saved = localStorage.getItem(CURRENT_USER_KEY);
  return saved ? JSON.parse(saved) as User : null;
}

function saveCurrentUser(user: User) {
  localStorage.setItem(CURRENT_USER_KEY, JSON.stringify(user));
}

function clearCurrentUser() {
  localStorage.removeItem(CURRENT_USER_KEY);
}

export function AuthProvider({ children }: { children: React.ReactNode }) {
  const [user, setUser] = useState<User | null>(() => {
    return readSavedUser();
  });
  const [isLoading, setIsLoading] = useState(true);

  useEffect(() => {
    getSession()
      .then((data) => {
        const savedUser = readSavedUser();
        if (savedUser) {
          setUser(savedUser);
          return;
        }

        if (data.user) {
          setUser(data.user);
          saveCurrentUser(data.user);
        } else {
          setUser(null);
          clearCurrentUser();
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

      if (nextUser.role !== role) {
        throw new Error(`This account is registered as ${nextUser.role}. Please choose the correct role.`);
      }

      setUser(nextUser);
      saveCurrentUser(nextUser);
      return nextUser;
    } catch (error) {
      const fallbackUser = readSavedUser();
      if (!fallbackUser) {
        throw error;
      }

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
    setUser(newUser);
    saveCurrentUser(newUser);
    return newUser;
  };

  const logout = async () => {
    setUser(null);
    clearCurrentUser();
    await logoutWithApi().catch(() => undefined);
  };

  const updateUser = (nextUser: User) => {
    setUser(nextUser);
    saveCurrentUser(nextUser);
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
