import React, { useState, useEffect } from 'react';
import {
  View,
  Text,
  StyleSheet,
  ScrollView,
  TouchableOpacity,
  Image,
  ActivityIndicator,
  Alert,
} from 'react-native';
import * as ImagePicker from 'expo-image-picker';
import * as DocumentPicker from 'expo-document-picker';
import { API } from '../api/client';
import { colors } from '../theme/colors';

export default function DocumentScreen() {
  const [loading, setLoading] = useState(true);
  const [docData, setDocData] = useState(null);
  const [selectedFile, setSelectedFile] = useState(null);
  const [uploading, setUploading] = useState(false);

  useEffect(() => {
    loadDocumentStatus();
  }, []);

  const loadDocumentStatus = async () => {
    setLoading(true);
    try {
      const res = await API.getDocument();
      if (res && res.data && res.data.document) {
        setDocData(res.data.document);
      }
    } catch (e) {
      console.warn('Error obteniendo documento:', e);
    } finally {
      setLoading(false);
    }
  };

  const pickImage = async () => {
    const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (status !== 'granted') {
      Alert.alert('Permiso denegado', 'Se requiere acceso a la galería para seleccionar tu documento.');
      return;
    }

    const result = await ImagePicker.launchImageLibraryAsync({
      mediaTypes: ['images'],
      allowsEditing: true,
      quality: 0.8,
    });

    if (!result.canceled && result.assets && result.assets[0]) {
      const asset = result.assets[0];
      setSelectedFile({
        uri: asset.uri,
        name: asset.fileName || 'cedula.jpg',
        type: asset.mimeType || 'image/jpeg',
      });
    }
  };

  const pickDocument = async () => {
    try {
      const res = await DocumentPicker.getDocumentAsync({
        type: ['application/pdf', 'image/*'],
      });

      if (!res.canceled && res.assets && res.assets[0]) {
        const asset = res.assets[0];
        setSelectedFile({
          uri: asset.uri,
          name: asset.name,
          type: asset.mimeType || 'application/pdf',
        });
      }
    } catch (e) {
      console.warn('Error seleccionando documento:', e);
    }
  };

  const handleUpload = async () => {
    if (!selectedFile) return;

    setUploading(true);
    try {
      const formData = new FormData();
      formData.append('document', {
        uri: selectedFile.uri,
        name: selectedFile.name,
        type: selectedFile.type,
      });

      await API.uploadDocument(formData);
      Alert.alert('¡Éxito!', 'Tu documento ha sido cargado y está en proceso de revisión.');
      setSelectedFile(null);
      await loadDocumentStatus();
    } catch (err) {
      Alert.alert('Error', err.message || 'No se pudo subir el archivo.');
    } finally {
      setUploading(false);
    }
  };

  const status = docData?.status || 'pending';
  const isApproved = status === 'approved';
  const isRejected = status === 'rejected';

  if (loading) {
    return (
      <View style={styles.centerContainer}>
        <ActivityIndicator size="large" color={colors.primary} />
      </View>
    );
  }

  return (
    <ScrollView style={styles.container} contentContainerStyle={styles.content}>
      <Text style={styles.title}>Documento de Identidad</Text>
      <Text style={styles.subtitle}>
        Requisito oficial para validar tu inscripción y emitir tu certificación
      </Text>

      {/* Banner de Estado */}
      <View
        style={[
          styles.statusCard,
          {
            backgroundColor: isApproved
              ? colors.successSubtle
              : isRejected
              ? colors.dangerSubtle
              : colors.warningSubtle,
          },
        ]}
      >
        <Text style={styles.statusIcon}>{isApproved ? '✅' : isRejected ? '❌' : '⌛'}</Text>
        <View style={styles.statusInfo}>
          <Text
            style={[
              styles.statusTitle,
              {
                color: isApproved
                  ? colors.successDark
                  : isRejected
                  ? colors.danger
                  : '#B45309',
              },
            ]}
          >
            {isApproved
              ? 'Documento Aprobado'
              : isRejected
              ? 'Documento Rechazado'
              : 'En Revisión por Administración'}
          </Text>
          <Text style={styles.statusDesc}>
            {isApproved
              ? 'Tu identidad ha sido verificada satisfactoriamente.'
              : isRejected
              ? docData?.rejection_reason || 'Por favor sube una imagen más legible.'
              : 'Tu documento está en lista de verificación académica.'}
          </Text>
        </View>
      </View>

      {/* Zona de Carga */}
      <View style={styles.uploadCard}>
        <Text style={styles.cardHeading}>Adjuntar Cédula o Tarjeta de Identidad</Text>
        <Text style={styles.cardSub}>
          Formatos compatibles: JPG, PNG o PDF (máximo 5MB). Verifica que los números y nombres
          sean completamente legibles.
        </Text>

        <View style={styles.buttonsRow}>
          <TouchableOpacity style={styles.pickButton} onPress={pickImage} activeOpacity={0.8}>
            <Text style={styles.pickButtonIcon}>🖼️</Text>
            <Text style={styles.pickButtonText}>Galería / Fotos</Text>
          </TouchableOpacity>

          <TouchableOpacity style={styles.pickButton} onPress={pickDocument} activeOpacity={0.8}>
            <Text style={styles.pickButtonIcon}>📄</Text>
            <Text style={styles.pickButtonText}>Archivo PDF</Text>
          </TouchableOpacity>
        </View>

        {selectedFile && (
          <View style={styles.previewBox}>
            <Text style={styles.previewName}>📎 {selectedFile.name}</Text>
            {selectedFile.type?.startsWith('image') && (
              <Image source={{ uri: selectedFile.uri }} style={styles.thumbnail} />
            )}

            <TouchableOpacity
              style={styles.uploadActionButton}
              onPress={handleUpload}
              disabled={uploading}
            >
              {uploading ? (
                <ActivityIndicator color={colors.textWhite} />
              ) : (
                <Text style={styles.uploadActionText}>⬆️ Subir Documento a la Plataforma</Text>
              )}
            </TouchableOpacity>
          </View>
        )}
      </View>
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
  centerContainer: {
    flex: 1,
    justifyContent: 'center',
    alignItems: 'center',
    backgroundColor: colors.bgApp,
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
  statusCard: {
    borderRadius: 18,
    padding: 16,
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 16,
  },
  statusIcon: {
    fontSize: 26,
    marginRight: 14,
  },
  statusInfo: {
    flex: 1,
  },
  statusTitle: {
    fontSize: 14,
    fontWeight: '800',
    marginBottom: 2,
  },
  statusDesc: {
    fontSize: 11,
    color: colors.textSecondary,
    lineHeight: 16,
  },
  uploadCard: {
    backgroundColor: colors.bgCard,
    borderRadius: 22,
    padding: 20,
    borderWidth: 1,
    borderColor: colors.border,
  },
  cardHeading: {
    fontSize: 15,
    fontWeight: '800',
    color: colors.textPrimary,
    marginBottom: 4,
  },
  cardSub: {
    fontSize: 12,
    color: colors.textSecondary,
    lineHeight: 17,
    marginBottom: 16,
  },
  buttonsRow: {
    flexDirection: 'row',
    gap: 12,
  },
  pickButton: {
    flex: 1,
    backgroundColor: colors.bgMuted,
    borderRadius: 16,
    paddingVertical: 16,
    alignItems: 'center',
    borderWidth: 1.5,
    borderColor: colors.border,
  },
  pickButtonIcon: {
    fontSize: 24,
    marginBottom: 6,
  },
  pickButtonText: {
    fontSize: 12,
    fontWeight: '700',
    color: colors.textPrimary,
  },
  previewBox: {
    marginTop: 18,
    backgroundColor: colors.bgMuted,
    borderRadius: 16,
    padding: 14,
    alignItems: 'center',
  },
  previewName: {
    fontSize: 12,
    fontWeight: '700',
    color: colors.primary,
    marginBottom: 10,
  },
  thumbnail: {
    width: '100%',
    height: 180,
    borderRadius: 12,
    resizeMode: 'cover',
    marginBottom: 12,
  },
  uploadActionButton: {
    width: '100%',
    backgroundColor: colors.primary,
    borderRadius: 14,
    paddingVertical: 14,
    alignItems: 'center',
  },
  uploadActionText: {
    color: colors.textWhite,
    fontSize: 13,
    fontWeight: '700',
  },
});
