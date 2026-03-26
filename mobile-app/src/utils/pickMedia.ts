import { Alert } from 'react-native';
import * as ImagePicker from 'expo-image-picker';

export type PickedImageFile = { uri: string; name: string; type: string };

/** Requests photo library access, then opens the image picker. Returns null if denied or cancelled. */
export async function pickImageFromLibrary(options?: {
    quality?: number;
    permissionDeniedMessage?: string;
}): Promise<PickedImageFile | null> {
    const { status } = await ImagePicker.requestMediaLibraryPermissionsAsync();
    if (status !== ImagePicker.PermissionStatus.GRANTED) {
        Alert.alert(
            'Photo access',
            options?.permissionDeniedMessage ??
                'Allow photo library access to choose an image for upload.'
        );
        return null;
    }
    const result = await ImagePicker.launchImageLibraryAsync({
        mediaTypes: ImagePicker.MediaTypeOptions.Images,
        allowsEditing: false,
        quality: options?.quality ?? 0.85,
    });
    if (result.canceled || !result.assets?.[0]) return null;
    const a = result.assets[0];
    return {
        uri: a.uri,
        name: a.fileName ?? 'photo.jpg',
        type: a.mimeType ?? 'image/jpeg',
    };
}
