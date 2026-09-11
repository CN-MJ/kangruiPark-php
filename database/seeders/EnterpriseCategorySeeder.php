<?php

namespace Database\Seeders;

use App\Models\Enterprise;
use App\Models\EnterpriseCategory;
use App\Support\EnterpriseCategoryTree;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class EnterpriseCategorySeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('enterprise_categories')) {
            return;
        }

        $tree = EnterpriseCategoryTree::tree();
        $keepMajorNames = array_keys($tree);

        DB::transaction(function () use ($tree, $keepMajorNames) {
            // 无企业数据时，清掉历史「地级市」等旧大类
            if (Schema::hasTable('enterprises') && ! Enterprise::query()->exists()) {
                if (Schema::hasTable('enterprise_category')) {
                    DB::table('enterprise_category')->delete();
                }
                EnterpriseCategory::query()->delete();
            }

            // 短名升级为完整大类名，保留 id 与已关联小类
            foreach (EnterpriseCategoryTree::majorAliases() as $oldName => $newName) {
                EnterpriseCategory::query()
                    ->whereNull('parent_id')
                    ->where('name', $oldName)
                    ->update(['name' => $newName, 'status' => EnterpriseCategory::STATUS_ACTIVE]);
            }

            $majorOrder = 0;
            foreach ($tree as $majorName => $minors) {
                $majorOrder++;
                $major = EnterpriseCategory::query()->updateOrCreate(
                    ['parent_id' => null, 'name' => $majorName],
                    [
                        'sort_order' => $majorOrder,
                        'status' => EnterpriseCategory::STATUS_ACTIVE,
                    ]
                );

                foreach ($minors as $i => $minorName) {
                    EnterpriseCategory::query()->updateOrCreate(
                        ['parent_id' => $major->id, 'name' => $minorName],
                        [
                            'sort_order' => $i + 1,
                            'status' => EnterpriseCategory::STATUS_ACTIVE,
                        ]
                    );
                }
            }

            EnterpriseCategory::query()
                ->whereNull('parent_id')
                ->whereNotIn('name', $keepMajorNames)
                ->update(['status' => EnterpriseCategory::STATUS_INACTIVE]);
        });
    }
}
