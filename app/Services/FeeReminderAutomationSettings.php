<?php

namespace App\Services;

/**
 * Persisted under settings.key = fee_reminder_automation (JSON).
 */
class FeeReminderAutomationSettings
{
    public const SETTING_KEY = 'fee_reminder_automation';

    public function __construct(
        public bool $enabled,
        public string $sendTime,
        /** @var list<int> */
        public array $daysBeforeDue,
        /** @var list<int> */
        public array $daysAfterOverdue,
        /** @var list<string> */
        public array $channelsBeforeDue,
        /** @var list<string> */
        public array $channelsOnDue,
        /** @var list<string> */
        public array $channelsAfterOverdue,
        public bool $clearanceEnabled,
        /** @var list<int> */
        public array $clearanceDaysBefore,
        /** @var list<int> */
        public array $clearanceDaysAfter,
        /** @var list<string> */
        public array $clearanceChannelsBefore,
        /** @var list<string> */
        public array $clearanceChannelsOn,
        /** @var list<string> */
        public array $clearanceChannelsAfter,
    ) {
    }

    public static function defaults(): self
    {
        $ch = ['email', 'sms', 'whatsapp'];
        return new self(
            enabled: true,
            sendTime: '09:00',
            daysBeforeDue: [7, 3, 1],
            daysAfterOverdue: [1, 3, 7],
            channelsBeforeDue: $ch,
            channelsOnDue: $ch,
            channelsAfterOverdue: $ch,
            clearanceEnabled: true,
            clearanceDaysBefore: [2, 1],
            clearanceDaysAfter: [1, 3],
            clearanceChannelsBefore: $ch,
            clearanceChannelsOn: $ch,
            clearanceChannelsAfter: $ch,
        );
    }

    public static function load(): self
    {
        $raw = setting(self::SETTING_KEY);
        if (!$raw) {
            return self::defaults();
        }
        $data = is_string($raw) ? json_decode($raw, true) : $raw;
        if (!is_array($data)) {
            return self::defaults();
        }
        $d = self::defaults();
        return new self(
            enabled: (bool) ($data['enabled'] ?? $d->enabled),
            sendTime: self::normalizeTime((string) ($data['send_time'] ?? $d->sendTime)),
            daysBeforeDue: self::intList($data['days_before_due'] ?? $d->daysBeforeDue),
            daysAfterOverdue: self::intList($data['days_after_overdue'] ?? $d->daysAfterOverdue),
            channelsBeforeDue: self::channelList($data['channels_before_due'] ?? $d->channelsBeforeDue),
            channelsOnDue: self::channelList($data['channels_on_due'] ?? $d->channelsOnDue),
            channelsAfterOverdue: self::channelList($data['channels_after_overdue'] ?? $d->channelsAfterOverdue),
            clearanceEnabled: (bool) ($data['clearance_enabled'] ?? $d->clearanceEnabled),
            clearanceDaysBefore: self::intList($data['clearance_days_before'] ?? $d->clearanceDaysBefore),
            clearanceDaysAfter: self::intList($data['clearance_days_after'] ?? $d->clearanceDaysAfter),
            clearanceChannelsBefore: self::channelList($data['clearance_channels_before'] ?? $d->clearanceChannelsBefore),
            clearanceChannelsOn: self::channelList($data['clearance_channels_on'] ?? $d->clearanceChannelsOn),
            clearanceChannelsAfter: self::channelList($data['clearance_channels_after'] ?? $d->clearanceChannelsAfter),
        );
    }

    public function save(): void
    {
        setting_set(self::SETTING_KEY, json_encode($this->toArray()));
    }

    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'send_time' => $this->sendTime,
            'days_before_due' => array_values(array_unique($this->daysBeforeDue)),
            'days_after_overdue' => array_values(array_unique($this->daysAfterOverdue)),
            'channels_before_due' => array_values(array_unique($this->channelsBeforeDue)),
            'channels_on_due' => array_values(array_unique($this->channelsOnDue)),
            'channels_after_overdue' => array_values(array_unique($this->channelsAfterOverdue)),
            'clearance_enabled' => $this->clearanceEnabled,
            'clearance_days_before' => array_values(array_unique($this->clearanceDaysBefore)),
            'clearance_days_after' => array_values(array_unique($this->clearanceDaysAfter)),
            'clearance_channels_before' => array_values(array_unique($this->clearanceChannelsBefore)),
            'clearance_channels_on' => array_values(array_unique($this->clearanceChannelsOn)),
            'clearance_channels_after' => array_values(array_unique($this->clearanceChannelsAfter)),
        ];
    }

    public static function fromValidatedArray(array $v): self
    {
        $d = self::defaults();
        return new self(
            enabled: !empty($v['enabled']),
            sendTime: self::normalizeTime((string) ($v['send_time'] ?? $d->sendTime)),
            daysBeforeDue: self::parseIntListString($v['days_before_due'] ?? '', $d->daysBeforeDue),
            daysAfterOverdue: self::parseIntListString($v['days_after_overdue'] ?? '', $d->daysAfterOverdue),
            channelsBeforeDue: self::channelList($v['channels_before_due'] ?? $d->channelsBeforeDue),
            channelsOnDue: self::channelList($v['channels_on_due'] ?? $d->channelsOnDue),
            channelsAfterOverdue: self::channelList($v['channels_after_overdue'] ?? $d->channelsAfterOverdue),
            clearanceEnabled: !empty($v['clearance_enabled']),
            clearanceDaysBefore: self::parseIntListString($v['clearance_days_before'] ?? '', $d->clearanceDaysBefore),
            clearanceDaysAfter: self::parseIntListString($v['clearance_days_after'] ?? '', $d->clearanceDaysAfter),
            clearanceChannelsBefore: self::channelList($v['clearance_channels_before'] ?? $d->clearanceChannelsBefore),
            clearanceChannelsOn: self::channelList($v['clearance_channels_on'] ?? $d->clearanceChannelsOn),
            clearanceChannelsAfter: self::channelList($v['clearance_channels_after'] ?? $d->clearanceChannelsAfter),
        );
    }

    protected static function normalizeTime(string $t): string
    {
        if (!preg_match('/^([01]?\d|2[0-3]):([0-5]\d)$/', $t, $m)) {
            return '09:00';
        }
        return sprintf('%02d:%02d', (int) $m[1], (int) $m[2]);
    }

    /**
     * @param mixed $list
     * @return list<int>
     */
    protected static function intList($list): array
    {
        if (!is_array($list)) {
            return [];
        }
        $out = [];
        foreach ($list as $n) {
            $i = (int) $n;
            if ($i >= 0 && $i <= 365) {
                $out[] = $i;
            }
        }
        return array_values(array_unique($out));
    }

    /**
     * @return list<int>
     */
    protected static function parseIntListString(string $s, array $fallback): array
    {
        $s = trim($s);
        if ($s === '') {
            return $fallback;
        }
        $parts = preg_split('/[\s,]+/', $s);
        $out = [];
        foreach ($parts as $p) {
            $i = (int) $p;
            if ($i >= 0 && $i <= 365) {
                $out[] = $i;
            }
        }
        return $out !== [] ? array_values(array_unique($out)) : $fallback;
    }

    /**
     * @param mixed $list
     * @return list<string>
     */
    protected static function channelList($list): array
    {
        if (!is_array($list)) {
            return ['email', 'sms', 'whatsapp'];
        }
        $allowed = ['email', 'sms', 'whatsapp'];
        $out = [];
        foreach ($list as $c) {
            $c = strtolower((string) $c);
            if (in_array($c, $allowed, true)) {
                $out[] = $c;
            }
        }
        return $out !== [] ? array_values(array_unique($out)) : ['email', 'sms', 'whatsapp'];
    }
}
