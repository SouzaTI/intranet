<?php
declare(strict_types=1);

final class CarimbadorService
{
    public function __construct(private string $url = 'http://127.0.0.1:5000/api/carimbar') {}

    public function carimbar(string $entrada, string $saida, string $nome, string $setor, string $email, int $ordem, string $ip): array
    {
        $payload = json_encode([
            'caminho_entrada' => $entrada,
            'caminho_saida' => $saida,
            'assinante_nome' => $nome,
            'assinante_setor' => $setor,
            'assinante_email' => $email,
            'ordem' => $ordem,
            'ip_origem' => $ip,
            'data_hora' => date('Y-m-d H:i:s'),
        ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        $ch = curl_init($this->url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_CONNECTTIMEOUT => 3,
            CURLOPT_TIMEOUT => 60,
        ]);
        $body = curl_exec($ch);
        $erro = curl_error($ch);
        $http = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($body === false || $http !== 200) throw new RuntimeException($erro ?: "Carimbador retornou HTTP {$http}.");
        $resposta = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        if (empty($resposta['ok']) || !is_file($saida)) throw new RuntimeException($resposta['msg'] ?? 'Carimbador não gerou o arquivo.');
        return $resposta;
    }
}
