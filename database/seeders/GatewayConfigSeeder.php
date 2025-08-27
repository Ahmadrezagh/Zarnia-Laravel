<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GatewayConfig;

class GatewayConfigSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $configs = [
            // ================= Saman =================
            [
                'key'        => 'saman_merchant_id',
                'value'      => '14631093',
                'gateway_id' => 4,
            ],
            [
                'key'        => 'saman_redirect_base',
                'value'      => 'https://sep.shaparak.ir/OnlinePG/SendToken',
                'gateway_id' => 4,
            ],
            [
                'key'        => 'saman_init_endpoint',
                'value'      => 'https://sep.shaparak.ir/OnlinePG/OnlinePG',
                'gateway_id' => 4,
            ],
            [
                'key'        => 'saman_verify_endpoint',
                'value'      => 'https://sep.shaparak.ir/OnlinePG/VerifyToken',
                'gateway_id' => 4,
            ],
            [
                'key'        => 'saman_timeout',
                'value'      => '20',
                'gateway_id' => 4,
            ],
            [
                'key'        => 'saman_sandbox',
                'value'      => '0',
                'gateway_id' => 4,
            ],

            // ================= SnappPay =================
            [
                'key'        => 'SNAPPPAY_USERNAME',
                'value'      => 'your-username',
                'gateway_id' => 3,
            ],
            [
                'key'        => 'SNAPPPAY_PASSWORD',
                'value'      => 'your-password',
                'gateway_id' => 3,
            ],
            [
                'key'        => 'SNAPPPAY_CLIENT_ID',
                'value'      => 'your-client-id',
                'gateway_id' => 3,
            ],
            [
                'key'        => 'SNAPPPAY_CLIENT_SECRET',
                'value'      => 'your-client-secret',
                'gateway_id' => 3,
            ],
            [
                'key'        => 'SNAPPPAY_BASE_URL',
                'value'      => 'https://example.snappay.ir',
                'gateway_id' => 3,
            ],
        ];

        foreach ($configs as $config) {
            GatewayConfig::query()->updateOrCreate(
                [
                    'key'        => $config['key'],
                    'gateway_id' => $config['gateway_id'],
                ],
                [
                    'value'      => $config['value'],
                ]
            );
        }
    }
}
