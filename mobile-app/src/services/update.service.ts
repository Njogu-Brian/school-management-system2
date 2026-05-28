import { Alert, Linking, Platform } from 'react-native';
import * as Updates from 'expo-updates';
import Constants from 'expo-constants';
import { brandingApi } from '@api/branding.api';

interface CheckForUpdatesOptions {
    silent?: boolean;
    showNoUpdateMessage?: boolean;
}

function isExpoGoEnvironment(): boolean {
    return Constants.appOwnership === 'expo';
}

async function openApkDownloadUrl(url: string): Promise<void> {
    const canOpen = await Linking.canOpenURL(url);
    if (!canOpen) {
        throw new Error('Could not open the download link.');
    }
    await Linking.openURL(url);
}

async function promptApkDownload(reason: string): Promise<boolean> {
    try {
        const branding = await brandingApi.getBranding();
        const apkUrl = branding?.android_apk_download_url?.trim();
        if (!apkUrl) {
            Alert.alert(
                'Install update manually',
                `${reason}\n\nAsk your administrator to set MOBILE_APP_DOWNLOAD_URL on the server, or distribute a new APK.`,
            );
            return false;
        }

        return new Promise((resolve) => {
            Alert.alert(
                'New app version required',
                `${reason}\n\nDownload the latest APK from your school and install it (you may need to allow installs from this browser).`,
                [
                    { text: 'Cancel', style: 'cancel', onPress: () => resolve(false) },
                    {
                        text: 'Download APK',
                        onPress: async () => {
                            try {
                                await openApkDownloadUrl(apkUrl);
                                resolve(true);
                            } catch (e: any) {
                                Alert.alert('Download failed', e?.message || 'Could not open download link.');
                                resolve(false);
                            }
                        },
                    },
                ],
            );
        });
    } catch {
        Alert.alert('Update unavailable', reason);
        return false;
    }
}

export async function checkForAppUpdate(options: CheckForUpdatesOptions = {}): Promise<boolean> {
    const { silent = false, showNoUpdateMessage = false } = options;

    if (__DEV__ || isExpoGoEnvironment()) {
        if (showNoUpdateMessage) {
            Alert.alert('Updates', 'Updates are checked in production builds installed from a release APK.');
        }
        return false;
    }

    if (!Updates.isEnabled) {
        if (showNoUpdateMessage) {
            await promptApkDownload(
                'This build does not support in-app (OTA) updates. Use a release APK built with EAS, or download the latest APK.',
            );
        }
        return false;
    }

    try {
        const update = await Updates.checkForUpdateAsync();
        if (!update.isAvailable) {
            if (showNoUpdateMessage) {
                Alert.alert('Up to date', 'You already have the latest app update.');
            }
            return false;
        }

        if (silent) {
            return true;
        }

        Alert.alert(
            'Update available',
            'A new version is ready. Tap Update now to download and apply it (no new APK install needed).',
            [
                { text: 'Later', style: 'cancel' },
                {
                    text: 'Update now',
                    onPress: async () => {
                        try {
                            await Updates.fetchUpdateAsync();
                            await Updates.reloadAsync();
                        } catch (err: any) {
                            const msg = err?.message || 'Could not apply the update.';
                            if (Platform.OS === 'android') {
                                await promptApkDownload(`${msg} You can install the latest APK instead.`);
                            } else {
                                Alert.alert('Update failed', msg);
                            }
                        }
                    },
                },
            ],
        );
        return true;
    } catch (err: any) {
        if (!silent && showNoUpdateMessage) {
            const msg = err?.message || 'Could not check for updates right now.';
            if (Platform.OS === 'android') {
                await promptApkDownload(msg);
            } else {
                Alert.alert('Update check failed', msg);
            }
        }
        return false;
    }
}
