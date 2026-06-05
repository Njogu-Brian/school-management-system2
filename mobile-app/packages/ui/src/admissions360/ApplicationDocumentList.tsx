import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { Image, Linking, Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';

export interface ApplicationDocumentItemData {
  field: string;
  label: string;
  uploaded: boolean;
  viewUrl: string | null;
  isPrivate: boolean;
  downloadPath?: string | null;
}

export interface ApplicationDocumentListProps {
  documents: ApplicationDocumentItemData[];
  onDownloadPrivate?: (doc: ApplicationDocumentItemData) => void | Promise<void>;
  downloadingField?: string | null;
}

export const ApplicationDocumentList: React.FC<ApplicationDocumentListProps> = ({
  documents,
  onDownloadPrivate,
  downloadingField,
}) => {
  const { palette, colors, spacing, fontSizes, radius } = useTheme();

  return (
    <View style={{ paddingBottom: spacing.xl }}>
      {documents.map((doc) => (
        <View
          key={doc.field}
          style={[
            styles.card,
            {
              backgroundColor: palette.surface,
              borderColor: palette.border,
              borderRadius: radius.lg,
              padding: spacing.md,
              marginBottom: spacing.sm,
            },
          ]}
        >
          <View style={styles.row}>
            <Ionicons
              name={doc.uploaded ? 'document-attach-outline' : 'document-outline'}
              size={22}
              color={doc.uploaded ? colors.primary : palette.textSecondary}
            />
            <View style={{ flex: 1, marginLeft: spacing.sm }}>
              <Text style={{ color: palette.textPrimary, fontSize: fontSizes.md, fontWeight: '600' }}>
                {doc.label}
              </Text>
              <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 2 }}>
                {doc.uploaded
                  ? doc.isPrivate
                    ? 'Uploaded (private document)'
                    : 'Uploaded'
                  : 'Not uploaded'}
              </Text>
            </View>
            {doc.uploaded ? (
              <Ionicons name="checkmark-circle" size={20} color={colors.success} />
            ) : (
              <Ionicons name="close-circle-outline" size={20} color={palette.textSecondary} />
            )}
          </View>
          {doc.viewUrl ? (
            <Pressable onPress={() => void Linking.openURL(doc.viewUrl as string)} style={{ marginTop: spacing.sm }}>
              <Image source={{ uri: doc.viewUrl }} style={styles.preview} resizeMode="cover" />
            </Pressable>
          ) : null}
          {doc.uploaded && doc.isPrivate && doc.downloadPath && onDownloadPrivate ? (
            <Pressable
              onPress={() => void onDownloadPrivate(doc)}
              disabled={downloadingField === doc.field}
              style={{ marginTop: spacing.sm }}
            >
              <Text style={{ color: colors.primary, fontSize: fontSizes.sm, fontWeight: '600' }}>
                {downloadingField === doc.field ? 'Downloading…' : 'Download document'}
              </Text>
            </Pressable>
          ) : null}
        </View>
      ))}
    </View>
  );
};

const styles = StyleSheet.create({
  card: { borderWidth: StyleSheet.hairlineWidth },
  row: { flexDirection: 'row', alignItems: 'center' },
  preview: { width: '100%', height: 160, borderRadius: 8 },
});
