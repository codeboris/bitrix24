class TaskProcessor
{
    private $api;

    public function __construct(Bitrix24API $api)
    {
        $this->api = $api;
    }

    /**
     * Привязывает задачу к последней сделке контакта.
     *
     * @param int $taskId
     * @return void
     * @throws Exception
     */
    public function processTask(int $taskId): void
    {
        // 1. Получаем задачу
        $task = $this->getTask($taskId);

        // 2. Получаем контакт, связанный с задачей
        $contactId = $this->extractContactId($task);
        if (!$contactId) {
            throw new Exception("Контакт не найден в задаче ID $taskId");
        }

        // 3. Находим последнюю сделку контакта
        $lastDealId = $this->getLastDealIdForContact($contactId);
        if (!$lastDealId) {
            throw new Exception("Для контакта ID $contactId не найдено сделок");
        }

        // 4. Обновляем задачу, привязывая её к сделке
        $this->linkTaskToDeal($taskId, $lastDealId);

        echo "Задача ID $taskId успешно привязана к сделке ID $lastDealId.\n";
    }

    /**
     * Получает данные задачи по её ID.
     *
     * @param int $taskId
     * @return array
     * @throws Exception
     */
    private function getTask(int $taskId): array
    {
        $response = $this->api->call('tasks.task.get', ['taskId' => $taskId]);
        if (empty($response['result']['task'])) {
            throw new Exception("Задача ID $taskId не найдена.");
        }

        return $response['result']['task'];
    }

    /**
     * Извлекает ID контакта из задачи.
     *
     * @param array $task
     * @return int|null
     */
    private function extractContactId(array $task): ?int
    {
        if (!empty($task['UF_CRM_TASK'])) {
            foreach ($task['UF_CRM_TASK'] as $crmEntity) {
                if (strpos($crmEntity, 'C_') === 0) {
                    return (int)str_replace('C_', '', $crmEntity);
                }
            }
        }

        return null;
    }

    /**
     * Получает ID последней сделки для контакта.
     *
     * @param int $contactId
     * @return int|null
     * @throws Exception
     */
    private function getLastDealIdForContact(int $contactId): ?int
    {
        $response = $this->api->call('crm.deal.list', [
            'filter' => ['CONTACT_ID' => $contactId],
            'order' => ['DATE_CREATE' => 'DESC'],
            'select' => ['ID']
        ]);

        if (!empty($response['result'][0]['ID'])) {
            return (int)$response['result'][0]['ID'];
        }

        return null;
    }

    /**
     * Привязывает задачу к сделке.
     *
     * @param int $taskId
     * @param int $dealId
     * @return void
     * @throws Exception
     */
    private function linkTaskToDeal(int $taskId, int $dealId): void
    {
        $response = $this->api->call('tasks.task.update', [
            'taskId' => $taskId,
            'fields' => [
                'UF_CRM_TASK' => ['D_' . $dealId]
            ]
        ]);

        if (empty($response['result'])) {
            throw new Exception("Не удалось обновить задачу ID $taskId для привязки к сделке ID $dealId.");
        }
    }
}
