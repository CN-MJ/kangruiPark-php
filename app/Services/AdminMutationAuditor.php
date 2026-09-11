<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\Appointment;
use App\Models\BuildingHouse;
use App\Models\InvestmentIntent;
use App\Models\News;
use App\Models\ParkingRecord;
use App\Models\Policy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * 解析园区管理接口变更，生成「操作说明 + 修改前 + 修改后」。
 */
class AdminMutationAuditor
{
    /** @var array<int, array<string, mixed>> */
    private array $rules;

    public function __construct()
    {
        $this->rules = [
            [
                'match' => '#^api/park/houses$#',
                'methods' => ['POST'],
                'op' => 'create',
                'resource' => 'house',
                'label' => '房源',
                'model' => BuildingHouse::class,
            ],
            [
                'match' => '#^api/park/houses/(\d+)$#',
                'methods' => ['PUT'],
                'op' => 'update',
                'resource' => 'house',
                'label' => '房源',
                'model' => BuildingHouse::class,
            ],
            [
                'match' => '#^api/park/houses/(\d+)$#',
                'methods' => ['DELETE'],
                'op' => 'delete',
                'resource' => 'house',
                'label' => '房源',
                'model' => BuildingHouse::class,
            ],
            [
                'match' => '#^api/park/policies$#',
                'methods' => ['POST'],
                'op' => 'create',
                'resource' => 'policy',
                'label' => '政策',
                'model' => Policy::class,
            ],
            [
                'match' => '#^api/park/policies/(\d+)$#',
                'methods' => ['PUT'],
                'op' => 'update',
                'resource' => 'policy',
                'label' => '政策',
                'model' => Policy::class,
            ],
            [
                'match' => '#^api/park/policies/(\d+)$#',
                'methods' => ['DELETE'],
                'op' => 'delete',
                'resource' => 'policy',
                'label' => '政策',
                'model' => Policy::class,
            ],
            [
                'match' => '#^api/park/activities$#',
                'methods' => ['POST'],
                'op' => 'create',
                'resource' => 'activity',
                'label' => '活动',
                'model' => Activity::class,
            ],
            [
                'match' => '#^api/park/activities/(\d+)$#',
                'methods' => ['PUT'],
                'op' => 'update',
                'resource' => 'activity',
                'label' => '活动',
                'model' => Activity::class,
            ],
            [
                'match' => '#^api/park/activities/(\d+)$#',
                'methods' => ['DELETE'],
                'op' => 'delete',
                'resource' => 'activity',
                'label' => '活动',
                'model' => Activity::class,
            ],
            [
                'match' => '#^api/park/news$#',
                'methods' => ['POST'],
                'op' => 'create',
                'resource' => 'news',
                'label' => '新闻/公告',
                'model' => News::class,
            ],
            [
                'match' => '#^api/park/news/(\d+)$#',
                'methods' => ['PUT'],
                'op' => 'update',
                'resource' => 'news',
                'label' => '新闻/公告',
                'model' => News::class,
            ],
            [
                'match' => '#^api/park/news/(\d+)$#',
                'methods' => ['DELETE'],
                'op' => 'delete',
                'resource' => 'news',
                'label' => '新闻/公告',
                'model' => News::class,
            ],
            [
                'match' => '#^api/park/news/(\d+)/top$#',
                'methods' => ['PATCH'],
                'op' => 'update',
                'resource' => 'news',
                'label' => '新闻置顶',
                'model' => News::class,
                'title' => '切换新闻置顶状态',
            ],
            [
                'match' => '#^api/park/investments/(\d+)/status$#',
                'methods' => ['PATCH'],
                'op' => 'update',
                'resource' => 'investment',
                'label' => '招商意向',
                'model' => InvestmentIntent::class,
                'title' => '更新招商意向状态',
            ],
            [
                'match' => '#^api/park/appointments/(\d+)/cancel$#',
                'methods' => ['PATCH'],
                'op' => 'update',
                'resource' => 'appointment',
                'label' => '预约',
                'model' => Appointment::class,
                'title' => '取消预约',
            ],
            [
                'match' => '#^api/park/appointments/(\d+)/confirm$#',
                'methods' => ['PATCH'],
                'op' => 'update',
                'resource' => 'appointment',
                'label' => '预约',
                'model' => Appointment::class,
                'title' => '确认预约',
            ],
            [
                'match' => '#^api/park/appointments/(\d+)/reject$#',
                'methods' => ['PATCH'],
                'op' => 'update',
                'resource' => 'appointment',
                'label' => '预约',
                'model' => Appointment::class,
                'title' => '拒绝预约',
            ],
            [
                'match' => '#^api/park/parking/(\d+)/exit$#',
                'methods' => ['PATCH'],
                'op' => 'update',
                'resource' => 'parking',
                'label' => '停车记录',
                'model' => ParkingRecord::class,
                'title' => '车辆出场',
            ],
            [
                'match' => '#^api/park/users/(\d+)/approve-merchant$#',
                'methods' => ['POST'],
                'op' => 'update',
                'resource' => 'user',
                'label' => '入驻审核',
                'model' => \App\Models\User::class,
                'title' => '通过入驻申请',
            ],
            [
                'match' => '#^api/park/users/(\d+)/reject-merchant$#',
                'methods' => ['POST'],
                'op' => 'update',
                'resource' => 'user',
                'label' => '入驻审核',
                'model' => \App\Models\User::class,
                'title' => '拒绝入驻申请',
            ],
            [
                'match' => '#^api/park/change-password$#',
                'methods' => ['POST'],
                'op' => 'update',
                'resource' => 'account',
                'label' => '账号',
                'title' => '修改登录密码',
                'sensitive' => true,
            ],
        ];
    }

    public function shouldSkip(Request $request): bool
    {
        return $request->is('api/park/logout')
            || $request->is('api/park/upload');
    }

    /**
     * @return array{rule: ?array, id: ?string, before: ?array}|null
     */
    public function captureBefore(Request $request): ?array
    {
        $resolved = $this->resolve($request);
        if (! $resolved) {
            return null;
        }

        ['rule' => $rule, 'id' => $id] = $resolved;
        $before = null;

        if (! empty($rule['sensitive'])) {
            return ['rule' => $rule, 'id' => $id, 'before' => null];
        }

        if (in_array($rule['op'], ['update', 'delete'], true) && $id && ! empty($rule['model'])) {
            $before = $this->snapshotById($rule['model'], $id);
        }

        return ['rule' => $rule, 'id' => $id, 'before' => $before];
    }

    /**
     * @param  array{rule: array, id: ?string, before: ?array}  $captured
     * @return array{action: string, target_type: string, target_id: ?string, payload: array}|null
     */
    public function buildLog(Request $request, Response $response, array $captured): ?array
    {
        $rule = $captured['rule'];
        $id = $captured['id'];
        $before = $captured['before'];
        $op = $rule['op'];
        $label = $rule['label'];
        $opText = match ($op) {
            'create' => '新增',
            'delete' => '删除',
            default => '修改',
        };

        if (! empty($rule['sensitive'])) {
            return [
                'action' => $rule['resource'].'.'.$op,
                'target_type' => $rule['resource'],
                'target_id' => $id,
                'payload' => [
                    'title' => $rule['title'] ?? ($opText.$label),
                    'operation' => $op,
                    'resource' => $rule['resource'],
                    'resource_label' => $label,
                    'before' => null,
                    'after' => null,
                    'changes' => [],
                    'note' => '敏感操作，不记录密码内容',
                ],
            ];
        }

        $after = null;
        if ($op === 'create') {
            $after = $this->snapshotFromResponse($response, $rule['model'] ?? null);
            if ($after && isset($after['id'])) {
                $id = (string) $after['id'];
            }
        } elseif ($op === 'update' && $id && ! empty($rule['model'])) {
            $after = $this->snapshotById($rule['model'], $id);
            // 若接口直接返回实体，优先用响应（避免二次查询不一致）
            $fromResponse = $this->decodeResponseData($response);
            if (is_array($fromResponse) && isset($fromResponse['id'])) {
                $after = $this->normalizeArray($fromResponse);
            }
        } elseif ($op === 'delete') {
            $after = null;
        }

        $changes = $this->diff($before, $after);
        $title = $rule['title'] ?? ($opText.$label.($id ? " #{$id}" : ''));

        return [
            'action' => $rule['resource'].'.'.$op,
            'target_type' => $rule['resource'],
            'target_id' => $id,
            'payload' => [
                'title' => $title,
                'operation' => $op,
                'resource' => $rule['resource'],
                'resource_label' => $label,
                'path' => '/'.$request->path(),
                'method' => strtoupper($request->method()),
                'before' => $before,
                'after' => $after,
                'changes' => $changes,
            ],
        ];
    }

    /**
     * 未命中规则时的兜底：至少记下请求体（无法提供准确改前数据）。
     *
     * @return array{action: string, target_type: string, target_id: ?string, payload: array}
     */
    public function buildFallback(Request $request, Response $response): array
    {
        $method = strtoupper($request->method());
        $op = match ($method) {
            'POST' => 'create',
            'DELETE' => 'delete',
            default => 'update',
        };
        $id = $request->route('id') ? (string) $request->route('id') : null;
        $body = $request->except([
            'password', 'old_password', 'new_password', 'new_password_confirmation', 'sms_code',
        ]);

        return [
            'action' => 'park.'.$op,
            'target_type' => 'park_api',
            'target_id' => $id,
            'payload' => [
                'title' => ($op === 'create' ? '新增' : ($op === 'delete' ? '删除' : '修改')).'数据',
                'operation' => $op,
                'resource' => 'park_api',
                'resource_label' => '管理接口',
                'path' => '/'.$request->path(),
                'method' => $method,
                'before' => null,
                'after' => $op === 'delete' ? null : $this->normalizeArray(is_array($body) ? $body : []),
                'changes' => [],
                'note' => '未匹配到实体规则，仅记录请求内容',
            ],
        ];
    }

    /**
     * @return array{rule: array, id: ?string}|null
     */
    private function resolve(Request $request): ?array
    {
        $path = $request->path();
        $method = strtoupper($request->method());

        foreach ($this->rules as $rule) {
            if (! in_array($method, $rule['methods'], true)) {
                continue;
            }
            if (! preg_match($rule['match'], $path, $m)) {
                continue;
            }

            return [
                'rule' => $rule,
                'id' => isset($m[1]) ? (string) $m[1] : null,
            ];
        }

        return null;
    }

    private function snapshotById(string $modelClass, string $id): ?array
    {
        /** @var class-string<Model> $modelClass */
        $model = $modelClass::query()->find($id);
        if (! $model) {
            return null;
        }

        return $this->normalizeArray($model->toArray());
    }

    private function snapshotFromResponse(Response $response, ?string $modelClass): ?array
    {
        $data = $this->decodeResponseData($response);
        if (is_array($data) && isset($data['id'])) {
            return $this->normalizeArray($data);
        }

        if ($modelClass && is_array($data) && isset($data['id'])) {
            return $this->snapshotById($modelClass, (string) $data['id']);
        }

        return is_array($data) ? $this->normalizeArray($data) : null;
    }

    private function decodeResponseData(Response $response): mixed
    {
        $json = json_decode($response->getContent() ?: '', true);
        if (! is_array($json)) {
            return null;
        }

        return $json['data'] ?? null;
    }

    /**
     * @param  array<string, mixed>|null  $before
     * @param  array<string, mixed>|null  $after
     * @return array<string, array{from: mixed, to: mixed}>
     */
    private function diff(?array $before, ?array $after): array
    {
        $before = $before ?? [];
        $after = $after ?? [];
        $keys = array_unique(array_merge(array_keys($before), array_keys($after)));
        $ignore = ['updated_at', 'created_at'];
        $changes = [];

        foreach ($keys as $key) {
            if (in_array($key, $ignore, true)) {
                continue;
            }
            $from = $before[$key] ?? null;
            $to = $after[$key] ?? null;
            if ($this->sameValue($from, $to)) {
                continue;
            }
            $changes[$key] = ['from' => $from, 'to' => $to];
        }

        return $changes;
    }

    private function sameValue(mixed $a, mixed $b): bool
    {
        return json_encode($a, JSON_UNESCAPED_UNICODE) === json_encode($b, JSON_UNESCAPED_UNICODE);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizeArray(array $data): array
    {
        $sensitive = ['password', 'old_password', 'new_password', 'new_password_confirmation', 'sms_code', 'token'];
        foreach ($sensitive as $key) {
            unset($data[$key]);
        }

        foreach ($data as $key => $value) {
            if (is_string($value) && mb_strlen($value) > 800) {
                $data[$key] = mb_substr($value, 0, 800).'…';
            } elseif ($value instanceof \DateTimeInterface) {
                $data[$key] = $value->format('Y-m-d H:i:s');
            } elseif (is_array($value)) {
                $data[$key] = $this->normalizeArray($value);
            }
        }

        return $data;
    }
}
