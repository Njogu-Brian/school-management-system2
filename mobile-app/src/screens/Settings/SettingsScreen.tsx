import React, { useState } from 'react';
import {
    View,
    Text,
    StyleSheet,
    ScrollView,
    SafeAreaView,
    Switch,
    Alert,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { Card } from '@components/common/Card';
import { Button } from '@components/common/Button';
import { SPACING, FONT_SIZES } from '@constants/theme';
import AsyncStorage from '@react-native-async-storage/async-storage';

interface SettingsScreenProps {
    navigation: any;
}

export const SettingsScreen: React.FC<SettingsScreenProps> = ({ navigation }) => {
    const { isDark, colors, toggleTheme } = useTheme();

    const [notifications, setNotifications] = useState({
        pushEnabled: true,
        emailEnabled: true,
        smsEnabled: false,
        attendanceAlerts: true,
        feeReminders: true,
        announcements: true,
    });

    const [biometrics, setBiometrics] = useState({
        enabled: false,
        touchId: false,
        faceId: false,
    });

    const handleToggle = (category: string, setting: string) => {
        if (category === 'notifications') {
            setNotifications((prev) => ({ ...prev, [setting]: !prev[setting as keyof typeof prev] }));
        } else if (category === 'biometrics') {
            setBiometrics((prev) => ({ ...prev, [setting]: !prev[setting as keyof typeof prev] }));
        }
    };

    const handleClearCache = async () => {
        Alert.alert(
            'Clear Cache',
            'This will clear all cached data. Are you sure?',
            [
                { text: 'Cancel', style: 'cancel' },
                {
                    text: 'Clear',
                    style: 'destructive',
                    onPress: async () => {
                        try {
                            // Clear specific cache items, not auth token
                            await AsyncStorage.multiRemove(['@cached_students', '@cached_staff', '@cached_classes']);
                            Alert.alert('Success', 'Cache cleared successfully');
                        } catch (error) {
                            Alert.alert('Error', 'Failed to clear cache');
                        }
                    },
                },
            ]
        );
    };

    const renderSettingRow = (title: string, value: boolean, onToggle: () => void, description?: string) => (
        <View style={styles.settingRow}>
            <View style={styles.settingInfo}>
                <Text style={[styles.settingTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    {title}
                </Text>
                {description && (
                    <Text style={[styles.settingDescription, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        {description}
                    </Text>
                )}
            </View>
            <Switch
                value={value}
                onValueChange={onToggle}
                trackColor={{ false: '#767577', true: colors.primary + '80' }}
                thumbColor={value ? colors.primary : '#f4f3f4'}
            />
        </View>
    );

    return (
        <SafeAreaView
            style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
        >
            <View style={styles.header}>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    Settings
                </Text>
            </View>

            <ScrollView style={styles.content}>
                {/* Appearance */}
                <Card style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Appearance
                    </Text>
                    {renderSettingRow('Dark Mode', isDark, toggleTheme, 'Use dark theme throughout the app')}
                </Card>

                {/* Notifications */}
                <Card style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Notifications
                    </Text>
                    {renderSettingRow(
                        'Push Notifications',
                        notifications.pushEnabled,
                        () => handleToggle('notifications', 'pushEnabled'),
                        'Receive push notifications'
                    )}
                    {renderSettingRow(
                        'Email Notifications',
                        notifications.emailEnabled,
                        () => handleToggle('notifications', 'emailEnabled')
                    )}
                    {renderSettingRow(
                        'Attendance Alerts',
                        notifications.attendanceAlerts,
                        () => handleToggle('notifications', 'attendanceAlerts')
                    )}
                    {renderSettingRow(
                        'Fee Reminders',
                        notifications.feeReminders,
                        () => handleToggle('notifications', 'feeReminders')
                    )}
                    {renderSettingRow(
                        'Announcements',
                        notifications.announcements,
                        () => handleToggle('notifications', 'announcements')
                    )}
                </Card>

                {/* Security */}
                <Card style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Security
                    </Text>
                    {renderSettingRow(
                        'Biometric Login',
                        biometrics.enabled,
                        () => handleToggle('biometrics', 'enabled'),
                        'Use fingerprint or face recognition'
                    )}
                    <Button
                        title="Change Password"
                        onPress={() => navigation.navigate('ChangePassword')}
                        variant="outline"
                        fullWidth
                        style={styles.actionButton}
                    />
                </Card>

                {/* Data & Storage */}
                <Card style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        Data & Storage
                    </Text>
                    <Button
                        title="Clear Cache"
                        onPress={handleClearCache}
                        variant="outline"
                        fullWidth
                        style={styles.actionButton}
                    />
                </Card>

                {/* About */}
                <Card style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        About
                    </Text>
                    <View style={styles.infoRow}>
                        <Text style={[styles.infoLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Version
                        </Text>
                        <Text style={[styles.infoValue, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            1.0.0
                        </Text>
                    </View>
                    <View style={styles.infoRow}>
                        <Text style={[styles.infoLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Build
                        </Text>
                        <Text style={[styles.infoValue, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            100
                        </Text>
                    </View>
                </Card>
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    header: { paddingHorizontal: SPACING.xl, paddingVertical: SPACING.md },
    title: { fontSize: FONT_SIZES.xxl, fontWeight: 'bold' },
    content: { flex: 1, padding: SPACING.xl },
    section: { marginBottom: SPACING.lg },
    sectionTitle: { fontSize: FONT_SIZES.lg, fontWeight: 'bold', marginBottom: SPACING.md },
    settingRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        paddingVertical: SPACING.sm,
        borderBottomWidth: 1,
        borderBottomColor: '#e2e8f0',
    },
    settingInfo: { flex: 1, marginRight: SPACING.md },
    settingTitle: { fontSize: FONT_SIZES.sm, fontWeight: '600' },
    settingDescription: { fontSize: FONT_SIZES.xs, marginTop: 2 },
    actionButton: { marginTop: SPACING.sm },
    infoRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        paddingVertical: SPACING.sm,
        borderBottomWidth: 1,
        borderBottomColor: '#e2e8f0',
    },
    infoLabel: { fontSize: FONT_SIZES.sm },
    infoValue: { fontSize: FONT_SIZES.sm, fontWeight: '600' },
});
