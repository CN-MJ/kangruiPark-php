<?php

namespace Database\Seeders;

use App\Models\Enterprise;
use App\Models\EnterpriseCategory;
use App\Support\EnsureEnterpriseSchema;
use App\Support\GanzhouDistrict;
use Illuminate\Database\Seeder;

class EnterpriseSeeder extends Seeder
{
    public function run(): void
    {
        EnsureEnterpriseSchema::run();

        if (EnterpriseCategory::query()->whereNull('parent_id')->count() < 1) {
            $this->call(EnterpriseCategorySeeder::class);
        }

        // 坐标仅为测试分布用，非真实门店位置；地址含县区名以便解析/筛选。
        $samples = [
            [
                'name' => '赣南仁心综合门诊部',
                'contact_name' => '刘医生',
                'contact_phone' => '13800002001',
                'major' => '医疗健康服务（线下实体服务类）',
                'minors' => ['综合门诊/专科门诊', '慢病管理服务机构'],
                'business_intro' => '提供全科与专科门诊服务，开展慢病随访与健康管理，面向周边居民日常就医需求。',
                'district' => '章贡区',
                'address' => '赣州市章贡区测试路健康大道 88 号（测试定位）',
                'service_audience' => Enterprise::AUDIENCE_C,
                'source' => Enterprise::SOURCE_COMMUNITY,
                'settle_in_park' => true,
                'map_enabled' => true,
                'latitude' => 25.8321000,
                'longitude' => 114.9340000,
            ],
            [
                'name' => '回春堂中医馆',
                'contact_name' => '陈馆长',
                'contact_phone' => '13800002002',
                'major' => '医疗健康服务（线下实体服务类）',
                'minors' => ['中医馆、国医堂、中医理疗'],
                'business_intro' => '以中医诊疗与理疗为主，提供针灸、推拿、膏方调理等服务。',
                'district' => '南康区',
                'address' => '赣州市南康区测试中医街 12 号（测试定位）',
                'service_audience' => Enterprise::AUDIENCE_C,
                'source' => Enterprise::SOURCE_SELF,
                'settle_in_park' => false,
                'map_enabled' => true,
                'latitude' => 25.6625000,
                'longitude' => 114.7662000,
            ],
            [
                'name' => '康宁康复中心',
                'contact_name' => '周主任',
                'contact_phone' => '13800002003',
                'major' => '医疗健康服务（线下实体服务类）',
                'minors' => ['康复中心（术后康复、运动康复）'],
                'business_intro' => '专注术后康复与运动损伤康复，配备专业治疗团队与训练设备。',
                'district' => '赣县区',
                'address' => '赣州市赣县区测试康复路 6 号（测试定位）',
                'service_audience' => Enterprise::AUDIENCE_BC,
                'source' => Enterprise::SOURCE_COMMUNITY,
                'settle_in_park' => true,
                'map_enabled' => true,
                'latitude' => 25.8615000,
                'longitude' => 115.0128000,
            ],
            [
                'name' => '安颐养老院',
                'contact_name' => '王院长',
                'contact_phone' => '13800002004',
                'major' => '康养养老服务',
                'minors' => ['养老机构 / 养老院', '日间照料中心'],
                'business_intro' => '提供机构养老与日间照料服务，兼顾生活照护与文娱康养活动。',
                'district' => '信丰县',
                'address' => '赣州市信丰县测试康养大道 16 号（测试定位）',
                'service_audience' => Enterprise::AUDIENCE_C,
                'source' => Enterprise::SOURCE_COMMUNITY,
                'settle_in_park' => false,
                'map_enabled' => true,
                'latitude' => 25.3870000,
                'longitude' => 114.9240000,
            ],
            [
                'name' => '邻里居家养老服务中心',
                'contact_name' => '李主任',
                'contact_phone' => '13800002005',
                'major' => '康养养老服务',
                'minors' => ['社区居家养老服务', '适老化改造服务'],
                'business_intro' => '面向社区开展居家养老上门服务，并提供适老化改造咨询与施工对接。',
                'district' => '于都县',
                'address' => '赣州市于都县测试社区服务路 3 号（测试定位）',
                'service_audience' => Enterprise::AUDIENCE_C,
                'source' => Enterprise::SOURCE_SELF,
                'settle_in_park' => false,
                'map_enabled' => true,
                'latitude' => 25.9530000,
                'longitude' => 115.4165000,
            ],
            [
                'name' => '赣南艾草生物科技有限公司',
                'contact_name' => '张总',
                'contact_phone' => '13800002006',
                'major' => '健康产品生产制造（源头工厂）',
                'minors' => ['中成药、中药饮片（艾草、中药材加工）', '药食同源食品加工'],
                'business_intro' => '以艾草深加工为核心，生产中药饮片、药食同源食品及相关健康产品。',
                'district' => '瑞金市',
                'address' => '赣州市瑞金市测试生物产业园 3 号厂房（测试定位）',
                'service_audience' => Enterprise::AUDIENCE_B,
                'source' => Enterprise::SOURCE_COMMUNITY,
                'settle_in_park' => true,
                'map_enabled' => true,
                'latitude' => 25.8864000,
                'longitude' => 116.0282000,
            ],
            [
                'name' => '健安医疗器械制造厂',
                'contact_name' => '赵工',
                'contact_phone' => '13800002007',
                'major' => '健康产品生产制造（源头工厂）',
                'minors' => ['医疗器械（一类、二类）', '健康家居、康养器械'],
                'business_intro' => '生产一类、二类医疗器械及康养辅助器械，支持 OEM 与渠道合作。',
                'district' => '龙南市',
                'address' => '赣州市龙南市测试工业大道 58 号（测试定位）',
                'service_audience' => Enterprise::AUDIENCE_B,
                'source' => Enterprise::SOURCE_COMMUNITY,
                'settle_in_park' => false,
                'map_enabled' => true,
                'latitude' => 24.9120000,
                'longitude' => 114.7910000,
            ],
            [
                'name' => '益康医药流通有限公司',
                'contact_name' => '孙经理',
                'contact_phone' => '13800002008',
                'major' => '健康商贸流通（渠道、经销、供应链）',
                'minors' => ['医药流通、药品配送', '医药/医疗冷链物流'],
                'business_intro' => '覆盖赣南区域的医药配送与冷链物流服务，服务医院、药店与连锁终端。',
                'district' => '宁都县',
                'address' => '赣州市宁都县测试物流园 A 区（测试定位）',
                'service_audience' => Enterprise::AUDIENCE_B,
                'source' => Enterprise::SOURCE_SELF,
                'settle_in_park' => false,
                'map_enabled' => true,
                'latitude' => 26.4710000,
                'longitude' => 116.0105000,
            ],
            [
                'name' => '大健康优选商贸',
                'contact_name' => '周经理',
                'contact_phone' => '13800002009',
                'major' => '健康商贸流通（渠道、经销、供应链）',
                'minors' => ['大健康产品批发零售', '线上健康电商'],
                'business_intro' => '大健康产品批发零售与线上电商渠道运营，对接工厂与终端门店。',
                'district' => '兴国县',
                'address' => '赣州市兴国县测试商贸中心 2 栋（测试定位）',
                'service_audience' => Enterprise::AUDIENCE_BC,
                'source' => Enterprise::SOURCE_COMMUNITY,
                'settle_in_park' => true,
                'map_enabled' => true,
                'latitude' => 26.3386000,
                'longitude' => 115.3644000,
            ],
            [
                'name' => '智健产业咨询事务所',
                'contact_name' => '吴顾问',
                'contact_phone' => '13800002010',
                'major' => '健康配套产业（产业服务，赋能企业）',
                'minors' => ['健康产业咨询策划', '知识产权、政策申报服务'],
                'business_intro' => '为健康企业提供产业策划、政策申报与知识产权辅导服务。',
                'district' => '会昌县',
                'address' => '赣州市会昌县测试产业服务楼 1201（测试定位）',
                'service_audience' => Enterprise::AUDIENCE_B,
                'source' => Enterprise::SOURCE_COMMUNITY,
                'settle_in_park' => true,
                'map_enabled' => true,
                'latitude' => 25.6015000,
                'longitude' => 115.7872000,
            ],
            [
                'name' => '云康数字化医疗科技',
                'contact_name' => '郑总',
                'contact_phone' => '13800002011',
                'major' => '健康配套产业（产业服务，赋能企业）',
                'minors' => ['数字化医疗软件、健康系统开发', '医学检验检测'],
                'business_intro' => '研发健康管理系统与数字化医疗软件，并联合开展检验检测信息化服务。',
                'district' => '石城县',
                'address' => '赣州市石城县测试软件园 5 栋（测试定位）',
                'service_audience' => Enterprise::AUDIENCE_B,
                'source' => Enterprise::SOURCE_SELF,
                'settle_in_park' => false,
                'map_enabled' => true,
                'latitude' => 26.3274000,
                'longitude' => 116.3435000,
            ],
            [
                'name' => '赣南健康科普协会',
                'contact_name' => '何秘书长',
                'contact_phone' => '13800002012',
                'major' => '健康科普与公益社会组织',
                'minors' => ['行业协会、专委会', '健康科普传播机构'],
                'business_intro' => '组织健康科普活动与行业交流，推动健康知识进企业、进社区。',
                'district' => '寻乌县',
                'address' => '赣州市寻乌县测试公益路 9 号（测试定位）',
                'service_audience' => Enterprise::AUDIENCE_BC,
                'source' => Enterprise::SOURCE_COMMUNITY,
                'settle_in_park' => false,
                'map_enabled' => true,
                'latitude' => 24.9642000,
                'longitude' => 115.6526000,
            ],
            [
                'name' => '上犹山水疗养中心',
                'contact_name' => '黄主任',
                'contact_phone' => '13800002013',
                'major' => '康养养老服务',
                'minors' => ['日间照料中心', '社区居家养老服务'],
                'business_intro' => '依托山水环境开展短期疗养与日间照料，服务周边居民与康养游客（测试数据）。',
                'district' => '上犹县',
                'address' => '赣州市上犹县测试疗养路 21 号（测试定位）',
                'service_audience' => Enterprise::AUDIENCE_C,
                'source' => Enterprise::SOURCE_COMMUNITY,
                'settle_in_park' => false,
                'map_enabled' => true,
                'latitude' => 25.7951000,
                'longitude' => 114.5526000,
            ],
            [
                'name' => '大余本草加工坊',
                'contact_name' => '谢厂长',
                'contact_phone' => '13800002014',
                'major' => '健康产品生产制造（源头工厂）',
                'minors' => ['中成药、中药饮片（艾草、中药材加工）'],
                'business_intro' => '开展中药材粗加工与饮片试制，配套本地种植户收购（测试数据）。',
                'district' => '大余县',
                'address' => '赣州市大余县测试本草园 8 号（测试定位）',
                'service_audience' => Enterprise::AUDIENCE_B,
                'source' => Enterprise::SOURCE_SELF,
                'settle_in_park' => false,
                'map_enabled' => true,
                'latitude' => 25.4030000,
                'longitude' => 114.3635000,
            ],
            [
                'name' => '安远绿色食品贸易',
                'contact_name' => '罗经理',
                'contact_phone' => '13800002015',
                'major' => '健康商贸流通（渠道、经销、供应链）',
                'minors' => ['大健康产品批发零售'],
                'business_intro' => '批发零售药食同源与绿色农产品，服务县域商超与电商渠道（测试数据）。',
                'district' => '安远县',
                'address' => '赣州市安远县测试商贸街 66 号（测试定位）',
                'service_audience' => Enterprise::AUDIENCE_BC,
                'source' => Enterprise::SOURCE_COMMUNITY,
                'settle_in_park' => false,
                'map_enabled' => true,
                'latitude' => 25.1378000,
                'longitude' => 115.3950000,
            ],
            [
                'name' => '崇义森林康养驿站',
                'contact_name' => '邓站长',
                'contact_phone' => '13800002016',
                'major' => '健康科普与公益社会组织',
                'minors' => ['健康科普传播机构'],
                'business_intro' => '开展森林康养科普与公益体验活动，服务本地与周边游客（测试数据）。',
                'district' => '崇义县',
                'address' => '赣州市崇义县测试驿站路 1 号（测试定位）',
                'service_audience' => Enterprise::AUDIENCE_C,
                'source' => Enterprise::SOURCE_COMMUNITY,
                'settle_in_park' => false,
                'map_enabled' => true,
                'latitude' => 25.6824000,
                'longitude' => 114.3095000,
            ],
            [
                'name' => '定南智慧体检站',
                'contact_name' => '钟医生',
                'contact_phone' => '13800002017',
                'major' => '医疗健康服务（线下实体服务类）',
                'minors' => ['慢病管理服务机构'],
                'business_intro' => '提供基础体检与慢病筛查服务，对接县域医疗资源（测试数据）。',
                'district' => '定南县',
                'address' => '赣州市定南县测试体检路 18 号（测试定位）',
                'service_audience' => Enterprise::AUDIENCE_C,
                'source' => Enterprise::SOURCE_SELF,
                'settle_in_park' => false,
                'map_enabled' => true,
                'latitude' => 24.7850000,
                'longitude' => 115.0290000,
            ],
            [
                'name' => '全南康复辅具店',
                'contact_name' => '赖店长',
                'contact_phone' => '13800002018',
                'major' => '健康配套产业（产业服务，赋能企业）',
                'minors' => ['健康产业咨询策划'],
                'business_intro' => '销售与租赁康复辅具，并提供选型咨询（测试数据）。',
                'district' => '全南县',
                'address' => '赣州市全南县测试辅具街 27 号（测试定位）',
                'service_audience' => Enterprise::AUDIENCE_BC,
                'source' => Enterprise::SOURCE_SELF,
                'settle_in_park' => false,
                'map_enabled' => true,
                'latitude' => 24.7432000,
                'longitude' => 114.5314000,
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

            $district = $sample['district']
                ?? GanzhouDistrict::resolve(
                    $sample['address'],
                    $sample['latitude'] ?? null,
                    $sample['longitude'] ?? null,
                );

            $enterprise = Enterprise::query()->updateOrCreate(
                ['name' => $sample['name']],
                [
                    'contact_name' => $sample['contact_name'],
                    'contact_phone' => $sample['contact_phone'],
                    'primary_category_id' => $major->id,
                    'business_intro' => $sample['business_intro'],
                    'address' => $sample['address'],
                    'district' => $district,
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
