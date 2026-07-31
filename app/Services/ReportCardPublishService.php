<?php

namespace App\Services;

use App\Mail\GenericMail;
use App\Models\Academics\ReportCard;
use App\Models\CommunicationLog;
use App\Models\CommunicationTemplate;
use App\Models\Family;
use App\Models\FamilyReportPortalLink;
use App\Models\Student;
use App\Services\SMSService;
use App\Services\WhatsAppService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class ReportCardPublishService
{
    public function __construct(
        protected SMSService $smsService,
        protected WhatsAppService $whatsAppService
    ) {}

    /**
     * @param  array<int>  $reportCardIds
     * @param  array<string>  $channels  sms|email|whatsapp
     * @param  array<string, int|string|null>  $templateIds  channel => communication_templates.id override
     * @return array{published: int, families_notified: int, failed: int}
     */
    public function publishMany(array $reportCardIds, array $channels = [], bool $notify = true, array $templateIds = []): array
    {
        $channels = array_values(array_unique(array_filter($channels)));
        $reportCards = ReportCard::query()
            ->with(['student.family', 'term', 'academicYear'])
            ->whereIn('id', $reportCardIds)
            ->get();

        $published = 0;
        foreach ($reportCards as $reportCard) {
            $this->publishOne($reportCard);
            $published++;
        }

        $familiesNotified = 0;
        $failed = 0;

        if ($notify && $channels !== []) {
            $groups = $this->groupByFamily($reportCards);
            foreach ($groups as $group) {
                $ok = $this->notifyFamilyGroup($group, $channels, $templateIds);
                if ($ok) {
                    $familiesNotified++;
                } else {
                    $failed++;
                }
            }
        }

        return [
            'published' => $published,
            'families_notified' => $familiesNotified,
            'failed' => $failed,
        ];
    }

    public function publishOne(ReportCard $reportCard): ReportCard
    {
        if (empty($reportCard->public_token)) {
            $reportCard->public_token = Str::random(40);
        }

        $reportCard->published_at = now();
        $reportCard->published_by = optional(Auth::user()?->staff)->id;
        $reportCard->save();

        try {
            app(ParentAppNotifyService::class)->notifyReportCardPublished($reportCard->fresh(['student']) ?? $reportCard);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Report card parent app notify failed: '.$e->getMessage(), [
                'report_card_id' => $reportCard->id,
            ]);
        }

        return $reportCard->fresh();
    }

    /**
     * Report form template codes per channel, in order of preference.
     * WhatsApp falls back to the SMS wording so a school that has not customised
     * the WhatsApp template still gets a sensible message.
     *
     * @return list<string>
     */
    public static function templateCodesForChannel(string $channel): array
    {
        return match ($channel) {
            'email' => ['academics_family_report_portal_email', 'academics_report_email'],
            'whatsapp' => ['academics_family_report_portal_whatsapp', 'academics_family_report_portal_sms', 'academics_report_sms'],
            default => ['academics_family_report_portal_sms', 'academics_report_sms'],
        };
    }

    public static function resolveTemplateForChannel(string $channel, int|string|null $preferredId = null): ?CommunicationTemplate
    {
        if ($preferredId) {
            $chosen = CommunicationTemplate::find($preferredId);
            if ($chosen) {
                return $chosen;
            }
        }

        foreach (self::templateCodesForChannel($channel) as $code) {
            $template = CommunicationTemplate::where('code', $code)->first();
            if ($template) {
                return $template;
            }
        }

        return null;
    }

    /**
     * Templates a user may pick for each channel on the publish screens.
     *
     * @return array<string, \Illuminate\Support\Collection<int, CommunicationTemplate>>
     */
    public static function selectableTemplates(): array
    {
        $all = CommunicationTemplate::orderBy('title')->get();

        return [
            'sms' => $all->where('type', 'sms')->values(),
            'whatsapp' => $all->whereIn('type', ['whatsapp', 'sms'])->values(),
            'email' => $all->where('type', 'email')->values(),
        ];
    }

    /**
     * @param  Collection<int, ReportCard>  $reportCards
     * @return list<array{family_id: ?int, student_id: ?int, report_cards: Collection, year_id: int, term_id: int, students: Collection}>
     */
    protected function groupByFamily(Collection $reportCards): array
    {
        $groups = [];

        foreach ($reportCards as $reportCard) {
            $student = $reportCard->student;
            if (! $student) {
                continue;
            }

            $key = $student->family_id
                ? 'family:'.$student->family_id
                : 'student:'.$student->id;

            if (! isset($groups[$key])) {
                $groups[$key] = [
                    'family_id' => $student->family_id ? (int) $student->family_id : null,
                    'student_id' => $student->family_id ? null : (int) $student->id,
                    'report_cards' => collect(),
                    'year_id' => (int) $reportCard->academic_year_id,
                    'term_id' => (int) $reportCard->term_id,
                    'students' => collect(),
                ];
            }

            $groups[$key]['report_cards']->push($reportCard);
            $groups[$key]['students']->push($student);
        }

        return array_values($groups);
    }

    /**
     * @param  array{family_id: ?int, student_id: ?int, report_cards: Collection, year_id: int, term_id: int, students: Collection}  $group
     * @param  array<string>  $channels
     * @param  array<string, int|string|null>  $templateIds
     */
    protected function notifyFamilyGroup(array $group, array $channels, array $templateIds = []): bool
    {
        $portalLink = ensure_family_report_portal_link(
            $group['family_id'],
            $group['student_id'],
            $group['year_id'],
            $group['term_id']
        );

        if (! $portalLink) {
            return false;
        }

        if ($group['family_id']) {
            ensure_family_payment_link($group['family_id']);
        }

        $portalLink->last_sent_at = now();
        $portalLink->save();

        $primaryStudent = $group['students']->first();
        if (! $primaryStudent) {
            return false;
        }

        $portalUrl = $portalLink->getUrl();
        $termName = $group['report_cards']->first()?->term?->name ?? 'Term';
        $academicYear = $group['report_cards']->first()?->academicYear?->year ?? '';
        $childrenNames = $group['students']
            ->map(fn (Student $s) => $s->full_name ?? trim($s->first_name.' '.$s->last_name))
            ->unique()
            ->implode(', ');
        $schoolName = setting('school_name', config('app.name', 'School'));

        $anySent = false;

        foreach ($channels as $channel) {
            $recipients = CommunicationHelperService::collectRecipients([
                'target' => 'student',
                'student_id' => $primaryStudent->id,
            ], $channel === 'whatsapp' ? 'whatsapp' : $channel);

            if (empty($recipients)) {
                continue;
            }

            $template = self::resolveTemplateForChannel($channel, $templateIds[$channel] ?? null);

            foreach (CommunicationHelperService::expandRecipientsToPairs($recipients) as $pair) {
                [$contact, $entity, $parentMeta] = array_pad($pair, 3, null);

                if ($template) {
                    $vars = [
                        'parent_name' => $parentMeta['name'] ?? 'Parent',
                        'family_portal_link' => $portalUrl,
                        'report_card_link' => $portalUrl,
                        'finance_portal_link' => $portalUrl,
                        'term_name' => $termName,
                        'academic_year' => (string) $academicYear,
                        'school_name' => $schoolName,
                        'children_names' => $childrenNames,
                        'student_name' => $primaryStudent->full_name ?? $primaryStudent->first_name,
                    ];
                    $body = $template->content;
                    $finalSubject = $template->subject ?? 'Report Cards Available';
                    foreach ($vars as $key => $value) {
                        $body = str_replace('{{'.$key.'}}', (string) $value, $body);
                        $finalSubject = str_replace('{{'.$key.'}}', (string) $value, $finalSubject);
                    }
                    $body = personalize_message_for_parent_recipient(
                        $body,
                        $entity instanceof Student ? $entity : $primaryStudent,
                        $parentMeta
                    );
                    if ($body === null) {
                        continue;
                    }
                    $finalSubject = personalize_message_for_parent_recipient(
                        $finalSubject,
                        $entity instanceof Student ? $entity : $primaryStudent,
                        $parentMeta
                    ) ?? $finalSubject;
                } else {
                    $body = personalize_message_for_parent_recipient(
                        "Dear Parent,\n\nReport cards for {$childrenNames} ({$termName} {$academicYear}) are now available:\n{$portalUrl}\n\n{$schoolName}",
                        $entity instanceof Student ? $entity : $primaryStudent,
                        $parentMeta
                    );
                    if ($body === null) {
                        continue;
                    }
                    $finalSubject = 'Report Cards – '.$termName.' '.$academicYear;
                }

                try {
                    if ($channel === 'sms') {
                        $this->smsService->sendSMS($contact, $body, $this->smsService->getFinanceSenderId());
                    } elseif ($channel === 'whatsapp') {
                        $this->whatsAppService->sendMessage($contact, $body);
                    } else {
                        Mail::to($contact)->send(new GenericMail($finalSubject, $body));
                    }

                    CommunicationLog::create([
                        'recipient_type' => 'student',
                        'recipient_id' => $primaryStudent->id,
                        'contact' => $contact,
                        'channel' => $channel,
                        'title' => 'Family Report Portal',
                        'message' => $body,
                        'type' => 'report_card',
                        'status' => 'sent',
                        'classroom_id' => $primaryStudent->classroom_id,
                        'scope' => 'family_report_portal',
                        'sent_at' => now(),
                    ]);

                    $anySent = true;
                } catch (\Throwable $e) {
                    CommunicationLog::create([
                        'recipient_type' => 'student',
                        'recipient_id' => $primaryStudent->id,
                        'contact' => $contact,
                        'channel' => $channel,
                        'title' => 'Family Report Portal',
                        'message' => $body ?? '',
                        'type' => 'report_card',
                        'status' => 'failed',
                        'response' => $e->getMessage(),
                        'classroom_id' => $primaryStudent->classroom_id,
                        'scope' => 'family_report_portal',
                        'sent_at' => now(),
                    ]);
                }
            }
        }

        return $anySent;
    }
}
