import DateTimePicker, { DateTimePickerAndroid } from '@react-native-community/datetimepicker';
import React, { useState } from 'react';
import {
  Modal,
  Platform,
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useTheme } from '../theme/ThemeContext';
import { Button } from './Button';

function formatDateYmd(d: Date): string {
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

export interface DatePickerFieldProps {
  value: Date;
  onChange: (next: Date) => void;
  label?: string;
  maximumDate?: Date;
  minimumDate?: Date;
}

/**
 * Date field that never mounts an inline calendar in the form.
 * Android tablets otherwise render Material calendar in-flow, which
 * pushes lists and submit docks off-screen.
 */
export const DatePickerField: React.FC<DatePickerFieldProps> = ({
  value,
  onChange,
  label = 'Date',
  maximumDate,
  minimumDate,
}) => {
  const { colors, palette, spacing, typography } = useTheme();
  const insets = useSafeAreaInsets();
  const [iosOpen, setIosOpen] = useState(false);
  const [iosDraft, setIosDraft] = useState(value);

  const open = () => {
    if (Platform.OS === 'android') {
      DateTimePickerAndroid.open({
        value,
        mode: 'date',
        maximumDate,
        minimumDate,
        onChange: (event, date) => {
          if (event.type === 'dismissed' || !date) return;
          onChange(date);
        },
      });
      return;
    }
    setIosDraft(value);
    setIosOpen(true);
  };

  return (
    <>
      <Pressable
        onPress={open}
        accessibilityRole="button"
        accessibilityLabel={`${label}: ${formatDateYmd(value)}. Change date`}
        style={[
          styles.dateRow,
          { borderColor: palette.border, backgroundColor: palette.surfaceRaised },
        ]}
      >
        <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>{label}</Text>
        <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: typography.titleSmall.fontSize }}>
          {formatDateYmd(value)}
        </Text>
        <Text style={{ color: colors.primary, fontSize: typography.caption.fontSize, fontWeight: '600' }}>
          Change
        </Text>
      </Pressable>

      {Platform.OS === 'ios' ? (
        <Modal visible={iosOpen} transparent animationType="slide" onRequestClose={() => setIosOpen(false)}>
          <View style={styles.iosRoot}>
            <Pressable style={styles.iosScrim} onPress={() => setIosOpen(false)} />
            <View
              style={[
                styles.iosSheet,
                {
                  backgroundColor: palette.surfaceRaised,
                  paddingBottom: Math.max(insets.bottom, spacing.md),
                },
              ]}
            >
              <DateTimePicker
                value={iosDraft}
                mode="date"
                display="spinner"
                maximumDate={maximumDate}
                minimumDate={minimumDate}
                onChange={(_, date) => {
                  if (date) setIosDraft(date);
                }}
              />
              <View style={{ paddingHorizontal: spacing.md, gap: spacing.sm }}>
                <Button
                  label="Done"
                  onPress={() => {
                    onChange(iosDraft);
                    setIosOpen(false);
                  }}
                />
                <Button label="Cancel" variant="ghost" onPress={() => setIosOpen(false)} />
              </View>
            </View>
          </View>
        </Modal>
      ) : null}
    </>
  );
};

const styles = StyleSheet.create({
  dateRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    borderWidth: StyleSheet.hairlineWidth,
    borderRadius: 10,
    padding: 12,
    marginBottom: 8,
  },
  iosRoot: {
    flex: 1,
    justifyContent: 'flex-end',
  },
  iosScrim: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(0,0,0,0.4)',
  },
  iosSheet: {
    borderTopLeftRadius: 16,
    borderTopRightRadius: 16,
    paddingTop: 8,
  },
});
