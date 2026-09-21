import { PRODUCT } from '@erp/core';
import React from 'react';
import { View } from 'react-native';
import Svg, { Circle, Path, Rect } from 'react-native-svg';

type Variant = 'default' | 'white';

/**
 * Edulynk icon from the public website favicon (rounded square, three bars, cyan node).
 */
export const EdulynkMark: React.FC<{
  size?: number;
  variant?: Variant;
}> = ({ size = 40, variant = 'default' }) => {
  const fill = variant === 'white' ? '#FFFFFF' : PRODUCT.colors.brand;
  const stroke = variant === 'white' ? PRODUCT.colors.navy : '#FFFFFF';
  const node = PRODUCT.colors.cyan;

  return (
    <View style={{ width: size, height: size }}>
      <Svg width={size} height={size} viewBox="0 0 36 36">
        <Rect width={36} height={36} rx={10} fill={fill} />
        <Path
          d="M10 13.5h16M10 18h12.5M10 22.5h16"
          stroke={stroke}
          strokeWidth={2.3}
          strokeLinecap="round"
        />
        <Circle cx={25.2} cy={18} r={2.2} fill={node} />
      </Svg>
    </View>
  );
};
