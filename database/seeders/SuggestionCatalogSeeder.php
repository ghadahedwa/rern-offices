<?php

namespace Database\Seeders;

use App\Livewire\Feedback\Suggestion;
use App\Models\SuggestionDomain;
use App\Models\SuggestionTopic;
use Illuminate\Database\Seeder;

/**
 * يزرع مجالات المقترح وعناوينها من Suggestion::DOMAINS (مصدر واحد).
 * idempotent — يمكن تشغيله بأمان أكثر من مرة (updateOrCreate بالمفتاح).
 */
class SuggestionCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $domainOrder = 0;

        foreach (Suggestion::DOMAINS as $domainKey => [$domainName, $topics]) {
            $domain = SuggestionDomain::updateOrCreate(
                ['key' => $domainKey],
                ['name' => $domainName, 'order' => ++$domainOrder],
            );

            $topicOrder = 0;
            foreach ($topics as $topicKey => $topicName) {
                SuggestionTopic::updateOrCreate(
                    ['key' => $topicKey],
                    [
                        'suggestion_domain_id' => $domain->id,
                        'name'                 => $topicName,
                        'order'                => ++$topicOrder,
                    ],
                );
            }
        }
    }
}
