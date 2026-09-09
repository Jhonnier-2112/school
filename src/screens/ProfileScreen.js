import React from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Alert,
} from 'react-native';
import { useAuth } from '../context/AuthContext';
import { colors } from '../theme/colors';

export default function ProfileScreen() {
  const { user, logout } = useAuth();

  const handleLogout = () => {
    Alert.alert('Cerrar Sesión', '¿Estás seguro de que deseas salir de tu cuenta?', [
      { text: 'Cancelar', style: 'cancel' },
      { text: 'Cerrar Sesión', style: 'destructive', onPress: logout },
    ]);
  };

  const name = user?.full_name || 'Estudiante';
  const initial = name.charAt(0).toUpperCase();

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>
      <Text style={styles.title}>Mi Perfil</Text>
      <Text style={styles.subtitle}>Información de tu cuenta y estado de la plataforma</Text>

      {/* Tarjeta de Usuario */}
      <View style={styles.userCard}>
        <View style={styles.avatarCircle}>
          <Text style={styles.avatarInitial}>{initial}</Text>
        </View>
        <Text style={styles.userName}>{name}</Text>
        <Text style={styles.userEmail}>{user?.email || 'estudiante@icfes.edu.co'}</Text>
        <View style={styles.roleBadge}>
          <Text style={styles.roleText}>ESTUDIANTE ACTIVO</Text>
        </View>
      </View>

      {/* Información de la App */}
      <View style={styles.infoCard}>
        <Text style={styles.infoTitle}>Información de la Plataforma</Text>

        <View style={styles.infoRow}>
          <Text style={styles.infoLabel}>Versión de la App</Text>
          <Text style={styles.infoVal}>1.0.0 (Build Nativo)</Text>
        </View>

        <View style={styles.infoRow}>
          <Text style={styles.infoLabel}>Destino de Publicación</Text>
          <Text style={styles.infoVal}>Google Play & App Store</Text>
        </View>

        <View style={styles.infoRow}>
          <Text style={styles.infoLabel}>Backend API</Text>
          <Text style={[styles.infoVal, { color: colors.success }]}>Online (Hostinger)</Text>
        </View>
      </View>

      {/* Botón de Logout */}
      <TouchableOpacity style={styles.logoutButton} onPress={handleLogout} activeOpacity={0.85}>
        <Text style={styles.logoutText}>🚪 Cerrar Sesión</Text>
      </TouchableOpacity>
    </ScrollView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
    backgroundColor: colors.bgApp,
  },
  content: {
    padding: 18,
    paddingBottom: 36,
  },
  title: {
    fontSize: 20,
    fontWeight: '800',
    color: colors.textPrimary,
  },
  subtitle: {
    fontSize: 12,
    color: colors.textSecondary,
    marginTop: 4,
    marginBottom: 16,
  },
  userCard: {
    backgroundColor: colors.bgCard,
    borderRadius: 24,
    padding: 24,
    alignItems: 'center',
    borderWidth: 1,
    borderColor: colors.border,
    marginBottom: 16,
  },
  avatarCircle: {
    width: 72,
    height: 72,
    borderRadius: 36,
    backgroundColor: colors.primary,
    alignItems: 'center',
    justifyContent: 'center',
    marginBottom: 12,
  },
  avatarInitial: {
    fontSize: 28,
    fontWeight: '800',
    color: colors.textWhite,
  },
  userName: {
    fontSize: 18,
    fontWeight: '800',
    color: colors.textPrimary,
    marginBottom: 4,
  },
  userEmail: {
    fontSize: 13,
    color: colors.textSecondary,
    marginBottom: 12,
  },
  roleBadge: {
    backgroundColor: colors.successSubtle,
    paddingHorizontal: 12,
    paddingVertical: 4,
    borderRadius: 12,
    borderWidth: 1,
    borderColor: '#A7F3D0',
  },
  roleText: {
    color: colors.successDark,
    fontSize: 10,
    fontWeight: '800',
  },
  infoCard: {
    backgroundColor: colors.bgCard,
    borderRadius: 20,
    padding: 18,
    borderWidth: 1,
    borderColor: colors.border,
    marginBottom: 20,
  },
  infoTitle: {
    fontSize: 14,
    fontWeight: '800',
    color: colors.textPrimary,
    marginBottom: 12,
  },
  infoRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    paddingVertical: 10,
    borderBottomWidth: 1,
    borderBottomColor: colors.borderLight,
  },
  infoLabel: {
    fontSize: 12,
    color: colors.textSecondary,
    fontWeight: '600',
  },
  infoVal: {
    fontSize: 12,
    color: colors.textPrimary,
    fontWeight: '700',
  },
  logoutButton: {
    backgroundColor: colors.dangerSubtle,
    borderWidth: 1.5,
    borderColor: '#FECACA',
    borderRadius: 16,
    paddingVertical: 14,
    alignItems: 'center',
  },
  logoutText: {
    color: colors.danger,
    fontSize: 14,
    fontWeight: '800',
  },
});
