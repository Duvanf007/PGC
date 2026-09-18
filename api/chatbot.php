<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');

$data = json_decode(file_get_contents("php://input"), true);
$mensaje = $data['mensaje'] ?? '';

if (empty($mensaje)) {
    echo json_encode(['error' => 'Mensaje vacío']);
    exit;
}

// ==========================================
// CONFIGURACIÓN DE IA (Google Gemini)
// ==========================================
// Obtén tu clave gratis en: https://aistudio.google.com/app/apikey
// Y pégala aquí abajo:
$apiKey = 'PEGA_AQUI_TU_API_KEY_DE_GEMINI'; 

if ($apiKey === 'PEGA_AQUI_TU_API_KEY_DE_GEMINI') {
    // Si no hay clave aún, respondemos con un mensaje informativo
    echo json_encode([
        'respuesta' => "🤖 ¡Hola! Soy el asistente virtual. El sistema está listo, pero el administrador aún necesita configurar la **API Key gratuita de Gemini** en el archivo `api/chatbot.php`. Mientras tanto, solo repito lo que dijiste: *" . htmlspecialchars($mensaje) . "*"
    ]);
    exit;
}

$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key=' . $apiKey;

$payload = [
    'contents' => [
        [
            'parts' => [
                ['text' => "Eres el asistente virtual amable del sitio web de Seguridad Guachetá. Responde de forma concisa y amigable a este mensaje del usuario: " . $mensaje]
            ]
        ]
    ]
];

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

if ($httpCode !== 200) {
    echo json_encode(['respuesta' => "Lo siento, tuve un problema al conectarme con la Inteligencia Artificial. Error: $httpCode"]);
    exit;
}

$responseData = json_decode($response, true);
$textoRespuesta = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? 'No pude entender la respuesta de la IA.';

echo json_encode(['respuesta' => $textoRespuesta]);
