import MapView, { Marker, PROVIDER_GOOGLE, type Region } from 'react-native-maps';
import React, { useMemo } from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme';

export type MapPin = {
  id: string;
  latitude: number;
  longitude: number;
  title?: string;
  subtitle?: string;
  /** bus | stop | selected */
  tone?: 'bus' | 'stop' | 'selected' | 'user';
};

type Props = {
  pins: MapPin[];
  height?: number;
  onMapPress?: (coords: { latitude: number; longitude: number }) => void;
  onPinPress?: (pin: MapPin) => void;
  followsUserLocation?: boolean;
  showsUserLocation?: boolean;
};

const DEFAULT_REGION: Region = {
  latitude: -1.286389,
  longitude: 36.817223,
  latitudeDelta: 0.08,
  longitudeDelta: 0.08,
};

function pinColor(tone: MapPin['tone'], palette: { primary: string }, semantic: { success: { fg: string }; warning: { fg: string }; info: { fg: string } }) {
  if (tone === 'bus') return semantic.success.fg;
  if (tone === 'selected') return semantic.warning.fg;
  if (tone === 'user') return semantic.info.fg;
  return palette.primary;
}

/**
 * Google Maps view without a Cloud Map ID (Maps SDK stays free / unlimited).
 */
export const TransportMapView: React.FC<Props> = ({
  pins,
  height = 280,
  onMapPress,
  onPinPress,
  followsUserLocation = false,
  showsUserLocation = false,
}) => {
  const { palette, spacing, typography, radius, semantic } = useTheme();

  const region = useMemo<Region>(() => {
    const withCoords = pins.filter(
      (p) => Number.isFinite(p.latitude) && Number.isFinite(p.longitude),
    );
    if (withCoords.length === 0) return DEFAULT_REGION;
    const lats = withCoords.map((p) => p.latitude);
    const lngs = withCoords.map((p) => p.longitude);
    const minLat = Math.min(...lats);
    const maxLat = Math.max(...lats);
    const minLng = Math.min(...lngs);
    const maxLng = Math.max(...lngs);
    const midLat = (minLat + maxLat) / 2;
    const midLng = (minLng + maxLng) / 2;
    const latDelta = Math.max(0.01, (maxLat - minLat) * 1.6 || 0.04);
    const lngDelta = Math.max(0.01, (maxLng - minLng) * 1.6 || 0.04);
    return {
      latitude: midLat,
      longitude: midLng,
      latitudeDelta: latDelta,
      longitudeDelta: lngDelta,
    };
  }, [pins]);

  if (pins.length === 0 && !showsUserLocation) {
    return (
      <View
        style={{
          height,
          borderRadius: radius.md,
          borderWidth: 1,
          borderColor: palette.border,
          backgroundColor: palette.surface,
          alignItems: 'center',
          justifyContent: 'center',
          padding: spacing.md,
        }}
      >
        <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, textAlign: 'center' }}>
          Waiting for location. When the driver starts the trip and GPS pings arrive, the bus will appear here.
        </Text>
      </View>
    );
  }

  return (
    <View style={{ height, borderRadius: radius.md, overflow: 'hidden', borderWidth: 1, borderColor: palette.border }}>
      <MapView
        style={StyleSheet.absoluteFill}
        provider={PROVIDER_GOOGLE}
        initialRegion={region}
        region={region}
        onPress={(e) => {
          const { latitude, longitude } = e.nativeEvent.coordinate;
          onMapPress?.({ latitude, longitude });
        }}
        showsUserLocation={showsUserLocation}
        followsUserLocation={followsUserLocation}
        showsMyLocationButton={showsUserLocation}
        toolbarEnabled={false}
      >
        {pins.map((pin) => (
          <Marker
            key={pin.id}
            coordinate={{ latitude: pin.latitude, longitude: pin.longitude }}
            title={pin.title}
            description={pin.subtitle}
            pinColor={pinColor(pin.tone, palette, semantic)}
            onPress={() => onPinPress?.(pin)}
          />
        ))}
      </MapView>
    </View>
  );
};
