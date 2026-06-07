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

A revista DEVE ter EXATAMENTE 10 páginas com estes layouts específicos (siga RIGOROSAMENTE):

PÁGINA 1 - layout: \"cover\"
- Título curto da revista (1-2 palavras impactantes, ex: \"NÚCLEO\", \"ECO\", \"RAÍZES\")
- Subtítulo no formato: \"CONSTRUÇÃO — SUSTENTÁVEL\" ou similar com 2 palavras separadas por travessão

PÁGINA 2 - layout: \"subcover\"
- Variação do título da capa (ex: se capa é \"NÚCLEO\", subcapa pode ser \"ECO BROOKS\")
- Subtítulo variação: \"CONSTRUÇÃO — CONSCIENTE\" ou similar

PÁGINA 3 - layout: \"internal_01\"
- Título uppercase em caixa alta (manchete da matéria principal)
- Conteúdo: 2 parágrafos densos de texto (cada um com 4-5 linhas)
- image_suggestion: foto de obra/construção para imagem full-width no topo
- image_suggestion_2: foto secundária para coluna direita

PÁGINA 4 - layout: \"internal_02\"
- Título grande bold italic (subtema)
- subtitle: frase complementar ao título
- Conteúdo: 2 parágrafos
- image_suggestion: imagem principal grande (ocupa lado esquerdo)
- image_suggestion_2: imagem menor para coluna direita inferior

PÁGINA 5 - layout: \"internal_03\"
- Título bold grande italic
- subtitle: subtítulo descritivo
- Conteúdo: 2 parágrafos longos (texto principal full-width)
- image_suggestion: imagem 1 para grid
- image_suggestion_2: imagem 2 para grid
- caption: legenda das imagens (texto em itálico)

PÁGINA 6 - layout: \"internal_04\"
- Título bold grande (será sobreposto na imagem com fundo escuro)
- subtitle: texto curto uppercase que aparece ao lado do título
- Conteúdo: 3 parágrafos de texto abaixo da imagem
- image_suggestion: imagem impactante full-width com paisagem/obra

PÁGINA 7 - layout: \"internal_05\"
- Título bold (seção)
- subtitle: \"LOREM IPSUM\" / subtítulo curto
- Conteúdo: 2 parágrafos em coluna esquerda + 2 parágrafos em coluna direita (separe com |||)
- image_suggestion: imagem 1 (lado esquerdo)
- image_suggestion_2: imagem 2 (lado direito)
- caption: legenda curta para as imagens

PÁGINA 8 - layout: \"internal_06\"
- Sem título (página de galeria)
- Conteúdo: 2 parágrafos de texto que ficam à direita das imagens
- image_suggestion: imagem para grid (4 imagens serão geradas)
- image_suggestion_2: segunda imagem para grid

PÁGINA 9 - layout: \"internal_07\"
- Título: frase de destaque/citação grande (1-2 linhas impactantes sobre o tema)
- Conteúdo: 2 parágrafos que ficam à direita da imagem
- image_suggestion: imagem de apoio

PÁGINA 10 - layout: \"backcover\"
- Conteúdo: frase institucional (\"Construção consciente do zero ao acabamento. Comprometidos com o meio ambiente, com as pessoas e com o futuro.\")

REGRAS IMPORTANTES:
- Todo conteúdo DEVE ser sobre o tema \"{$topicTitle}\"
- Textos profissionais, informativos, voltados para clientes de alto padrão
- Cada parágrafo deve ter 3-5 linhas (nem muito curto, nem muito longo)
- Títulos devem ser criativos e impactantes
- image_suggestion deve descrever fotos reais de construção/arquitetura/reformas

Retorne APENAS JSON válido (sem markdown, sem ```):
{
    \"title\": \"TÍTULO CURTO DA REVISTA\",
    \"subtitle\": \"CONSTRUÇÃO — SUSTENTÁVEL\",
    \"pages\": [
        {
            \"layout\": \"cover\",
            \"title\": \"TÍTULO\",
            \"subtitle\": \"CONSTRUÇÃO — SUSTENTÁVEL\"
        },
        {
            \"layout\": \"subcover\",
            \"title\": \"VARIAÇÃO TÍTULO\",
            \"subtitle\": \"CONSTRUÇÃO — CONSCIENTE\"
        },
        {
            \"layout\": \"internal_01\",
            \"title\": \"TÍTULO DA MATÉRIA\",
            \"content\": \"Parágrafo 1...\\n\\nParágrafo 2...\",
            \"image_suggestion\": \"descrição da imagem principal\",
            \"image_suggestion_2\": \"descrição da imagem secundária\"
        },
        ...continua para todas as 10 páginas...
    ]
}";

        $response = $this->chatCompletion($prompt);
        
        // Limpa possíveis marcadores de code block
        $response = trim($response);
        $response = preg_replace('/^```json\s*/i', '', $response);
        $response = preg_replace('/\s*```$/i', '', $response);
        
        $content = json_decode($response, true);

        if (!is_array($content) || !isset($content['pages'])) {
            throw new \Exception('Resposta inválida da IA ao gerar conteúdo da revista.');
        }

        // Tenta gerar imagens para cada página
        foreach ($content['pages'] as &$page) {
            if (!empty($page['image_suggestion'])) {
                try {
                    $page['image_url'] = $this->generateImage($page['image_suggestion']);
                } catch (\Exception $e) {
                    $page['image_url'] = null;
                }
            }
            if (!empty($page['image_suggestion_2'])) {
                try {
                    $page['image_url_2'] = $this->generateImage($page['image_suggestion_2']);
                } catch (\Exception $e) {
                    $page['image_url_2'] = null;
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
