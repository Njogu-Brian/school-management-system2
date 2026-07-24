<?php

namespace App\Services;

use App\Models\DropOffPoint;
use App\Models\StudentAssignment;

class TransportFeeCalculator
{
    /**
     * Calculate term list price from morning + evening drop-off points.
     *
     * @return array{amount: float|null, breakdown: array|null, errors: array<int, string>, can_calculate: bool}
     */
    public static function calculate(?int $morningPointId, ?int $eveningPointId): array
    {
        $ids = array_values(array_filter([$morningPointId, $eveningPointId]));
        $points = $ids
            ? DropOffPoint::withTrashed()->whereIn('id', $ids)->get()->keyBy('id')
            : collect();

        $morning = $morningPointId ? ($points[$morningPointId] ?? null) : null;
        $evening = $eveningPointId ? ($points[$eveningPointId] ?? null) : null;

        return self::calculateFromPoints($morning, $evening);
    }

    /**
     * @return array{amount: float|null, breakdown: array|null, errors: array<int, string>, can_calculate: bool}
     */
    public static function calculateFromAssignment(?StudentAssignment $assignment): array
    {
        if (!$assignment) {
            return self::emptyResult(['Morning and evening drop-off points are required to calculate transport fee.']);
        }

        return self::calculate(
            $assignment->morning_drop_off_point_id,
            $assignment->evening_drop_off_point_id
        );
    }

    /**
     * @return array{amount: float|null, breakdown: array|null, errors: array<int, string>, can_calculate: bool}
     */
    public static function calculateFromPoints(?DropOffPoint $morning, ?DropOffPoint $evening): array
    {
        if (!$morning && !$evening) {
            return self::emptyResult(['Morning and evening drop-off points are required to calculate transport fee.']);
        }

        if (!$morning || !$evening) {
            return self::emptyResult(['Both morning and evening drop-off points are required to calculate transport fee.']);
        }

        $morningOwn = $morning->isOwnMeans();
        $eveningOwn = $evening->isOwnMeans();

        if ($morningOwn && $eveningOwn) {
            return [
                'amount' => 0.0,
                'breakdown' => [
                    'formula' => 'own_means_both',
                    'label' => 'Own means (morning & evening) — no transport fee',
                    'morning' => self::pointSnapshot($morning),
                    'evening' => self::pointSnapshot($evening),
                    'components' => [],
                ],
                'errors' => [],
                'can_calculate' => true,
            ];
        }

        if ($morningOwn && !$eveningOwn) {
            return self::oneWayResult($evening, 'evening', 'Own means morning — one-way evening fare');
        }

        if ($eveningOwn && !$morningOwn) {
            return self::oneWayResult($morning, 'morning', 'Own means evening — one-way morning fare');
        }

        if ((int) $morning->id === (int) $evening->id) {
            $twoWay = self::requireTwoWay($morning);
            if ($twoWay['error']) {
                return self::emptyResult([$twoWay['error']]);
            }

            $amount = round((float) $twoWay['amount'], 2);

            return [
                'amount' => $amount,
                'breakdown' => [
                    'formula' => 'same_point_two_way',
                    'label' => sprintf('Two-way fare at %s', $morning->name),
                    'morning' => self::pointSnapshot($morning),
                    'evening' => self::pointSnapshot($evening),
                    'components' => [
                        [
                            'point_id' => $morning->id,
                            'point_name' => $morning->name,
                            'rate_type' => 'two_way',
                            'rate' => $amount,
                            'share' => $amount,
                        ],
                    ],
                ],
                'errors' => [],
                'can_calculate' => true,
            ];
        }

        $morningTwoWay = self::requireTwoWay($morning);
        $eveningTwoWay = self::requireTwoWay($evening);
        $errors = array_values(array_filter([$morningTwoWay['error'], $eveningTwoWay['error']]));
        if ($errors) {
            return self::emptyResult($errors);
        }

        $morningHalf = round(((float) $morningTwoWay['amount']) / 2, 2);
        $eveningHalf = round(((float) $eveningTwoWay['amount']) / 2, 2);
        $amount = round($morningHalf + $eveningHalf, 2);

        return [
            'amount' => $amount,
            'breakdown' => [
                'formula' => 'mixed_half_two_way',
                'label' => sprintf(
                    'Half of %s two-way (%s) + half of %s two-way (%s)',
                    $morning->name,
                    number_format($morningHalf, 2),
                    $evening->name,
                    number_format($eveningHalf, 2)
                ),
                'morning' => self::pointSnapshot($morning),
                'evening' => self::pointSnapshot($evening),
                'components' => [
                    [
                        'point_id' => $morning->id,
                        'point_name' => $morning->name,
                        'rate_type' => 'two_way_half',
                        'rate' => (float) $morningTwoWay['amount'],
                        'share' => $morningHalf,
                    ],
                    [
                        'point_id' => $evening->id,
                        'point_name' => $evening->name,
                        'rate_type' => 'two_way_half',
                        'rate' => (float) $eveningTwoWay['amount'],
                        'share' => $eveningHalf,
                    ],
                ],
            ],
            'errors' => [],
            'can_calculate' => true,
        ];
    }

    /**
     * @return array{amount: float|null, breakdown: array|null, errors: array<int, string>, can_calculate: bool}
     */
    private static function oneWayResult(DropOffPoint $point, string $leg, string $label): array
    {
        $oneWay = self::requireOneWay($point);
        if ($oneWay['error']) {
            return self::emptyResult([$oneWay['error']]);
        }

        $amount = round((float) $oneWay['amount'], 2);

        return [
            'amount' => $amount,
            'breakdown' => [
                'formula' => 'own_means_one_way',
                'label' => $label,
                'morning' => $leg === 'morning' ? self::pointSnapshot($point) : ['own_means' => true],
                'evening' => $leg === 'evening' ? self::pointSnapshot($point) : ['own_means' => true],
                'components' => [
                    [
                        'point_id' => $point->id,
                        'point_name' => $point->name,
                        'rate_type' => 'one_way',
                        'rate' => $amount,
                        'share' => $amount,
                    ],
                ],
            ],
            'errors' => [],
            'can_calculate' => true,
        ];
    }

    /**
     * @return array{amount: float|null, error: string|null}
     */
    private static function requireTwoWay(DropOffPoint $point): array
    {
        if ($point->two_way_amount === null) {
            return [
                'amount' => null,
                'error' => "Two-way rate is not set for drop-off point \"{$point->name}\".",
            ];
        }

        return ['amount' => (float) $point->two_way_amount, 'error' => null];
    }

    /**
     * @return array{amount: float|null, error: string|null}
     */
    private static function requireOneWay(DropOffPoint $point): array
    {
        if ($point->one_way_amount === null) {
            return [
                'amount' => null,
                'error' => "One-way rate is not set for drop-off point \"{$point->name}\".",
            ];
        }

        return ['amount' => (float) $point->one_way_amount, 'error' => null];
    }

    private static function pointSnapshot(DropOffPoint $point): array
    {
        return [
            'id' => $point->id,
            'name' => $point->name,
            'two_way_amount' => $point->two_way_amount !== null ? (float) $point->two_way_amount : null,
            'one_way_amount' => $point->one_way_amount !== null ? (float) $point->one_way_amount : null,
            'own_means' => $point->isOwnMeans(),
        ];
    }

    /**
     * @param  array<int, string>  $errors
     * @return array{amount: float|null, breakdown: array|null, errors: array<int, string>, can_calculate: bool}
     */
    private static function emptyResult(array $errors): array
    {
        return [
            'amount' => null,
            'breakdown' => null,
            'errors' => $errors,
            'can_calculate' => false,
        ];
    }
}
