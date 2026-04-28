<?php

declare(strict_types=1);

namespace App\Services;

use App\Domain\DTO\Expenses;
use App\Domain\Enums\ExpenseCategory;
use App\Domain\Exceptions\AIServiceException;
use JsonException;
use Psr\Log\LoggerInterface;

final class AIService
{
    private const TIMEOUT_SECONDS = 30;
    private const MAX_LOGGED_BODY_LENGTH = 1000;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $apiUrl,
        private readonly string $model,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * @param array<string, mixed> $metrics
     * @return array<string, mixed>
     * @throws AIServiceException when the upstream call fails or returns malformed data
     */
    public function analyze(float $income, Expenses $expenses, array $metrics): array
    {
        $payload = $this->buildPayload($income, $expenses, $metrics);
        $rawResponse = $this->callApi($payload);

        try {
            $data = json_decode($rawResponse, true, flags: JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->logger->error('AI returned non-JSON envelope', ['error' => $e->getMessage()]);
            throw new AIServiceException('Resposta da IA não é JSON válido.', previous: $e);
        }

        $content = $data['choices'][0]['message']['content'] ?? '';
        $result = json_decode(trim((string) $content), true);

        if (!is_array($result) || !isset($result['diagnostico'], $result['melhorias'])) {
            $this->logger->error('AI returned invalid content', [
                'content' => substr((string) $content, 0, self::MAX_LOGGED_BODY_LENGTH),
            ]);
            throw new AIServiceException('Resposta da IA com formato inesperado.');
        }

        return $result;
    }

    /**
     * @param array<string, mixed> $metrics
     */
    private function buildPayload(float $income, Expenses $expenses, array $metrics): string
    {
        $expLines = '';
        foreach (ExpenseCategory::cases() as $cat) {
            $val = $expenses->get($cat);
            if ($val > 0) {
                $pct = $metrics['expense_ratios'][$cat->value] ?? 0;
                $formatted = number_format($val, 2, ',', '.');
                $expLines .= "- {$cat->label()}: R$ {$formatted} ({$pct}% da renda)\n";
            }
        }

        $rule        = $metrics['rule_50_30_20'];
        $incomeFmt   = number_format($income, 2, ',', '.');
        $totalExpFmt = number_format((float) $metrics['total_expenses'], 2, ',', '.');
        $balanceFmt  = number_format((float) $metrics['balance'], 2, ',', '.');

        $prompt = <<<PROMPT
Você é um consultor financeiro pessoal direto e objetivo.

Dados do usuário:
- Renda mensal líquida: R$ {$incomeFmt}
- Total de despesas: R$ {$totalExpFmt}
- Saldo: R$ {$balanceFmt}
- Taxa de poupança: {$metrics['savings_rate']}%
- Score de saúde financeira: {$metrics['health_score']}/100

Distribuição pela regra 50/30/20:
- Necessidades: {$rule['needs_pct']}% (ideal ≤50%)
- Desejos: {$rule['wants_pct']}% (ideal ≤30%)
- Dívidas: {$rule['debt_pct']}%

Despesas por categoria:
{$expLines}

Responda APENAS com JSON válido no seguinte formato:
{
  "diagnostico": "2 a 3 frases diretas sobre a saúde financeira atual usando os números reais",
  "melhorias": [
    "melhoria 1 específica e acionável com valor ou percentual concreto",
    "melhoria 2 específica e acionável com valor ou percentual concreto",
    "melhoria 3 específica e acionável com valor ou percentual concreto"
  ],
  "score_label": "Crítica|Preocupante|Regular|Boa|Excelente"
}
PROMPT;

        return json_encode([
            'model'           => $this->model,
            'max_tokens'      => 800,
            'response_format' => ['type' => 'json_object'],
            'messages'        => [['role' => 'user', 'content' => $prompt]],
        ], JSON_THROW_ON_ERROR);
    }

    private function callApi(string $payload): string
    {
        $ch = curl_init($this->apiUrl);
        if ($ch === false) {
            throw new AIServiceException('Não foi possível inicializar o cliente HTTP.');
        }

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT        => self::TIMEOUT_SECONDS,
        ]);

        $rawResponse = curl_exec($ch);
        $httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError   = $rawResponse === false ? curl_error($ch) : null;
        curl_close($ch);

        if ($rawResponse === false) {
            $this->logger->error('AI request failed (transport)', ['error' => $curlError]);
            throw new AIServiceException('Falha de rede ao chamar a IA.');
        }

        if ($httpCode !== 200) {
            $this->logger->error('AI request returned non-200', [
                'status' => $httpCode,
                'body'   => substr((string) $rawResponse, 0, self::MAX_LOGGED_BODY_LENGTH),
            ]);
            throw new AIServiceException("API da IA retornou HTTP {$httpCode}.");
        }

        return (string) $rawResponse;
    }
}
