// @ts-nocheck
import React from 'react';
import { createStackNavigator } from '@react-navigation/stack';
import { PaymentsHubScreen } from '@screens/Payments/PaymentsHubScreen';
import { PaymentDetailScreen } from '@screens/Finance/PaymentDetailScreen';
import { TransactionDetailScreen } from '@screens/Payments/TransactionDetailScreen';

const Stack = createStackNavigator();

export const PaymentsNavigator = () => {
    return (
        <Stack.Navigator screenOptions={{ headerShown: false }} initialRouteName="PaymentsHub">
            <Stack.Screen name="PaymentsHub" component={PaymentsHubScreen} />
            <Stack.Screen name="PaymentDetail" component={PaymentDetailScreen} />
            <Stack.Screen name="TransactionDetail" component={TransactionDetailScreen} />
        </Stack.Navigator>
    );
};
