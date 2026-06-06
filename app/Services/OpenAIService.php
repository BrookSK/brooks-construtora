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
        $prompt = "Você é um redator especializado em construção civil e arquitetura de alto padrão. 
        Crie o conteúdo completo para uma revista digital da Brooks Construtora.
        
        TEMA: {$topicTitle}
        DESCRIÇÃO: {$topicDescription}
        
        A revista deve seguir este modelo (baseado no PDF de referência):
        - Página 1 (Capa): título da revista e subtítulo
        - Página 2 (Subcapa): uma variação do título com o logo
        - Páginas 3-8: conteúdo editorial com textos informativos, divididos em seções
        - Página 9 (Contracapa): frase de impacto e informações da empresa
        
        Para cada página interna (3-8), crie:
        - Um título de seção
        - 2-3 parágrafos de conteúdo relevante e bem escrito
        - Uma sugestão de imagem para ilustrar
        
        O conteúdo deve ser profissional, informativo e voltado para clientes de alto padrão.
        
        Retorne em formato JSON:
        {
            \"title\": \"Título da revista\",
            \"subtitle\": \"Subtítulo/tema\",
            \"pages\": [
                {\"title\": \"Título da página\", \"content\": \"Conteúdo da página\", \"layout\": \"cover\", \"image_suggestion\": \"Descrição da imagem sugerida\"},
                ...
            ]
        }
        
        Retorne APENAS o JSON, sem markdown ou texto adicional.";

        $response = $this->chatCompletion($prompt);
        $content = json_decode($response, true);

        if (!is_array($content) || !isset($content['pages'])) {
            throw new \Exception('Resposta inválida da IA ao gerar conteúdo da revista.');
        }

        // Tenta gerar imagens para cada página
        foreach ($content['pages'] as &$page) {
            if (isset($page['image_suggestion']) && !empty($page['image_suggestion'])) {
                try {
                    $imageUrl = $this->generateImage($page['image_suggestion']);
                    $page['image_url'] = $imageUrl;
                } catch (\Exception $e) {
                    $page['image_url'] = null;
                    error_log('Erro ao gerar imagem: ' . $e->getMessage());
                }
            }
        }

        return $content;
    }

    public function generateImage(string $description): ?string
    {
        $prompt = "Imagem profissional para revista de construção/arquitetura de alto padrão: {$description}. Estilo fotográfico, alta qualidade, iluminação natural.";

        $data = [
            'model' => $this->imageModel,
            'prompt' => $prompt,
            'n' => 1,
            'size' => '1024x1024',
            'quality' => 'standard',
        ];

        $response = $this->request('https://api.openai.com/v1/images/generations', $data);
        $result = json_decode($response, true);

        if (isset($result['data'][0]['url'])) {
            // Baixa a imagem e salva localmente
            $imageUrl = $result['data'][0]['url'];
            $localPath = $this->downloadImage($imageUrl);
            return $localPath;
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
                ['role' => 'system', 'content' => 'Você é um assistente especializado em conteúdo sobre construção civil, arquitetura e reformas de alto padrão no Brasil.'],
                ['role' => 'user', 'content' => $prompt],
            ],
            'temperature' => 0.7,
            'max_tokens' => 4000,
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
