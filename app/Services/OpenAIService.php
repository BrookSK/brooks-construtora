<?php

namespace App\Services;

use App\Models\Setting;

class OpenAIService
{
    private string $apiKey;
    private string $model;
    private string $imageModel;

    public function __construct()
    {
        $this->apiKey = Setting::get('openai_api_key', '');
        $this->model = Setting::get('openai_model', 'gpt-4');
        $this->imageModel = Setting::get('openai_image_model', 'dall-e-3');

        if (empty($this->apiKey)) {
            throw new \Exception('Chave da API OpenAI não configurada. Acesse Configurações para definir.');
        }
    }

    public function generateTopics(int $quantity = 10): array
    {
        $prompt = "Você é um especialista em construção civil, reformas e arquitetura no Brasil. 
        Gere {$quantity} temas para revistas digitais da Brooks Construtora, uma empresa especializada em reformas e construções de alto padrão em São Paulo.
        
        Os temas devem ser relevantes, educativos e interessantes para clientes de alto padrão interessados em:
        - Reformas residenciais e corporativas
        - Construção sustentável
        - Tendências de arquitetura e design
        - Materiais e tecnologias de construção
        - Valorização de imóveis
        - Sustentabilidade na construção
        
        Retorne em formato JSON com a seguinte estrutura:
        [{\"title\": \"Título do tema\", \"description\": \"Breve descrição do que a revista abordará\"}]
        
        Retorne APENAS o JSON, sem markdown ou texto adicional.";

        $response = $this->chatCompletion($prompt);
        $topics = json_decode($response, true);

        if (!is_array($topics)) {
            throw new \Exception('Resposta inválida da IA ao gerar temas.');
        }

        return $topics;
    }

    public function generateMagazineContent(string $topicTitle, string $topicDescription): array
    {
        $prompt = "Crie conteúdo para uma revista digital da Brooks Construtora sobre: {$topicTitle} - {$topicDescription}

Gere EXATAMENTE 10 páginas em JSON. Cada página tem: layout, title, subtitle (opcional), content (parágrafos separados por \\n\\n), image_suggestion, image_suggestion_2 (opcional), caption (opcional).

Layouts obrigatórios na ordem:
1. \"cover\" - title: \"NÚCLEO\" (FIXO, sempre este valor), subtitle: \"CONSTRUÇÃO — SUSTENTÁVEL\"
2. \"subcover\" - title: \"ECO\" (FIXO, sempre este valor), subtitle: \"CONSTRUÇÃO — CONSCIENTE\"  
3. \"internal_01\" - title: manchete uppercase, content: 2 parágrafos, image_suggestion + image_suggestion_2
4. \"internal_02\" - title: subtema bold, subtitle: frase curta, content: 2 parágrafos, image_suggestion + image_suggestion_2
5. \"internal_03\" - title: titulo bold, subtitle: descritivo, content: 2 parágrafos longos, image_suggestion + image_suggestion_2, caption: legenda
6. \"internal_04\" - title: frase impacto (overlay), subtitle: complemento, content: 3 parágrafos, image_suggestion
7. \"internal_05\" - title: seção, content: texto coluna1 ||| texto coluna2, image_suggestion + image_suggestion_2, caption: legenda
8. \"internal_06\" - content: 2 parágrafos, image_suggestion + image_suggestion_2 (grid fotos)
9. \"internal_07\" - title: citação grande impactante, content: 2 parágrafos, image_suggestion
10. \"backcover\" - content: \"Construção consciente do zero ao acabamento. Comprometidos com o meio ambiente, com as pessoas e com o futuro.\"

IMPORTANTE: A cover DEVE ter title=\"NÚCLEO\" e a subcover DEVE ter title=\"ECO\". Nunca altere esses valores.

Textos profissionais sobre construção/reformas alto padrão. Parágrafos com 6-8 linhas cada (textos longos e detalhados para preencher a página). image_suggestion = descrição de foto de construção/arquitetura.

JSON puro sem markdown:
{\"title\":\"NÚCLEO\",\"subtitle\":\"CONSTRUÇÃO — SUSTENTÁVEL\",\"pages\":[...]}";

        $response = $this->chatCompletion($prompt);
        
        // Limpa possíveis marcadores de code block
        $response = trim($response);
        $response = preg_replace('/^```json\s*/i', '', $response);
        $response = preg_replace('/\s*```$/i', '', $response);
        
        $content = json_decode($response, true);

        if (!is_array($content) || !isset($content['pages'])) {
            throw new \Exception('Resposta inválida da IA ao gerar conteúdo da revista.');
        }

        // Tenta gerar imagens para cada página (DESABILITADO no fluxo síncrono para evitar timeout)
        // As imagens serão geradas sob demanda ou via cron
        foreach ($content['pages'] as &$page) {
            $page['image_url'] = null;
            $page['image_url_2'] = null;
        }

        return $content;
    }

    public function generateImage(string $description, string $orientation = 'landscape'): ?string
    {
        $size = $orientation === 'portrait' ? '1024x1536' : '1536x1024';
        
        $prompt = "Fotografia profissional de arquitetura e construção de alto padrão. {$description}. Estilo editorial para revista, iluminação natural, sem pessoas em destaque. IMPORTANTE: NÃO incluir nenhum texto, letra, palavra, número ou tipografia na imagem. Apenas fotografia pura sem elementos textuais.";

        $data = [
            'model' => $this->imageModel,
            'prompt' => $prompt,
            'n' => 1,
            'size' => $size,
        ];

        $response = $this->request('https://api.openai.com/v1/images/generations', $data);
        $result = json_decode($response, true);

        if (isset($result['data'][0]['url'])) {
            $imageUrl = $result['data'][0]['url'];
            $localPath = $this->downloadImage($imageUrl);
            return $localPath;
        } elseif (isset($result['data'][0]['b64_json'])) {
            // gpt-image-1 retorna base64
            $imageContent = base64_decode($result['data'][0]['b64_json']);
            $uploadDir = ROOT_PATH . '/public/uploads/magazines/ai/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $filename = 'ai_' . uniqid() . '.png';
            file_put_contents($uploadDir . $filename, $imageContent);
            return '/uploads/magazines/ai/' . $filename;
        }

        return null;
    }

    private function downloadImage(string $url): ?string
    {
        $imageContent = file_get_contents($url);
        if ($imageContent === false) {
            return null;
        }

        $uploadDir = ROOT_PATH . '/public/uploads/magazines/ai/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = 'ai_' . uniqid() . '.png';
        $filepath = $uploadDir . $filename;

        file_put_contents($filepath, $imageContent);
        return '/uploads/magazines/ai/' . $filename;
    }

    private function chatCompletion(string $prompt): string
    {
        $data = [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'Você é um assistente que gera conteúdo em JSON para revistas sobre construção civil e arquitetura de alto padrão. Responda APENAS com JSON válido, sem markdown.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.7,
            'max_tokens' => 4096,
        ];

        $response = $this->request('https://api.openai.com/v1/chat/completions', $data);
        $result = json_decode($response, true);

        if (isset($result['choices'][0]['message']['content'])) {
            return $result['choices'][0]['message']['content'];
        }

        throw new \Exception('Resposta inválida da API OpenAI: ' . $response);
    }

    private function request(string $url, array $data): string
    {
        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT => 120,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception("Erro de comunicação com a API: {$error}");
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            $error = json_decode($response, true);
            $message = $error['error']['message'] ?? 'Erro desconhecido';
            throw new \Exception("Erro da API OpenAI (HTTP {$httpCode}): {$message}");
        }

        return $response;
    }
}
