<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Extrai a lista de materiais de um PDF/imagem usando a IA (OpenAI).
 *
 * Centraliza a lógica que antes vivia apenas no Admin\PurchaseOrderController,
 * permitindo reutilização tanto pelo Novo Pedido (admin) quanto pela Lista
 * Semanal (formulário público via token), sem duplicar código.
 */
class MaterialParserService
{
    /**
     * Analisa um arquivo enviado ($_FILES entry) e retorna materiais.
     *
     * @return array{success?:bool, materials?:array, error?:string}
     */
    public static function parseUploadedFile(array $file): array
    {
        if (empty($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['error' => 'Erro no upload do arquivo.'];
        }

        $allowedTypes = ['application/pdf', 'image/jpeg', 'image/png', 'image/webp'];
        if (!in_array($file['type'], $allowedTypes)) {
            return ['error' => 'Tipo não permitido. Use PDF, JPG, PNG ou WEBP.'];
        }

        $apiKey = Setting::get('openai_api_key', '');
        $model = Setting::get('openai_model', 'gpt-4o');
        if (empty($apiKey)) {
            return ['error' => 'Chave API OpenAI não configurada.'];
        }

        if ($file['type'] === 'application/pdf') {
            $result = self::parsePdfViaResponsesApi($file['tmp_name'], $file['name'], $apiKey, $model);
            return $result ?? ['error' => 'Falha ao processar PDF. Tente novamente.'];
        }

        // Imagens: enviar como base64 para o Chat Completions
        $content = base64_encode(file_get_contents($file['tmp_name']));
        $mediaType = $file['type'];
        $messages = [
            ['role' => 'system', 'content' => 'Você é um assistente que analisa documentos de listagem de materiais de construção. Extraia todos os materiais listados e retorne APENAS um JSON array. Cada item deve ter: name (nome do material), specification (tipo/especificação como "mat. Hidraulica", "mat. Civil", "madeira", etc), classification (medida como "100mm", "3/4", "50x40", etc), unit (unidade de medida como "unid", "mts", "m²", "kg", etc), quantity (quantidade numérica, use 1 se não especificado). Se não conseguir identificar algum campo, use string vazia. Retorne APENAS o JSON, sem markdown, sem explicação.'],
            ['role' => 'user', 'content' => [
                ['type' => 'text', 'text' => 'Analise este documento e extraia a lista de materiais com quantidades:'],
                ['type' => 'image_url', 'image_url' => ['url' => "data:{$mediaType};base64,{$content}"]],
            ]],
        ];

        $ch = curl_init('https://api.openai.com/v1/chat/completions');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'model' => $model,
                'messages' => $messages,
                'max_tokens' => 4000,
                'temperature' => 0.1,
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 90,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $err = json_decode($response, true);
            return ['error' => 'Erro na API OpenAI: ' . ($err['error']['message'] ?? "HTTP {$httpCode}")];
        }

        $result = json_decode($response, true);
        $text = $result['choices'][0]['message']['content'] ?? '';
        $text = trim(preg_replace(['/```json\s*/', '/```\s*/'], '', $text));
        $materials = json_decode($text, true);

        if (!is_array($materials)) {
            return ['error' => 'Não foi possível interpretar o documento. Tente uma imagem mais nítida.'];
        }
        return ['success' => true, 'materials' => $materials];
    }

    /**
     * PDF: Upload via Files API + Responses API.
     */
    private static function parsePdfViaResponsesApi(string $tmpPath, string $fileName, string $apiKey, string $model): ?array
    {
        // 1. Upload do arquivo
        $ch = curl_init('https://api.openai.com/v1/files');
        $cFile = new \CURLFile($tmpPath, 'application/pdf', $fileName);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => ['file' => $cFile, 'purpose' => 'user_data'],
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $uploadResp = curl_exec($ch);
        $uploadCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($uploadCode !== 200) {
            $err = json_decode($uploadResp, true);
            return ['error' => 'Erro no upload do PDF: ' . ($err['error']['message'] ?? "HTTP {$uploadCode}")];
        }

        $fileId = json_decode($uploadResp, true)['id'] ?? null;
        if (!$fileId) return ['error' => 'Falha ao obter ID do arquivo.'];

        // 2. Responses API com input_file
        $ch = curl_init('https://api.openai.com/v1/responses');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode([
                'model' => $model,
                'input' => [[
                    'role' => 'user',
                    'content' => [
                        ['type' => 'input_file', 'file_id' => $fileId],
                        ['type' => 'input_text', 'text' => 'Analise este PDF e extraia TODOS os materiais/produtos listados. Retorne APENAS um JSON array (sem markdown, sem explicação). Cada item deve ter: name (nome do material), specification (tipo como "mat. Hidraulica", "mat. Civil", "madeira", "MATERIAL", "SERVICOS", etc), classification (medida como "100mm", "3/4", "50x40", etc), unit (unidade como "UN", "M", "KG", "M2", "M3", "L", etc), quantity (quantidade numérica, use 1 se não especificado). Retorne APENAS o JSON array.'],
                    ],
                ]],
            ]),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $apiKey,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 3. Deletar arquivo da OpenAI
        $ch = curl_init("https://api.openai.com/v1/files/{$fileId}");
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $apiKey],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        curl_exec($ch);
        curl_close($ch);

        if ($httpCode !== 200) {
            $err = json_decode($response, true);
            return ['error' => 'Erro na API OpenAI: ' . ($err['error']['message'] ?? "HTTP {$httpCode}")];
        }

        $result = json_decode($response, true);
        $responseText = '';
        if (isset($result['output'])) {
            foreach ($result['output'] as $output) {
                if (isset($output['content'])) {
                    foreach ($output['content'] as $content) {
                        if (isset($content['text'])) $responseText .= $content['text'];
                    }
                }
            }
        } elseif (isset($result['choices'][0]['message']['content'])) {
            $responseText = $result['choices'][0]['message']['content'];
        }

        if (empty($responseText)) return ['error' => 'Resposta vazia da IA.'];

        $responseText = trim(preg_replace(['/```json\s*/', '/```\s*/'], '', $responseText));
        $materials = json_decode($responseText, true);

        if (!is_array($materials)) {
            return ['error' => 'Não foi possível interpretar o documento. Resposta: ' . mb_substr($responseText, 0, 200)];
        }
        return ['success' => true, 'materials' => $materials];
    }
}
