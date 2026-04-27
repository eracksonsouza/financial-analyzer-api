<?php

declare(strict_types=1);

namespace App\Services;

use RuntimeException;

class ClaudeService
{
    private const API_URL = 'https://api.anthropic.com/v1/messages';
    private const MODEL   = 'claude-sonnet-4-20250514';

    public function __construct(private readonly string $apiKey) {}

    public function analyze(float $income, array $expenses, array $metrics): array
    {
        $expLines = '';
        $labels = [
            'moradia' => 'Moradia', 'alimentacao' => 'Alimentação',
            'transporte' => 'Transporte', 'saude' => 'Saúde',
            'lazer' => 'Lazer', 'educacao' => 'Educação',
            'dividas' => 'Dívidas', 'outros' => 'Outros',
        ];

        foreach ($expenses as $key => $val) {
            if ($val > 0) {
                $pct = $metrics['expense_ratios'][$key] ?? 0;
                $expLines .= "- {$labels[$key]}: R$ {$val} ({$pct}% da renda)\n";
            }
        }

        $rule = $metrics['rule_50_30_20'];
        $prompt = <<<PROMPT
Você é um consultor financeiro pessoal direto e objetivo.

Dados do usuário:
- Renda mensal líquida: R$ {$income}
- Total de despesas: R$ {$metrics['total_expenses']}
- Saldo: R$ {$metrics['balance']}
- Taxa de poupança: {$metrics['savings_rate']}%
- Score de saúde financeira: {$metrics['health_score']}/100

Distribuição pela regra 50/30/20:
- Necessidades: {$rule['needs_pct']}% (ideal ≤50%)
- Desejos: {$rule['wants_pct']}% (ideal ≤30%)
- Dívidas: {$rule['debt_pct']}%

Despesas por categoria:
{$expLines}

Responda APENAS com JSON válido, sem markdown, sem explicação:
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

        $payload = json_encode([
            'model'      => self::MODEL,
            'max_tokens' => 800,
            'messages'   => [['role' => 'user', 'content' => $prompt]],
        ]);

        $ch = curl_init(self::API_URL);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: 2023-06-01',
            ],
            CURLOPT_TIMEOUT        => 30,
        ]);

        $rawResponse = curl_exec($ch);
        $httpCode    = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($rawResponse === false || $httpCode !== 200) {
            throw new RuntimeException("Claude API error: HTTP {$httpCode}");
        }

        $data    = json_decode($rawResponse, true);
        $content = $data['content'][0]['text'] ?? '';
        $result  = json_decode(trim($content), true);

        if (!$result || !isset($result['diagnostico'], $result['melhorias'])) {
            throw new RuntimeException('Resposta inválida da IA.');
        }

        return $result;
    }
}
