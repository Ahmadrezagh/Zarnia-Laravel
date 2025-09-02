<?php

namespace Database\Seeders;

use App\Models\QA;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class QASeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $qas = [
            [
                'question' => 'به هر دلیلی از طلای خریداری شده خوشم نیومد تعویض دارید؟',
                'answer'   => 'خیر، تعویض نداریم.',
            ],
            [
                'question' => 'برای تهران چطوری میفرستید؟شهرهای دیگه چطور؟',
                'answer'   => 'ارسال برای تهران با پیک و برای شهرهای دیگر با پست انجام می‌شود.',
            ],
            [
                'question' => 'آدرس‌تون برای خرید حضوری کجاست؟ساعت کارتون ؟',
                'answer'   => 'آدرس و ساعت کاری در بخش تماس با ما درج شده است.',
            ],
            [
                'question' => 'طلای‌تون ۱۸ عیاره؟ مجوز دارید؟',
                'answer'   => 'بله، طلای ما ۱۸ عیار است و دارای مجوز می‌باشد.',
            ],
        ];

        foreach ($qas as $qa) {
            QA::create($qa);
        }
    }
}
