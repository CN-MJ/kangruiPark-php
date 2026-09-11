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
                'title' => '【园区通知】赣南健康城智慧服务平台正式上线，欢迎使用',
                'content' => '赣南健康城智慧服务平台已正式上线，访客预约、停车服务、新闻公告、企业库等功能现已开放，欢迎使用。',
            ],
            [
                'title' => '【温馨提示】预约服务请提前一天提交，停车请遵守园区管理规定',
                'content' => '请各位访客提前预约来访时间；进入园区停车请遵守园区管理规定，文明停车。',
            ],
            [
                'title' => '【服务公告】园区会议室、活动场地现已开放在线预约',
                'content' => '园区会议室、活动场地现已支持在线预约，请通过小程序「访客预约」或「场馆预定」提交申请。',
            ],
            [
                'title' => '【企业库上线】健康服务企业可按大类/小类检索浏览',
                'content' => '首页「企业库」已开放，支持按医疗健康、康养养老、生产制造等分类筛选，欢迎查阅。',
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

        $newsList = [
            [
                'title' => '赣南健康城产业对接活动圆满举办',
                'content' => "本期产业对接聚焦医疗器械与康养服务，吸引区域内多家企业参与交流。\n活动现场达成多份合作意向，后续将通过小程序企业库持续展示优质企业。",
                'is_top' => true,
            ],
            [
                'title' => '园区智慧停车与访客预约功能升级说明',
                'content' => '访客可通过小程序完成预约、查看停车余位；园区动态与公告将持续在首页更新，请关注最新通知。',
                'is_top' => false,
            ],
            [
                'title' => '艾草深加工与药食同源企业走进健康城',
                'content' => "围绕赣南艾草特色产业，多家生产制造与商贸流通企业来园参观。\n欢迎在企业库选择「健康产品生产制造」「药食同源食品加工」等相关标签了解详情。",
                'is_top' => false,
            ],
            [
                'title' => '社区居家养老服务对接会成功举行',
                'content' => '针对老龄化服务需求，园区组织康养养老类企业与街道社区座谈，围绕日间照料、适老化改造等话题展开讨论。',
                'is_top' => false,
            ],
            [
                'title' => '中医馆与康复中心联合义诊进园区',
                'content' => '本周六上午在园区广场开展义诊活动，涵盖中医理疗与运动康复咨询，面向园区企业员工及周边居民开放。',
                'is_top' => false,
            ],
            [
                'title' => '健康数字化系统开发企业沙龙回顾',
                'content' => '健康配套产业专场沙龙分享了医学检验、产业咨询与数字化医疗软件案例，助力企业数字化转型。',
                'is_top' => false,
            ],
            [
                'title' => '赣南健康共同体企业入库通道正式开放',
                'content' => '走访企业信息可由园区工作台录入企业库，支持主大类与多选小类标签，便于访客按需筛选检索。',
                'is_top' => false,
            ],
            [
                'title' => '本月园区开放日：参观房源与了解入驻政策',
                'content' => '每月第二个周五为园区开放日，可预约参观在售房源并了解相关政策；详情见小程序政策与房源栏目。',
                'is_top' => false,
            ],
        ];

        foreach ($newsList as $index => $item) {
            News::query()->updateOrCreate(
                ['title' => $item['title']],
                [
                    'type' => News::TYPE_NEWS,
                    'content' => $item['content'],
                    'publish_time' => now()->subHours(count($newsList) - $index),
                    'is_top' => (bool) ($item['is_top'] ?? false),
                ]
            );
        }
    }
}
