<?php

use App\Models\Meeting;
use App\Models\User;
use Spatie\Permission\Models\Role;

/** طباعة أجندة الاجتماعات بخط Khalid Art (طلب العميل 2026-11-02) */
test('meetings day pdf embeds the Khalid Art font', function () {
    $user = tap(User::factory()->create())->assignRole(Role::findOrCreate('super-admin', 'web'));
    Meeting::create(['date' => '2026-11-02', 'time' => '10:30:00', 'subject' => 'مناقشة خطة تطوير المقرات', 'location' => 'مقر الوزارة']);

    $response = $this->actingAs($user)->get(route('meetings.print', ['date' => '2026-11-02']));

    $response->assertOk();
    $pdf = $response->getContent();
    expect($pdf)->toStartWith('%PDF')
        ->and($pdf)->toContain('KhalidArtbold')
        ->and($pdf)->not->toContain('DejaVuSans');
});
