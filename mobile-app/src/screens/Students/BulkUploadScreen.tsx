import React, { useState } from 'react';
import {
    View,
    Text,
    StyleSheet,
    ScrollView,
    SafeAreaView,
    Alert,
    TouchableOpacity,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { Button } from '@components/common/Button';
import { Card } from '@components/common/Card';
import { studentsApi } from '@api/students.api';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface BulkUploadScreenProps {
    navigation: any;
}

export const BulkUploadScreen: React.FC<BulkUploadScreenProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const [loading, setLoading] = useState(false);
    const [file, setFile] = useState<any>(null);
    const [preview, setPreview] = useState<any[]>([]);

    const handleDownloadTemplate = () => {
        Alert.alert('Download Template', 'Excel template will be downloaded');
        // In real app, download CSV/Excel template
    };

    const handleSelectFile = () => {
        Alert.alert('Select File', 'File picker will open');
        // In real app, use document picker
        // Mock selection
        setFile({ name: 'students.xlsx', size: 45000 });
        setPreview([
            { admission_number: 'ADM001', first_name: 'John', last_name: 'Doe', class: 'Form 1A' },
            { admission_number: 'ADM002', first_name: 'Jane', last_name: 'Smith', class: 'Form 1A' },
        ]);
    };

    const handleUpload = async () => {
        if (!file) {
            Alert.alert('Error', 'Please select a file first');
            return;
        }

        setLoading(true);
        try {
            // const formData = new FormData();
            // formData.append('file', file);
            // await studentsApi.bulkUpload(formData);

            Alert.alert('Success', `${preview.length} students uploaded successfully`, [
                { text: 'OK', onPress: () => navigation.goBack() },
            ]);
        } catch (error: any) {
            Alert.alert('Error', error.message || 'Failed to upload students');
        } finally {
            setLoading(false);
        }
    };

    return (
        <SafeAreaView
            style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
        >
            <ScrollView style={styles.content}>
                <Card style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Step 1: Download Template
                    </Text>
                    <Text style={[styles.description, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        Download the Excel template and fill in student details
                    </Text>
                    <Button
                        title="Download Template"
                        onPress={handleDownloadTemplate}
                        icon="download"
                        variant="outline"
                        fullWidth
                    />
                </Card>

                <Card style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Step 2: Select File
                    </Text>
                    <Text style={[styles.description, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        Upload your completed Excel file
                    </Text>

                    {!file ? (
                        <TouchableOpacity
                            style={[styles.uploadBox, { borderColor: isDark ? colors.borderDark : colors.borderLight }]}
                            onPress={handleSelectFile}
                        >
                            <Icon name="cloud-upload" size={48} color={isDark ? colors.textSubDark : colors.textSubLight} />
                            <Text style={[styles.uploadText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                Tap to select file
                            </Text>
                        </TouchableOpacity>
                    ) : (
                        <View style={[styles.fileInfo, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight }]}>
                            <Icon name="insert-drive-file" size={24} color={colors.primary} />
                            <View style={styles.fileDetails}>
                                <Text style={[styles.fileName, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                    {file.name}
                                </Text>
                                <Text style={[styles.fileSize, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                    {(file.size / 1000).toFixed(2)} KB
                                </Text>
                            </View>
                            <TouchableOpacity onPress={() => setFile(null)}>
                                <Icon name="close" size={24} color={colors.error} />
                            </TouchableOpacity>
                        </View>
                    )}
                </Card>

                {preview.length > 0 && (
                    <Card style={styles.section}>
                        <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            Preview ({preview.length} students)
                        </Text>

                        {preview.map((student, index) => (
                            <View
                                key={index}
                                style={[
                                    styles.previewRow,
                                    { borderBottomColor: isDark ? colors.borderDark : colors.borderLight },
                                ]}
                            >
                                <Text style={[styles.previewText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                    {student.admission_number}
                                </Text>
                                <Text style={[styles.previewText, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                    {student.first_name} {student.last_name}
                                </Text>
                                <Text style={[styles.previewText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                    {student.class}
                                </Text>
                            </View>
                        ))}
                    </Card>
                )}

                <View style={styles.buttonContainer}>
                    <Button
                        title="Cancel"
                        onPress={() => navigation.goBack()}
                        variant="outline"
                        fullWidth
                        style={styles.button}
                    />
                    <Button
                        title={`Upload ${preview.length > 0 ? `(${preview.length})` : ''}`}
                        onPress={handleUpload}
                        loading={loading}
                        disabled={!file}
                        fullWidth
                        style={styles.button}
                    />
                </View>
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    content: { flex: 1, padding: SPACING.xl },
    section: { marginBottom: SPACING.lg },
    sectionTitle: { fontSize: FONT_SIZES.lg, fontWeight: 'bold', marginBottom: SPACING.sm },
    description: { fontSize: FONT_SIZES.sm, marginBottom: SPACING.md, lineHeight: 20 },
    uploadBox: {
        borderWidth: 2,
        borderStyle: 'dashed',
        borderRadius: 8,
        padding: SPACING.xxl,
        alignItems: 'center',
        gap: SPACING.sm,
    },
    uploadText: { fontSize: FONT_SIZES.sm },
    fileInfo: {
        flexDirection: 'row',
        alignItems: 'center',
        padding: SPACING.md,
        borderRadius: 8,
        gap: SPACING.sm,
    },
    fileDetails: { flex: 1 },
    fileName: { fontSize: FONT_SIZES.sm, fontWeight: '600' },
    fileSize: { fontSize: FONT_SIZES.xs, marginTop: 2 },
    previewRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        paddingVertical: SPACING.sm,
        borderBottomWidth: 1,
    },
    previewText: { fontSize: FONT_SIZES.xs, flex: 1 },
    buttonContainer: { flexDirection: 'row', gap: SPACING.md, marginTop: SPACING.lg, marginBottom: SPACING.xxl },
    button: { flex: 1 },
});
