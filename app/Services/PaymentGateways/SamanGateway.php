<?php

namespace App\Services\PaymentGateways;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use App\Models\GatewayConfig;

class SamanGateway
{
    protected HttpClient $http;
    protected string $merchantId;
    protected array $cfg;

    public function __construct(?HttpClient $http = null)
    {
        $this->http = $http ?: new HttpClient([
            'http_errors' => false,
            'timeout'     => 20,
        ]);

        $this->merchantId = GatewayConfig::getConfig('saman_merchant_id');

        $this->cfg = [
            'redirect_base'   => GatewayConfig::getConfig('saman_redirect_base') ?? 'https://sep.shaparak.ir/OnlinePG/SendToken',
            'init_endpoint'   => GatewayConfig::getConfig('saman_init_endpoint') ?? 'https://sep.shaparak.ir/OnlinePG/OnlinePG',
            'verify_endpoint' => GatewayConfig::getConfig('saman_verify_endpoint') ?? 'https://sep.shaparak.ir/OnlinePG/VerifyToken',
            'timeout'         => (int) (GatewayConfig::getConfig('saman_timeout') ?? 20),
            'sandbox'         => (bool) (GatewayConfig::getConfig('saman_sandbox') ?? false),
        ];

        if (empty($this->merchantId)) {
            throw new RuntimeException('Saman merchant_id is not configured.');
        }
    }

    /**
     * Initialize payment and get a redirect URL.
     */
    public function requestPayment($amount, $orderId, $callbackUrl, $extra = []): array
    {
        $data = [
            'Action'  => 'Token',
            'TerminalId'  => $this->merchantId,
            'Amount'      => $amount,
            'ResNum'      => $orderId,       // required by Saman
            'RedirectUrl' => $callbackUrl,
            'Description' => $extra['description'] ?? '',
        ];

        if (!empty($extra['mobile'])) {
            $data['CellNumber'] = $extra['mobile'];
        }
        try {
            $response = $this->http->post($this->cfg['init_endpoint'], [
                'json' => $data,
                'timeout' => $this->cfg['timeout'],
            ]);

            $body = json_decode((string)$response->getBody(), true);

            Log::info('Saman init response', ['request' => $data, 'response' => $response]);

            if (isset($body['status']) && $body['status'] == 1 && !empty($body['token'])) {
                return [
                    'token'        => $body['token'],
                    'redirect_url' => $this->buildRedirectUrl($body['token']),
                ];
            }

            $message = $body['errorDesc'] ?? 'Unknown error from gateway.';
            throw new RuntimeException("Saman init failed: {$message}");

        } catch (\GuzzleHttp\Exception\RequestException $e) {
            Log::error('Saman request error', ['exception' => $e]);
            throw new RuntimeException('Saman request failed: ' . $e->getMessage());
        }
    }

    public function verifyByToken(string $token, ?int $amount = null): array
    {
        $payload = [
            'Token'      => $token,
            'MerchantID' => $this->merchantId,
        ];
        if ($amount) $payload['Amount'] = $amount;

        $resp = $this->http->post($this->cfg['verify_endpoint'], ['json' => $payload]);
        $body = json_decode((string)$resp->getBody(), true) ?: [];

        $ok = ($body['Status'] ?? 0) == 1;

        return [
            'success'  => $ok,
            'ref_num'  => $body['RefNum']    ?? null,
            'rrn'      => $body['RRN']       ?? null,
            'card_pan' => $body['MaskedPan'] ?? null,
            'amount'   => isset($body['Amount']) ? (int)$body['Amount'] : null,
            'message'  => (string) ($body['Message'] ?? ($ok ? 'OK' : 'FAILED')),
            'raw'      => $body,
        ];
    }

    public function callback(Request $request): array
    {
        $state   = $request->input('State');
        $status  = $request->input('Status');
        $token   = $request->input('Token');
        $orderId = $request->input('ResNum');
        $raw     = $request->all();

        if (strtoupper((string)$state) !== 'OK' && (string)$status !== '1') {
            return [
                'success'  => false,
                'ref_num'  => null,
                'rrn'      => null,
                'card_pan' => null,
                'amount'   => null,
                'order_id' => $orderId,
                'token'    => $token,
                'message'  => $raw['Message'] ?? 'Payment cancelled or failed by user.',
                'raw'      => $raw,
            ];
        }

        if (empty($token)) {
            return [
                'success'  => false,
                'ref_num'  => null,
                'rrn'      => null,
                'card_pan' => null,
                'amount'   => null,
                'order_id' => $orderId,
                'token'    => null,
                'message'  => 'Missing token in callback.',
                'raw'      => $raw,
            ];
        }

        $verify = $this->verifyByToken($token);
        $verify['order_id'] = $orderId;
        $verify['token']    = $token;
        $verify['raw']      = ['callback' => $raw, 'verify' => $verify['raw']];

        return $verify;
    }

    /**
     * Verify transaction using RefNum and TerminalNumber
     * This is the new verification endpoint for Saman gateway
     */
    public function verifyTransaction(string $refNum, string $terminalNumber): array
    {
        $payload = [
            'RefNum' => $refNum,
            'TerminalNumber' => (int)$terminalNumber,
        ];

        try {
            $response = $this->http->post('https://sep.shaparak.ir/verifyTxnRandomSessionkey/ipg/VerifyTransaction', [
                'json' => $payload,
                'timeout' => $this->cfg['timeout'],
            ]);

            $body = json_decode((string)$response->getBody(), true) ?: [];

            $success = isset($body['Success']) && $body['Success'] === true && 
                      isset($body['ResultCode']) && $body['ResultCode'] == 0;

            $transactionDetail = $body['TransactionDetail'] ?? [];

            return [
                'success' => $success,
                'result_code' => $body['ResultCode'] ?? null,
                'result_description' => $body['ResultDescription'] ?? null,
                'transaction_detail' => $transactionDetail,
                'rrn' => $transactionDetail['RRN'] ?? null,
                'ref_num' => $transactionDetail['RefNum'] ?? null,
                'masked_pan' => $transactionDetail['MaskedPan'] ?? null,
                'terminal_number' => $transactionDetail['TerminalNumber'] ?? null,
                'original_amount' => $transactionDetail['OrginalAmount'] ?? null,
                'affective_amount' => $transactionDetail['AffectiveAmount'] ?? null,
                'trace_date' => $transactionDetail['StraceDate'] ?? null,
                'trace_no' => $transactionDetail['StraceNo'] ?? null,
                'message' => $body['ResultDescription'] ?? ($success ? 'عملیات با موفقیت انجام شد' : 'Verification failed'),
                'raw' => $body,
            ];
        } catch (\Exception $e) {
            Log::error('Saman Gateway Error : ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'ref_num' => $refNum,
                'terminal_number' => $terminalNumber,
            ]);
            
            return [
                'success' => false,
                'message' => 'Error verifying transaction: ' . $e->getMessage(),
                'raw' => [],
            ];
        }
    }

    public function buildRedirectUrl(string $token): string
    {
        $base = rtrim($this->cfg['redirect_base'], '/');
        $query = http_build_query(['token' => $token]);
        return strpos($base, 'SendToken') !== false
            ? $base . '?' . $query
            : $base . '/?' . $query;
    }
}
