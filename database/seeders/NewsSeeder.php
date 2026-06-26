<?php

namespace Database\Seeders;

use App\Models\News;
use Illuminate\Database\Seeder;

class NewsSeeder extends Seeder
{
    public function run(): void
    {
        $notices = [
            [
                'title' => '【园区通知】康瑞园区智慧服务平台正式上线，欢迎使用',
                'content' => '康瑞园区智慧服务平台已正式上线，访客预约、停车服务、新闻公告等功能现已开放，欢迎使用。',
            ],
            [
                'title' => '【温馨提示】预约服务请提前一天提交，停车请遵守园区管理规定',
                'content' => '请各位访客提前预约来访时间；进入园区停车请遵守园区管理规定，文明停车。',
            ],
            [
                'title' => '【服务公告】园区会议室、活动场地现已开放在线预约',
                'content' => '园区会议室、活动场地现已支持在线预约，请通过小程序「访客预约」或「场馆预定」提交申请。',
            ],
        ];

        foreach ($notices as $index => $notice) {
            News::query()->updateOrCreate(
                ['title' => $notice['title']],
                [
                    'type' => News::TYPE_NOTICE,
                    'content' => $notice['content'],
                    'publish_time' => now()->subDays(count($notices) - $index),
                    'is_top' => $index === 0,
                ]
            );
        }
    }
}
