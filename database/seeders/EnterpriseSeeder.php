<?php

namespace Database\Seeders;

use App\Models\Enterprise;
use App\Models\EnterpriseCategory;
use App\Support\EnsureEnterpriseSchema;
use Illuminate\Database\Seeder;

class EnterpriseSeeder extends Seeder
{
    public function run(): void
    {
        EnsureEnterpriseSchema::run();

        if (EnterpriseCategory::query()->whereNull('parent_id')->count() < 1) {
            $this->call(EnterpriseCategorySeeder::class);
        }

        $samples = [
            [
                'name' => '赣南仁心综合门诊部',
                'contact_name' => '刘医生',
                'contact_phone' => '13800002001',
                'major' => '医疗健康服务（线下实体服务类）',
                'minors' => ['综合门诊/专科门诊', '慢病管理服务机构'],
                'business_intro' => '提供全科与专科门诊服务，开展慢病随访与健康管理，面向周边居民日常就医需求。',
                'address' => '赣州市章贡区健康大道 88 号',
                'service_audience' => Enterprise::AUDIENCE_C,
                'source' => Enterprise::SOURCE_COMMUNITY,
                'settle_in_park' => true,
                'map_enabled' => true,
                'latitude' => 25.8319000,
                'longitude' => 114.9336000,
            ],
            [
                'name' => '回春堂中医馆',
                'contact_name' => '陈馆长',
                'contact_phone' => '13800002002',
                'major' => '医疗健康服务（线下实体服务类）',
                'minors' => ['中医馆、国医堂、中医理疗'],
                'business_intro' => '以中医诊疗与理疗为主，提供针灸、推拿、膏方调理等服务。',
                'address' => '赣州市章贡区文清路 126 号',
                'service_audience' => Enterprise::AUDIENCE_C,
                'source' => Enterprise::SOURCE_SELF,
                'settle_in_park' => false,
                'map_enabled' => false,
            ],
            [
                'name' => '康宁康复中心',
                'contact_name' => '周主任',
                'contact_phone' => '13800002003',
                'major' => '医疗健康服务（线下实体服务类）',
                'minors' => ['康复中心（术后康复、运动康复）'],
                'business_intro' => '专注术后康复与运动损伤康复，配备专业治疗团队与训练设备。',
                'address' => '赣南健康城 B 栋 2 层',
                'service_audience' => Enterprise::AUDIENCE_BC,
                'source' => Enterprise::SOURCE_COMMUNITY,
                'settle_in_park' => true,
                'map_enabled' => true,
                'latitude' => 25.8325000,
                'longitude' => 114.9342000,
            ],
            [
                'name' => '安颐养老院',
                'contact_name' => '王院长',
                'contact_phone' => '13800002004',
                'major' => '康养养老服务',
                'minors' => ['养老机构 / 养老院', '日间照料中心'],
                'business_intro' => '提供机构养老与日间照料服务，兼顾生活照护与文娱康养活动。',
                'address' => '赣县区康养路 16 号',
                'service_audience' => Enterprise::AUDIENCE_C,
                'source' => Enterprise::SOURCE_COMMUNITY,
                'settle_in_park' => false,
                'map_enabled' => false,
            ],
            [
                'name' => '邻里居家养老服务中心',
                'contact_name' => '李主任',
                'contact_phone' => '13800002005',
                'major' => '康养养老服务',
                'minors' => ['社区居家养老服务', '适老化改造服务'],
                'business_intro' => '面向社区开展居家养老上门服务，并提供适老化改造咨询与施工对接。',
                'address' => '赣州市章贡区文明大道社区',
                'service_audience' => Enterprise::AUDIENCE_C,
                'source' => Enterprise::SOURCE_SELF,
                'settle_in_park' => false,
                'map_enabled' => false,
            ],
            [
                'name' => '赣南艾草生物科技有限公司',
                'contact_name' => '张总',
                'contact_phone' => '13800002006',
                'major' => '健康产品生产制造（源头工厂）',
                'minors' => ['中成药、中药饮片（艾草、中药材加工）', '药食同源食品加工'],
                'business_intro' => '以艾草深加工为核心，生产中药饮片、药食同源食品及相关健康产品。',
                'address' => '赣州经开区生物产业园 3 号厂房',
                'service_audience' => Enterprise::AUDIENCE_B,
                'source' => Enterprise::SOURCE_COMMUNITY,
                'settle_in_park' => true,
                'map_enabled' => true,
                'latitude' => 25.8501000,
                'longitude' => 114.9208000,
            ],
            [
                'name' => '健安医疗器械制造厂',
                'contact_name' => '赵工',
                'contact_phone' => '13800002007',
                'major' => '健康产品生产制造（源头工厂）',
                'minors' => ['医疗器械（一类、二类）', '健康家居、康养器械'],
                'business_intro' => '生产一类、二类医疗器械及康养辅助器械，支持 OEM 与渠道合作。',
                'address' => '南康区工业大道 58 号',
                'service_audience' => Enterprise::AUDIENCE_B,
                'source' => Enterprise::SOURCE_COMMUNITY,
                'settle_in_park' => false,
                'map_enabled' => false,
            ],
            [
                'name' => '益康医药流通有限公司',
                'contact_name' => '孙经理',
                'contact_phone' => '13800002008',
                'major' => '健康商贸流通（渠道、经销、供应链）',
                'minors' => ['医药流通、药品配送', '医药/医疗冷链物流'],
                'business_intro' => '覆盖赣南区域的医药配送与冷链物流服务，服务医院、药店与连锁终端。',
                'address' => '赣州市水西物流园 A 区',
                'service_audience' => Enterprise::AUDIENCE_B,
                'source' => Enterprise::SOURCE_SELF,
                'settle_in_park' => false,
                'map_enabled' => false,
            ],
            [
                'name' => '大健康优选商贸',
                'contact_name' => '周经理',
                'contact_phone' => '13800002009',
                'major' => '健康商贸流通（渠道、经销、供应链）',
                'minors' => ['大健康产品批发零售', '线上健康电商'],
                'business_intro' => '大健康产品批发零售与线上电商渠道运营，对接工厂与终端门店。',
                'address' => '赣南健康城 C 栋商贸中心',
                'service_audience' => Enterprise::AUDIENCE_BC,
                'source' => Enterprise::SOURCE_COMMUNITY,
                'settle_in_park' => true,
                'map_enabled' => false,
            ],
            [
                'name' => '智健产业咨询事务所',
                'contact_name' => '吴顾问',
                'contact_phone' => '13800002010',
                'major' => '健康配套产业（产业服务，赋能企业）',
                'minors' => ['健康产业咨询策划', '知识产权、政策申报服务'],
                'business_intro' => '为健康企业提供产业策划、政策申报与知识产权辅导服务。',
                'address' => '赣南健康城 A 栋 1201',
                'service_audience' => Enterprise::AUDIENCE_B,
                'source' => Enterprise::SOURCE_COMMUNITY,
                'settle_in_park' => true,
                'map_enabled' => false,
            ],
            [
                'name' => '云康数字化医疗科技',
                'contact_name' => '郑总',
                'contact_phone' => '13800002011',
                'major' => '健康配套产业（产业服务，赋能企业）',
                'minors' => ['数字化医疗软件、健康系统开发', '医学检验检测'],
                'business_intro' => '研发健康管理系统与数字化医疗软件，并联合开展检验检测信息化服务。',
                'address' => '赣州高新区软件园 5 栋',
                'service_audience' => Enterprise::AUDIENCE_B,
                'source' => Enterprise::SOURCE_SELF,
                'settle_in_park' => false,
                'map_enabled' => false,
            ],
            [
                'name' => '赣南健康科普协会',
                'contact_name' => '何秘书长',
                'contact_phone' => '13800002012',
                'major' => '健康科普与公益社会组织',
                'minors' => ['行业协会、专委会', '健康科普传播机构'],
                'business_intro' => '组织健康科普活动与行业交流，推动健康知识进企业、进社区。',
                'address' => '赣州市章贡区公益路 9 号',
                'service_audience' => Enterprise::AUDIENCE_BC,
                'source' => Enterprise::SOURCE_COMMUNITY,
                'settle_in_park' => false,
                'map_enabled' => false,
            ],
        ];

        foreach ($samples as $sample) {
            $major = EnterpriseCategory::query()
                ->whereNull('parent_id')
                ->where('name', $sample['major'])
                ->first();

            if (! $major) {
                continue;
            }

            $minorIds = EnterpriseCategory::query()
                ->where('parent_id', $major->id)
                ->whereIn('name', $sample['minors'])
                ->pluck('id')
                ->all();

            if ($minorIds === []) {
                continue;
            }

            $enterprise = Enterprise::query()->updateOrCreate(
                ['name' => $sample['name']],
                [
                    'contact_name' => $sample['contact_name'],
                    'contact_phone' => $sample['contact_phone'],
                    'primary_category_id' => $major->id,
                    'business_intro' => $sample['business_intro'],
                    'address' => $sample['address'],
                    'latitude' => $sample['latitude'] ?? null,
                    'longitude' => $sample['longitude'] ?? null,
                    'map_enabled' => (bool) ($sample['map_enabled'] ?? false),
                    'service_audience' => $sample['service_audience'],
                    'source' => $sample['source'],
                    'settle_in_park' => (bool) $sample['settle_in_park'],
                    'images' => [],
                    'status' => Enterprise::STATUS_ON,
                ]
            );

            $enterprise->categories()->sync($minorIds);
        }
    }
}
