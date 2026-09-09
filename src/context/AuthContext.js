import React, { createContext, useState, useEffect, useContext } from 'react';
import AsyncStorage from '@react-native-async-storage/async-storage';
import { API, TOKEN_KEY, USER_KEY } from '../api/client';

const AuthContext = createContext();

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [token, setToken] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadStoredSession();
  }, []);

  const loadStoredSession = async () => {
    try {
      const storedToken = await AsyncStorage.getItem(TOKEN_KEY);
      const storedUser  = await AsyncStorage.getItem(USER_KEY);

      if (storedToken && storedUser) {
        setToken(storedToken);
        setUser(JSON.parse(storedUser));
      }
    } catch (e) {
      console.warn('Error cargando sesión almacenada:', e);
    } finally {
      setLoading(false);
    }
  };

  const login = async (email, password) => {
    const res = await API.login(email, password);
    if (res && res.data && res.data.token) {
      await AsyncStorage.setItem(TOKEN_KEY, res.data.token);
      await AsyncStorage.setItem(USER_KEY, JSON.stringify(res.data.user));
      setToken(res.data.token);
      setUser(res.data.user);
      return res;
    }
    throw new Error(res.message || 'Error en inicio de sesión');
  };

  const register = async (data) => {
    const res = await API.register(data);
    if (res && res.data && res.data.token) {
      await AsyncStorage.setItem(TOKEN_KEY, res.data.token);
      await AsyncStorage.setItem(USER_KEY, JSON.stringify(res.data.user));
      setToken(res.data.token);
      setUser(res.data.user);
      return res;
    }
    return res;
  };

  const quickDemoLogin = async () => {
    const demoUser = {
      id: 'demo-student-id',
      full_name: 'Santiago Mendoza García',
      email: 'santiago.estudiante@icfes.edu.co',
      role: 'student',
    };
    const demoToken = 'demo-valid-student-jwt-token';

    await AsyncStorage.setItem(TOKEN_KEY, demoToken);
    await AsyncStorage.setItem(USER_KEY, JSON.stringify(demoUser));
    setToken(demoToken);
    setUser(demoUser);
  };

  const logout = async () => {
    await AsyncStorage.removeItem(TOKEN_KEY);
    await AsyncStorage.removeItem(USER_KEY);
    setToken(null);
    setUser(null);
  };

  return (
    <AuthContext.Provider value={{ user, token, loading, login, register, logout, quickDemoLogin }}>
      {children}
    </AuthContext.Provider>
  );
};

export const useAuth = () => useContext(AuthContext);
