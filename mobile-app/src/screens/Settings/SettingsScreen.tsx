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
import { useAuth } from '@contexts/AuthContext';
import { Card } from '@components/common/Card';
import { Button } from '@components/common/Button';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { Palette } from '@styles/palette';
import { UserRole } from '@constants/roles';
import AsyncStorage from '@react-native-async-storage/async-storage';
import Constants from 'expo-constants';
import * as Updates from 'expo-updates';
import { checkForAppUpdate } from '@services/update.service';
import { canUseBiometrics, getBiometricEnabled, setBiometricEnabled } from '@utils/biometrics';

interface SettingsScreenProps {
    navigation: any;
}

export const SettingsScreen: React.FC<SettingsScreenProps> = ({ navigation }) => {
    const { isDark, colors, toggleTheme } = useTheme();
    const { user } = useAuth();
    const appVersion = Constants.expoConfig?.version ?? '1.0.0';
    const buildNumber = String(
        Constants.expoConfig?.android?.versionCode ?? Constants.expoConfig?.ios?.buildNumber ?? '100'
    );
    const isAdminUser = user?.role === UserRole.ADMIN || user?.role === UserRole.SUPER_ADMIN;
    const updateChannel = Updates.channel ?? 'N/A';
    const runtimeVersion = Updates.runtimeVersion ?? 'N/A';
    const updateId = Updates.updateId ?? 'N/A';

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
    const [deviceSupportsBiometrics, setDeviceSupportsBiometrics] = useState(false);

    React.useEffect(() => {
        (async () => {
            const [supported, enabled] = await Promise.all([canUseBiometrics(), getBiometricEnabled()]);
            setDeviceSupportsBiometrics(supported);
            setBiometrics((prev) => ({ ...prev, enabled: supported ? enabled : false }));
        })();
    }, []);

    const handleToggle = (category: string, setting: string) => {
        if (category === 'notifications') {
            setNotifications((prev) => ({ ...prev, [setting]: !prev[setting as keyof typeof prev] }));
        } else if (category === 'biometrics') {
            setBiometrics((prev) => {
                const nextValue = !prev[setting as keyof typeof prev];
                if (setting === 'enabled') {
                    setBiometricEnabled(nextValue).catch(() => undefined);
                }
                return { ...prev, [setting]: nextValue };
            });
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
                trackColor={{ false: Palette.switchTrackOff, true: colors.primary + '80' }}
                thumbColor={value ? colors.primary : Palette.switchThumbOff}
            />
        </View>
    );

    const handleCheckUpdates = async () => {
        await checkForAppUpdate({ silent: false, showNoUpdateMessage: true });
    };

    return (
        <SafeAreaView
            style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
        >
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
                        biometrics.enabled && deviceSupportsBiometrics,
                        () => handleToggle('biometrics', 'enabled'),
                        deviceSupportsBiometrics
                            ? 'Use fingerprint or face recognition'
                            : 'Biometric login not available on this device'
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
                        title="Check for Updates"
                        onPress={handleCheckUpdates}
                        variant="outline"
                        fullWidth
                        style={styles.actionButton}
                    />
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
                            {appVersion}
                        </Text>
                    </View>
                    <View style={styles.infoRow}>
                        <Text style={[styles.infoLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Build
                        </Text>
                        <Text style={[styles.infoValue, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            {buildNumber}
                        </Text>
                    </View>
                </Card>

                {isAdminUser ? (
                    <Card style={styles.section}>
                        <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            Update Diagnostics (Admin)
                        </Text>
                        <View style={styles.infoRow}>
                            <Text style={[styles.infoLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                Channel
                            </Text>
                            <Text style={[styles.infoValue, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                {updateChannel}
                            </Text>
                        </View>
                        <View style={styles.infoRow}>
                            <Text style={[styles.infoLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                Runtime Version
                            </Text>
                            <Text style={[styles.infoValue, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                {runtimeVersion}
                            </Text>
                        </View>
                        <View style={styles.infoRow}>
                            <Text style={[styles.infoLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                Update ID
                            </Text>
                            <Text style={[styles.infoValue, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                {updateId}
                            </Text>
                        </View>
                    </Card>
                ) : null}
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    content: { flex: 1, padding: SPACING.xl, paddingTop: SPACING.md },
    section: { marginBottom: SPACING.lg },
    sectionTitle: { fontSize: FONT_SIZES.lg, fontWeight: 'bold', marginBottom: SPACING.md },
    settingRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        paddingVertical: SPACING.sm,
        borderBottomWidth: 1,
        borderBottomColor: Palette.borderSlate,
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
        borderBottomColor: Palette.borderSlate,
    },
    infoLabel: { fontSize: FONT_SIZES.sm },
    infoValue: { fontSize: FONT_SIZES.sm, fontWeight: '600' },
});
