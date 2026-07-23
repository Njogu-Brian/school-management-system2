import * as FileSystem from 'expo-file-system/legacy';
import * as Sharing from 'expo-sharing';
import { Alert } from 'react-native';
import { API_BASE_URL } from '../config/env';
import { getToken } from '../storage/secureStorage';

export async function downloadAuthenticatedFile(
  downloadPath: string,
  fileLabel: string,
): Promise<void> {
  const token = await getToken();
  if (!token) {
    throw new Error('You are not signed in.');
  }

  const url = `${API_BASE_URL}${downloadPath}`;
  const safeName = fileLabel.replace(/[^a-z0-9_-]/gi, '_');
  const ext = downloadPath.toLowerCase().includes('payslip') ? '.pdf' : '';
  const dest = `${FileSystem.cacheDirectory}${safeName}-${Date.now()}${ext}`;
  const result = await FileSystem.downloadAsync(url, dest, {
    headers: { Authorization: `Bearer ${token}` },
  });

  if (result.status !== 200) {
    throw new Error('Download failed.');
  }

  if (await Sharing.isAvailableAsync()) {
    await Sharing.shareAsync(result.uri);
  } else {
    Alert.alert('Downloaded', 'File saved to app cache.');
  }
}
