import { Alert, Linking, Platform } from 'react-native';
import * as Updates from 'expo-updates';

interface CheckForUpdatesOptions {
    silent?: boolean;
    showNoUpdateMessage?: boolean;
}

function isExpoGoEnvironment(): boolean {
    // expo-updates is unavailable in Expo Go; avoid noisy errors.
    return Updates.channel == null;
}

async function openStorePage(): Promise<void> {
    const androidStoreUrl = 'market://details?id=com.schoolerp';
    const androidFallbackUrl = 'https://play.google.com/store/apps/details?id=com.schoolerp';
    const iosStoreUrl = 'itms-apps://itunes.apple.com/app/id0000000000';
    const iosFallbackUrl = 'https://apps.apple.com/app/id0000000000';

    try {
        if (Platform.OS === 'android') {
            const canOpenMarket = await Linking.canOpenURL(androidStoreUrl);
            await Linking.openURL(canOpenMarket ? androidStoreUrl : androidFallbackUrl);
            return;
        }

        const canOpenIosStore = await Linking.canOpenURL(iosStoreUrl);
        await Linking.openURL(canOpenIosStore ? iosStoreUrl : iosFallbackUrl);
    } catch {
        // Keep this silent; caller already handles no-update / error messaging.
    }
}

export async function checkForAppUpdate(options: CheckForUpdatesOptions = {}): Promise<boolean> {
    const { silent = false, showNoUpdateMessage = false } = options;

    if (__DEV__ || isExpoGoEnvironment()) {
        if (showNoUpdateMessage) {
            Alert.alert('Updates', 'Updates are checked in production builds.');
        }
        return false;
    }

    try {
        const update = await Updates.checkForUpdateAsync();
        if (!update.isAvailable) {
            if (showNoUpdateMessage) {
                Alert.alert('Up to date', 'You are already on the latest version.');
            }
            return false;
        }

        if (silent) {
            return true;
        }

        Alert.alert(
            'Update available',
            'A new version is ready. Update now to get the latest fixes and improvements.',
            [
                { text: 'Later', style: 'cancel' },
                {
                    text: 'Update now',
                    onPress: async () => {
                        try {
                            await Updates.fetchUpdateAsync();
                            await Updates.reloadAsync();
                        } catch {
                            await openStorePage();
                        }
                    },
                },
            ]
        );
        return true;
    } catch {
        if (!silent && showNoUpdateMessage) {
            Alert.alert('Update check failed', 'Could not check for updates right now.');
        }
        return false;
    }
}
