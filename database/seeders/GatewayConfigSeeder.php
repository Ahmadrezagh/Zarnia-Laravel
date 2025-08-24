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
            [
                'key'        => 'saman_merchant_id',
                'value'      => '14631093', // put your merchant id here
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
                'value'      => '0', // 0 = false, 1 = true
                'gateway_id' => 4,
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
