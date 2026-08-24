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

    public function generateTopics(int $quantity = 10, string $customPrompt = '', string $sourceUrls = ''): array
    {
        $today = date('d/m/Y');
        $year = date('Y');

        $prompt = "Você é um especialista em construção civil, reformas e arquitetura no Brasil. 
        DATA DE HOJE: {$today}. Estamos em {$year}.
        
        Gere {$quantity} temas para revistas digitais da Brooks Construtora, uma empresa especializada em reformas e construções de alto padrão em São Paulo.
        
        IMPORTANTE: Os temas devem ser ATUAIS e relevantes para {$year}. NÃO mencione anos anteriores (2022, 2023, 2024, 2025). Todos os temas devem refletir tendências, novidades e realidades de {$year}.
        
        Os temas devem ser relevantes, educativos e interessantes para clientes de alto padrão interessados em:
        - Reformas residenciais e corporativas
        - Construção sustentável
        - Tendências de arquitetura e design
        - Materiais e tecnologias de construção
        - Valorização de imóveis
        - Sustentabilidade na construção";

        if (!empty($customPrompt)) {
            $prompt .= "\n\nINSTRUÇÕES ADICIONAIS DO USUÁRIO:\n{$customPrompt}";
        }

        if (!empty($sourceUrls)) {
            $prompt .= "\n\nIMPORTANTE: Baseie os temas nas seguintes fontes/referências. Crie temas inspirados no conteúdo desses links, mas adaptando para o contexto ATUAL ({$year}). Ignore datas antigas que possam aparecer nas fontes — adapte para a realidade de hoje:\n{$sourceUrls}";
        }

        $prompt .= "\n\nRetorne em formato JSON com a seguinte estrutura:
        [{\"title\": \"Título do tema\", \"description\": \"Breve descrição do que a revista abordará\"}]
        
        Retorne APENAS o JSON, sem markdown ou texto adicional.";

        $response = $this->chatCompletion($prompt);
        $topics = json_decode($response, true);

        if (!is_array($topics)) {
            throw new \Exception('Resposta inválida da IA ao gerar temas.');
        }

        return $topics;
    }

    public function generateMagazineContent(string $topicTitle, string $topicDescription, string $sourceUrls = ''): array
    {
        $sourcesInstruction = '';
        if (!empty($sourceUrls)) {
            $sourcesInstruction = "\n\nFONTES FORNECIDAS PELO USUÁRIO:\n{$sourceUrls}\n\nREGRAS SOBRE FONTES:\n- Liste nas fontes APENAS URLs que REALMENTE existem e que você REALMENTE usou como base.\n- As URLs fornecidas acima devem aparecer nas fontes.\n- Você PODE adicionar outras URLs reais que você conhece e usou como referência.\n- NUNCA invente URLs. Se uma URL não existe de verdade, NÃO coloque.\n- NÃO crie URLs fictícias com códigos inventados (como /a/XYZ123, /artigo/ABC).\n- Cada fonte deve ser uma URL real, verificável, de um site que existe.\n\nAo final do JSON, inclua um campo \"sources\" com array: [{\"title\": \"Título real do artigo/página\", \"url\": \"URL real que existe\", \"author\": \"Nome do site/veículo\"}]";
        }

        $prompt = "Crie conteúdo para uma revista digital da Brooks Construtora sobre: {$topicTitle} - {$topicDescription}{$sourcesInstruction}

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

    // ---------------------------------------------------------------
    // Transcrição de áudio via Whisper-1
    // ---------------------------------------------------------------

    /**
     * Envia um arquivo de áudio para a API Whisper e retorna a transcrição.
     *
     * @param  string $filePath  Caminho absoluto para o arquivo de áudio no servidor
     * @param  string $language  Código ISO do idioma (default: 'pt')
     * @return string            Texto transcrito
     * @throws \Exception        Em caso de erro de API ou arquivo inválido
     */
    public function transcribeAudio(string $filePath, string $language = 'pt'): string
    {
        if (!file_exists($filePath)) {
            throw new \Exception("Arquivo de áudio não encontrado: {$filePath}");
        }

        $url = 'https://api.openai.com/v1/audio/transcriptions';

        $ch = curl_init($url);

        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => [
                'file'     => new \CURLFile($filePath),
                'model'    => 'whisper-1',
                'language' => $language,
                'response_format' => 'json',
            ],
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_TIMEOUT        => 60,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception("Erro de comunicação com Whisper: {$error}");
        }

        curl_close($ch);

        if ($httpCode !== 200) {
            $errorData = json_decode($response, true);
            $message = $errorData['error']['message'] ?? 'Erro desconhecido';
            throw new \Exception("Erro Whisper (HTTP {$httpCode}): {$message}");
        }

        $result = json_decode($response, true);

        if (!isset($result['text'])) {
            throw new \Exception('Resposta inesperada da API Whisper: ' . $response);
        }

        return trim($result['text']);
    }

    // ---------------------------------------------------------------
    // Geração do Objeto do Contrato
    // ---------------------------------------------------------------

    /**
     * Substitui variáveis no template e envia para o modelo de texto,
     * retornando o texto redigido do Objeto do Contrato.
     *
     * @param  string $promptTemplate  Template com variáveis {{...}}
     * @param  array  $variables       Mapa ['cliente_nome' => 'João', ...]
     * @return array  ['text' => string, 'prompt_used' => string]
     */
    public function generateContractObject(string $promptTemplate, array $variables): array
    {
        // Substitui todas as variáveis {{chave}} pelos valores reais
        $prompt = $promptTemplate;
        foreach ($variables as $key => $value) {
            $prompt = str_replace('{{' . $key . '}}', (string) ($value ?? ''), $prompt);
        }

        // Remove variáveis que não foram substituídas (campos vazios)
        $prompt = preg_replace('/\{\{[^}]+\}\}/', '(não informado)', $prompt);

        $data = [
            'model'    => $this->model,
            'messages' => [
                [
                    'role'    => 'system',
                    'content' => 'Você é um advogado especialista em contratos de construção civil no Brasil. Redija de forma clara, técnica e juridicamente sólida. Responda apenas com o texto da cláusula, sem comentários adicionais.',
                ],
                [
                    'role'    => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => 0.4,
            'max_tokens'  => 2048,
        ];

        $response = $this->request('https://api.openai.com/v1/chat/completions', $data);
        $result   = json_decode($response, true);

        if (!isset($result['choices'][0]['message']['content'])) {
            throw new \Exception('Resposta inválida da IA ao gerar objeto do contrato.');
        }

        return [
            'text'        => trim($result['choices'][0]['message']['content']),
            'prompt_used' => $prompt,
        ];
    }

    // ---------------------------------------------------------------
    // Extração de dados do briefing a partir de PDF (Demanda #61)
    // ---------------------------------------------------------------

    public function extractBriefingFromPdf(string $filePath, string $fileName): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception("Arquivo PDF não encontrado: {$filePath}");
        }

        // Upload para OpenAI Files API
        $fileId = $this->uploadPdfToOpenAI($filePath, $fileName);

        // Processar com Responses API
        $prompt = $this->buildPdfExtractionPrompt();

        $data = [
            'model' => $this->model,
            'input' => [
                [
                    'role' => 'user',
                    'content' => [
                        ['type' => 'file', 'file' => ['file_id' => $fileId]],
                        ['type' => 'text', 'text' => $prompt],
                    ],
                ],
            ],
            'temperature' => 0.1,
        ];

        $ch = curl_init('https://api.openai.com/v1/responses');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($data),
            CURLOPT_HTTPHEADER     => [
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
            throw new \Exception("Erro ao processar PDF: {$error}");
        }
        curl_close($ch);

        // Se Responses API falhar, tenta fallback via chat/completions
        if ($httpCode !== 200) {
            return $this->extractPdfFallback($filePath);
        }

        $result = json_decode($response, true);
        $text = '';
        if (isset($result['output'])) {
            foreach ($result['output'] as $block) {
                if (isset($block['content'])) {
                    foreach ($block['content'] as $c) {
                        if (($c['type'] ?? '') === 'output_text') $text = $c['text'] ?? '';
                    }
                }
            }
        }

        if (empty($text)) {
            throw new \Exception('A IA não retornou dados do PDF.');
        }

        return $this->parsePdfResponse($text);
    }

    private function uploadPdfToOpenAI(string $filePath, string $fileName): string
    {
        $ch = curl_init('https://api.openai.com/v1/files');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => [
                'purpose' => 'user_data',
                'file'    => new \CURLFile($filePath, 'application/pdf', $fileName),
            ],
            CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $this->apiKey],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 60,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if (curl_errno($ch)) { $e = curl_error($ch); curl_close($ch); throw new \Exception("Upload PDF: {$e}"); }
        curl_close($ch);
        if ($httpCode !== 200) {
            $err = json_decode($response, true);
            throw new \Exception('Upload PDF: ' . ($err['error']['message'] ?? "HTTP {$httpCode}"));
        }
        $data = json_decode($response, true);
        if (empty($data['id'])) throw new \Exception('Upload PDF: resposta inválida.');
        return $data['id'];
    }

    private function extractPdfFallback(string $filePath): array
    {
        $base64 = base64_encode(file_get_contents($filePath));
        $prompt = $this->buildPdfExtractionPrompt();

        $data = [
            'model'    => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => 'Extraia dados de briefing do PDF. Responda APENAS com JSON válido.'],
                ['role' => 'user', 'content' => [
                    ['type' => 'text', 'text' => $prompt],
                    ['type' => 'file', 'file' => ['filename' => 'briefing.pdf', 'file_data' => 'data:application/pdf;base64,' . $base64]],
                ]],
            ],
            'temperature' => 0.1,
            'max_tokens'  => 4096,
        ];

        $response = $this->request('https://api.openai.com/v1/chat/completions', $data);
        $result = json_decode($response, true);
        $text = $result['choices'][0]['message']['content'] ?? '';
        return $this->parsePdfResponse($text);
    }

    private function parsePdfResponse(string $text): array
    {
        $text = preg_replace('/^```json\s*/i', '', trim($text));
        $text = preg_replace('/\s*```$/i', '', $text);
        $fields = json_decode($text, true);
        if (!is_array($fields)) throw new \Exception('Resposta da IA não é JSON válido.');
        return array_map(fn($v) => ($v === null || $v === 'null' || $v === 'N/A') ? '' : (string)$v, $fields);
    }

    private function buildPdfExtractionPrompt(): string
    {
        return 'Analise o PDF anexado (briefing de obra/construção civil). Extraia as informações e retorne APENAS JSON com estas chaves (string vazia "" se não encontrado — NUNCA invente dados):

{
  "client_name":"","client_document":"","client_phone":"","client_email":"",
  "client_nationality":"","client_marital_status":"",
  "project_type":"","project_address":"","project_address_number":"","project_complement":"",
  "project_neighborhood":"","project_city":"","project_state":"","project_cep":"",
  "project_goal":"","project_area":"",
  "preferences":"","priorities":"","needs":"","restrictions":"",
  "briefing_summary":"","negotiation_details":"",
  "contract_value":"","discount_value":"","discount_percent":"",
  "payment_method":"","payment_installments":"","payment_details":"",
  "project_number":"","start_date":"","end_date":"","deadline_days":"",
  "clauses":"","responsible_name":"","responsible_role":"",
  "contractor_company_name":"","contractor_cnpj":"","contractor_address":"",
  "contractor_city":"","contractor_state":"","contractor_cep":""
}

Regras: datas em YYYY-MM-DD, valores numéricos sem R$/%, documentos somente números.';
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

    // ---------------------------------------------------------------
    // Polimento de texto ditado por voz
    // ---------------------------------------------------------------

    /**
     * Recebe um texto bruto (ditado por voz) e retorna com gramática corrigida:
     * pontuação, vírgulas, primeira letra maiúscula, sem alterar o sentido.
     *
     * @param  string $rawText  Texto bruto da transcrição
     * @return string           Texto corrigido
     */
    public function polishText(string $rawText): string
    {
        $data = [
            'model'    => $this->model,
            'messages' => [
                [
                    'role'    => 'system',
                    'content' => 'Você é um corretor gramatical de português brasileiro. '
                        . 'Receba o texto ditado por voz e devolva APENAS o texto corrigido, sem explicações. '
                        . 'Regras: '
                        . '1) Inicie com letra maiúscula. '
                        . '2) Adicione vírgulas e pontos onde fizer sentido gramaticalmente. '
                        . '3) Remova espaços duplos ou triplos. '
                        . '4) NÃO altere o sentido, não adicione palavras novas, não remova informações. '
                        . '5) Mantenha o mesmo nível de formalidade do texto original. '
                        . '6) Se o texto já estiver correto, devolva-o sem alterações.',
                ],
                [
                    'role'    => 'user',
                    'content' => $rawText,
                ],
            ],
            'temperature' => 0.2,
            'max_tokens'  => 1024,
        ];

        $response = $this->request('https://api.openai.com/v1/chat/completions', $data);
        $result   = json_decode($response, true);

        if (!isset($result['choices'][0]['message']['content'])) {
            return $rawText; // Em caso de falha, retorna o original
        }

        return trim($result['choices'][0]['message']['content']);
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
