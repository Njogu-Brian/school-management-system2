<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$logs = \App\Models\CommunicationLog::where('channel','sms')
    ->whereDate('sent_at', today())
    ->orderBy('sent_at')
    ->get(['id','contact','recipient_id','recipient_type','status','sent_at','message']);

echo "Total SMS today: " . $logs->count() . "\n";
echo "Unique contacts: " . $logs->pluck('contact')->unique()->count() . "\n";

$byContact = $logs->groupBy('contact');
$duplicates = $byContact->filter(fn($g) => $g->count() > 1);
echo "Contacts with more than 1 SMS: " . $duplicates->count() . "\n\n";

echo "=== Duplicates (same contact, same child - recipient_id) ===\n";
foreach ($duplicates as $contact => $group) {
    $byRecipient = $group->groupBy('recipient_id');
    $sameChildTwice = $byRecipient->filter(fn($g) => $g->count() > 1);
    foreach ($sameChildTwice as $rid => $g) {
        echo "Contact $contact, student_id $rid => " . $g->count() . " times\n";
    }
}

echo "\n=== Sample messages (profile_update) ===\n";
$profileLogs = $logs->filter(fn($l) => str_contains($l->message ?? '', 'profile') || str_contains($l->message ?? '', 'confirm'));
echo "Profile/confirm messages: " . $profileLogs->count() . "\n";

$students = \App\Models\Student::where('archive',0)->where('is_alumni',false)->count();
echo "\nActive students (archive=0): $students\n";
