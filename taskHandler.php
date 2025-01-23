// === Конфигурация ===
// URL вебхука (замените на ваш)
$webhookUrl = 'https://yourcompany.bitrix24.ru/rest/USER_ID/WEBHOOK_KEY/';

try {
    // Инициализация API и обработчика задач
    $api = new Bitrix24API($webhookUrl);
    $processor = new TaskProcessor($api);

    // Получаем ID задачи из POST-запроса
    $inputData = json_decode(file_get_contents('php://input'), true);
    if (empty($inputData['taskId'])) {
        throw new Exception('Не указан ID задачи.');
    }

    $taskId = (int)$inputData['taskId'];

    // Обрабатываем задачу
    $processor->processTask($taskId);
} catch (Exception $e) {
    // Логирование ошибок
    error_log($e->getMessage());
    echo "Ошибка: " . $e->getMessage();
    http_response_code(400);
}
